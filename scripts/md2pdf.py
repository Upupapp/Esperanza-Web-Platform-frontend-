#!/usr/bin/env python3
"""Render one of the Master Command markdown books to a print-ready PDF.

Usage:  python3 scripts/md2pdf.py ESPERANZA_BACKEND_MASTER_COMMAND.md

Lives in the repo on purpose. It was previously kept in a session scratchpad
and lost three times, and each loss silently produced an empty PDF -- once
overwriting a good one. The books are committed artifacts; the thing that
builds them belongs beside them.

Renders via headless Chrome (no pandoc/wkhtmltopdf dependency). Handles the
markdown subset these books actually use: headings, tables, fenced code,
blockquotes, lists, bold/italic/inline-code/links, and horizontal rules.
"""
import html
import os
import re
import shutil
import subprocess
import sys
import tempfile

CHROME_CANDIDATES = [
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/Applications/Chromium.app/Contents/MacOS/Chromium',
    shutil.which('google-chrome') or '',
    shutil.which('chromium') or '',
]

CSS = """
@page { size: A4; margin: 17mm 15mm 18mm 15mm; }
* { box-sizing: border-box; }
body { font-family: "Charter","Iowan Old Style",Georgia,serif; font-size: 9.6pt;
       line-height: 1.52; color: #1a2230; margin: 0; }
h1 { font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; font-size: 23pt;
     line-height: 1.15; letter-spacing: -0.02em; margin: 0 0 6pt; color: #0b1b33; }
h2 { font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; font-size: 13.5pt;
     margin: 22pt 0 7pt; padding-bottom: 4pt; border-bottom: 1.2pt solid #0b1b33;
     color: #0b1b33; page-break-after: avoid; }
h2.tab { background: #0b1b33; color: #fff; border: 0; padding: 7pt 9pt;
         margin: 26pt 0 9pt; border-radius: 3pt; }
h3 { font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; font-size: 10.6pt;
     margin: 14pt 0 5pt; color: #14304f; page-break-after: avoid; }
h4 { font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; font-size: 9.6pt;
     margin: 11pt 0 4pt; color: #2c4a6b; page-break-after: avoid; }
p { margin: 0 0 7pt; text-align: justify; hyphens: auto; }
ul, ol { margin: 0 0 8pt; padding-left: 15pt; }
li { margin: 0 0 3.5pt; }
code { font-family: "SF Mono",Menlo,Consolas,monospace; font-size: 8.3pt;
       background: #eef2f7; padding: 0.5pt 2.5pt; border-radius: 2pt; color: #14304f; }
pre { background: #f5f7fa; border-left: 2.5pt solid #b8c6d8; padding: 7pt 9pt;
      margin: 0 0 9pt; border-radius: 0 3pt 3pt 0; page-break-inside: avoid;
      overflow-wrap: break-word; }
pre code { background: none; padding: 0; font-size: 7.9pt; line-height: 1.42; }
blockquote { margin: 0 0 10pt; padding: 8pt 10pt; background: #fff8e6;
             border-left: 2.5pt solid #d4a017; font-style: italic;
             border-radius: 0 3pt 3pt 0; }
blockquote p { margin: 0; text-align: left; }
table { width: 100%; border-collapse: collapse; margin: 0 0 10pt; font-size: 8.3pt;
        page-break-inside: avoid;
        font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; }
th { background: #0b1b33; color: #fff; text-align: left; padding: 4.5pt 5pt;
     font-weight: 600; font-size: 7.9pt; }
td { padding: 4pt 5pt; border-bottom: 0.5pt solid #dde4ec; vertical-align: top; }
tbody tr:nth-child(even) { background: #f7f9fc; }
td code, th code { font-size: 7.5pt; white-space: nowrap; }
hr { border: 0; border-top: 0.5pt solid #cfd8e3; margin: 16pt 0; }
a { color: #14304f; text-decoration: none; }
strong { color: #0b1b33; }
"""


def inline(t):
    t = html.escape(t)
    t = re.sub(r'`([^`]+)`', r'<code>\1</code>', t)
    t = re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', t)
    t = re.sub(r'(?<![\w*])\*([^*\n]+)\*(?![\w*])', r'<em>\1</em>', t)
    return re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'<a href="\2">\1</a>', t)


def cells(row):
    r = row.strip().strip('|')
    return [c.strip() for c in r.split('|')]


def to_html(src):
    lines, out, i = src.split('\n'), [], 0
    while i < len(lines):
        s = lines[i].strip()
        if s.startswith('```'):
            i += 1
            buf = []
            while i < len(lines) and not lines[i].strip().startswith('```'):
                buf.append(html.escape(lines[i]))
                i += 1
            i += 1
            out.append('<pre><code>' + '\n'.join(buf) + '</code></pre>')
            continue
        if re.match(r'^\|.*\|$', s) and i + 1 < len(lines) \
                and re.match(r'^\|[\s:|-]+\|$', lines[i + 1].strip()):
            head = cells(s)
            i += 2
            rows = []
            while i < len(lines) and lines[i].strip().startswith('|'):
                rows.append(cells(lines[i].strip()))
                i += 1
            t = ['<table><thead><tr>'] + ['<th>%s</th>' % inline(c) for c in head] \
                + ['</tr></thead><tbody>']
            for r in rows:
                t.append('<tr>' + ''.join('<td>%s</td>' % inline(c) for c in r) + '</tr>')
            out.append(''.join(t) + '</tbody></table>')
            continue
        m = re.match(r'^(#{1,4})\s+(.*)$', s)
        if m:
            lvl = len(m.group(1))
            cls = ' class="tab"' if re.match(r'^TAB\s', m.group(2)) else ''
            out.append('<h%d%s>%s</h%d>' % (lvl, cls, inline(m.group(2)), lvl))
            i += 1
            continue
        if re.match(r'^---+$', s):
            out.append('<hr>')
            i += 1
            continue
        if s.startswith('> '):
            buf = []
            while i < len(lines) and lines[i].strip().startswith('>'):
                buf.append(lines[i].strip().lstrip('>').strip())
                i += 1
            out.append('<blockquote>%s</blockquote>' % inline(' '.join(buf)))
            continue
        if re.match(r'^\d+\.\s', s) or s.startswith('- '):
            ordered = bool(re.match(r'^\d+\.\s', s))
            tag, items = ('ol' if ordered else 'ul'), []
            while i < len(lines):
                cur = lines[i].strip()
                starts = re.match(r'^\d+\.\s', cur) if ordered else cur.startswith('- ')
                if starts:
                    items.append(re.sub(r'^(\d+\.|-)\s+', '', cur))
                    i += 1
                    while i < len(lines) and lines[i].startswith('  ') \
                            and lines[i].strip() \
                            and not re.match(r'^\s*(\d+\.|-)\s', lines[i]):
                        items[-1] += ' ' + lines[i].strip()
                        i += 1
                elif cur == '' and i + 1 < len(lines):
                    nxt = lines[i + 1].strip()
                    if (re.match(r'^\d+\.\s', nxt) if ordered else nxt.startswith('- ')):
                        i += 1
                    else:
                        break
                else:
                    break
            out.append('<%s>%s</%s>' % (tag, ''.join('<li>%s</li>' % inline(x) for x in items), tag))
            continue
        if s == '':
            i += 1
            continue
        buf = []
        while i < len(lines) and lines[i].strip() and not re.match(
                r'^(#{1,4}\s|---+$|\||>|```|\d+\.\s|-\s)', lines[i].strip()):
            buf.append(lines[i].strip())
            i += 1
        if buf:
            out.append('<p>%s</p>' % inline(' '.join(buf)))
        else:
            i += 1
    return '\n'.join(out)


def main():
    if len(sys.argv) < 2:
        sys.exit('usage: md2pdf.py <book.md> [out.pdf]')
    md = sys.argv[1]
    pdf = sys.argv[2] if len(sys.argv) > 2 else os.path.splitext(md)[0] + '.pdf'

    chrome = next((c for c in CHROME_CANDIDATES if c and os.path.exists(c)), None)
    if not chrome:
        sys.exit('error: no Chrome/Chromium found; cannot render a PDF.')

    body = to_html(open(md).read())
    doc = ('<!doctype html><html><head><meta charset="utf-8"><title>%s</title>'
           '<style>%s</style></head><body>%s</body></html>'
           % (html.escape(os.path.basename(md)), CSS, body))

    with tempfile.TemporaryDirectory() as tmp:
        src = os.path.join(tmp, 'book.html')
        dst = os.path.join(tmp, 'book.pdf')
        open(src, 'w').write(doc)
        subprocess.run([chrome, '--headless', '--disable-gpu', '--no-sandbox',
                        '--no-pdf-header-footer', '--print-to-pdf=' + dst,
                        '--virtual-time-budget=8000', 'file://' + src],
                       capture_output=True, check=False)
        # An empty render must never overwrite a good committed PDF -- that has
        # already happened once, when the renderer was missing.
        if not os.path.exists(dst) or os.path.getsize(dst) < 20000:
            sys.exit('error: render produced no usable PDF; %s left untouched.' % pdf)
        shutil.copy(dst, pdf)

    size = os.path.getsize(pdf)
    pages = len(re.findall(rb'/Type\s*/Page[^s]', open(pdf, 'rb').read()))
    print('%s: %d KB, %d pages' % (pdf, size // 1024, pages))
    if pages < 5:
        sys.exit('error: %s has only %d page(s) -- that is not a book.' % (pdf, pages))


if __name__ == '__main__':
    main()
