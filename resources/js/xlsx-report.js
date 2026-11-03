// A management workbook with native Excel charts (Sakuna situation report,
// owner 2026-11-03). SheetJS's free build writes neither styles nor charts,
// so the workbook's parts are written here as plain Office Open XML and
// zipped with the ZIP writer SheetJS already ships (CFB): bold header rows,
// column widths, number formats, frozen headers and bar/column charts that
// read their numbers from the sheet's own cells, so a user can restyle them.
import { loadXlsx } from './resident-import';

const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
    // XML 1.0 has no place for control characters.
    .replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, '');
export const colName = (i) => { let s = ''; for (let n = i + 1; n > 0; n = Math.floor((n - 1) / 26)) s = String.fromCharCode(65 + ((n - 1) % 26)) + s; return s; };
const ref = (c, r) => colName(c) + (r + 1);
const quoteSheet = (name) => "'" + String(name).replace(/'/g, "''") + "'";

// Cell styles (cellXfs order below).
export const S = { plain: 0, header: 1, title: 2, subtitle: 3, section: 4, note: 5, para: 6, label: 7, num: 8, money: 9, pct: 10, wrap: 11 };

const STYLES = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="#,##0.00"/><numFmt numFmtId="166" formatCode="0%"/></numFmts>
<fonts count="7">
<font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="16"/><color rgb="FF0B1730"/><name val="Calibri"/><family val="2"/></font>
<font><sz val="11"/><color rgb="FF5F6B80"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="12"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>
<font><i/><sz val="9"/><color rgb="FF6B7280"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD6DFEE"/></left><right style="thin"><color rgb="FFD6DFEE"/></right><top style="thin"><color rgb="FFD6DFEE"/></top><bottom style="thin"><color rgb="FFD6DFEE"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="12">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>`;

function cellXml(v, c, r) {
    if (v === null || v === undefined || v === '') return '';
    const cell = typeof v === 'object' ? v : { v };
    const s = cell.s ? ` s="${cell.s}"` : '';
    if (typeof cell.v === 'number' && Number.isFinite(cell.v)) return `<c r="${ref(c, r)}"${s}><v>${cell.v}</v></c>`;
    if (cell.v === null || cell.v === undefined || cell.v === '') return cell.s ? `<c r="${ref(c, r)}"${s}/>` : '';
    return `<c r="${ref(c, r)}"${s} t="inlineStr"><is><t xml:space="preserve">${esc(cell.v)}</t></is></c>`;
}

function sheetXml(sheet, first, hasDrawing) {
    const pane = sheet.freeze ? `<pane ySplit="${sheet.freeze}" topLeftCell="A${sheet.freeze + 1}" activePane="bottomLeft" state="frozen"/>` : '';
    const cols = (sheet.cols || []).map((w, i) => `<col min="${i + 1}" max="${i + 1}" width="${w}" customWidth="1"/>`).join('');
    const rows = sheet.rows.map((row, r) => {
        const ht = sheet.heights?.[r] ? ` ht="${sheet.heights[r]}" customHeight="1"` : '';
        const cells = (row || []).map((v, c) => cellXml(v, c, r)).join('');
        return cells || ht ? `<row r="${r + 1}"${ht}>${cells}</row>` : '';
    }).join('');
    const merges = sheet.merges?.length ? `<mergeCells count="${sheet.merges.length}">${sheet.merges.map((m) => `<mergeCell ref="${m}"/>`).join('')}</mergeCells>` : '';
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>
<sheetViews><sheetView workbookViewId="0"${first ? ' tabSelected="1"' : ''}${sheet.gridlines === false ? ' showGridLines="0"' : ''}>${pane}</sheetView></sheetViews>
<sheetFormatPr defaultRowHeight="15"/>${cols ? `<cols>${cols}</cols>` : ''}<sheetData>${rows}</sheetData>${merges}
<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>
<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>${hasDrawing ? '<drawing r:id="rId1"/>' : ''}
</worksheet>`;
}

const hex = (c) => String(c || '#1E3A8A').replace('#', '').toUpperCase();

/**
 * A bar ('bar' horizontal, 'column' vertical) chart reading its categories
 * and values from the sheet. chart: { title, kind, sheet, catRange: [c, r0, r1],
 * series: [{ nameCell: [c, r], col, name, values, color, colors? }], categories, money }.
 */
function chartXml(ch) {
    const sh = quoteSheet(ch.sheet);
    const [cc, r0, r1] = ch.catRange;
    const n = r1 - r0 + 1;
    const catRef = `${sh}!$${colName(cc)}$${r0 + 1}:$${colName(cc)}$${r1 + 1}`;
    const strCache = (vals) => `<c:ptCount val="${vals.length}"/>` + vals.map((v, i) => `<c:pt idx="${i}"><c:v>${esc(v)}</c:v></c:pt>`).join('');
    const sers = ch.series.map((s, k) => {
        const vRef = `${sh}!$${colName(s.col)}$${r0 + 1}:$${colName(s.col)}$${r1 + 1}`;
        const dpts = (s.colors || []).map((c, i) => `<c:dPt><c:idx val="${i}"/><c:invertIfNegative val="0"/><c:bubble3D val="0"/><c:spPr><a:solidFill><a:srgbClr val="${hex(c)}"/></a:solidFill></c:spPr></c:dPt>`).join('');
        return `<c:ser><c:idx val="${k}"/><c:order val="${k}"/>
<c:tx><c:strRef><c:f>${sh}!$${colName(s.nameCell[0])}$${s.nameCell[1] + 1}</c:f><c:strCache>${strCache([s.name])}</c:strCache></c:strRef></c:tx>
<c:spPr><a:solidFill><a:srgbClr val="${hex(s.color)}"/></a:solidFill></c:spPr><c:invertIfNegative val="0"/>${dpts}
<c:dLbls><c:numFmt formatCode="#,##0;-#,##0;;" sourceLinked="0"/><c:spPr><a:noFill/><a:ln><a:noFill/></a:ln></c:spPr><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="800"><a:solidFill><a:srgbClr val="374151"/></a:solidFill></a:defRPr></a:pPr><a:endParaRPr lang="en-US"/></a:p></c:txPr><c:showLegendKey val="0"/><c:showVal val="1"/><c:showCatName val="0"/><c:showSerName val="0"/><c:showPercent val="0"/><c:showBubbleSize val="0"/></c:dLbls>
<c:cat><c:strRef><c:f>${catRef}</c:f><c:strCache>${strCache(ch.categories)}</c:strCache></c:strRef></c:cat>
<c:val><c:numRef><c:f>${vRef}</c:f><c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="${n}"/>${s.values.map((v, i) => `<c:pt idx="${i}"><c:v>${Number(v || 0)}</c:v></c:pt>`).join('')}</c:numCache></c:numRef></c:val>
</c:ser>`;
    }).join('');
    const horizontal = ch.kind === 'bar';
    const axisText = '<c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="800"><a:solidFill><a:srgbClr val="4B5563"/></a:solidFill></a:defRPr></a:pPr><a:endParaRPr lang="en-US"/></a:p></c:txPr>';
    const legend = ch.series.length > 1 ? '<c:legend><c:legendPos val="t"/><c:overlay val="0"/></c:legend>' : '';
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<c:roundedCorners val="0"/>
<c:chart>
<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="1200" b="1"/></a:pPr><a:r><a:rPr lang="en-US" sz="1200" b="1"><a:solidFill><a:srgbClr val="0B1730"/></a:solidFill></a:rPr><a:t>${esc(ch.title)}</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title>
<c:autoTitleDeleted val="0"/>
<c:plotArea><c:layout/>
<c:barChart><c:barDir val="${horizontal ? 'bar' : 'col'}"/><c:grouping val="clustered"/><c:varyColors val="0"/>${sers}
<c:gapWidth val="60"/><c:axId val="5001"/><c:axId val="5002"/></c:barChart>
<c:catAx><c:axId val="5001"/><c:scaling><c:orientation val="${horizontal ? 'maxMin' : 'minMax'}"/></c:scaling><c:delete val="0"/><c:axPos val="${horizontal ? 'l' : 'b'}"/><c:numFmt formatCode="General" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:spPr><a:ln><a:solidFill><a:srgbClr val="D6DFEE"/></a:solidFill></a:ln></c:spPr>${axisText}<c:crossAx val="5002"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/><c:noMultiLvlLbl val="0"/></c:catAx>
<c:valAx><c:axId val="5002"/><c:scaling><c:orientation val="minMax"/><c:min val="0"/></c:scaling><c:delete val="0"/><c:axPos val="${horizontal ? 'b' : 'l'}"/><c:majorGridlines><c:spPr><a:ln><a:solidFill><a:srgbClr val="E8EDF5"/></a:solidFill></a:ln></c:spPr></c:majorGridlines><c:numFmt formatCode="#,##0" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:spPr><a:ln><a:noFill/></a:ln></c:spPr>${axisText}<c:crossAx val="5001"/><c:crosses val="${horizontal ? 'max' : 'autoZero'}"/><c:crossBetween val="between"/>${!ch.money && Math.max(...ch.series.flatMap((s) => s.values.map(Number))) <= 10 ? '<c:majorUnit val="1"/>' : ''}</c:valAx>
<c:spPr><a:noFill/></c:spPr>
</c:plotArea>${legend}
<c:plotVisOnly val="1"/><c:dispBlanksAs val="gap"/>
</c:chart>
<c:spPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="D6DFEE"/></a:solidFill></a:ln></c:spPr>
</c:chartSpace>`;
}

function drawingXml(anchors) {
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">${anchors.map((a, i) => `
<xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>${a.col}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${a.row}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:to><xdr:col>${a.toCol}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${a.toRow}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>
<xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="${i + 2}" name="Chart ${i + 1}"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr><xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart"><c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId${i + 1}"/></a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>`).join('')}
</xdr:wsDr>`;
}

const rels = (list) => `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${list.map(([id, type, target]) => `<Relationship Id="${id}" Type="${type}" Target="${target}"/>`).join('')}</Relationships>`;
const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';

/**
 * The workbook as bytes. sheets: [{ name, rows, cols?, merges?, heights?, freeze?,
 * gridlines?, charts?: [{ ...chart, anchor: { col, row, toCol, toRow } }] }].
 */
export async function buildXlsx(sheets, meta = {}) {
    const XLSX = await loadXlsx();
    const CFB = XLSX.CFB;
    const zip = CFB.utils.cfb_new();
    const enc = new TextEncoder();
    const put = (path, text) => CFB.utils.cfb_add(zip, path, enc.encode(text));
    const overrides = [];
    let chartNo = 0;
    let drawingNo = 0;
    sheets.forEach((sh, i) => {
        const charts = sh.charts || [];
        put(`xl/worksheets/sheet${i + 1}.xml`, sheetXml(sh, i === 0, charts.length > 0));
        overrides.push([`/xl/worksheets/sheet${i + 1}.xml`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml']);
        if (!charts.length) return;
        drawingNo += 1;
        put(`xl/worksheets/_rels/sheet${i + 1}.xml.rels`, rels([['rId1', R + 'drawing', `../drawings/drawing${drawingNo}.xml`]]));
        const links = [];
        charts.forEach((ch, k) => {
            chartNo += 1;
            put(`xl/charts/chart${chartNo}.xml`, chartXml({ ...ch, sheet: sh.name }));
            overrides.push([`/xl/charts/chart${chartNo}.xml`, 'application/vnd.openxmlformats-officedocument.drawingml.chart+xml']);
            links.push([`rId${k + 1}`, R + 'chart', `../charts/chart${chartNo}.xml`]);
        });
        put(`xl/drawings/drawing${drawingNo}.xml`, drawingXml(charts.map((c) => c.anchor)));
        put(`xl/drawings/_rels/drawing${drawingNo}.xml.rels`, rels(links));
        overrides.push([`/xl/drawings/drawing${drawingNo}.xml`, 'application/vnd.openxmlformats-officedocument.drawing+xml']);
    });
    put('xl/workbook.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets>${sheets.map((sh, i) => `<sheet name="${esc(sh.name)}" sheetId="${i + 1}" r:id="rId${i + 1}"/>`).join('')}</sheets></workbook>`);
    put('xl/_rels/workbook.xml.rels', rels([...sheets.map((sh, i) => [`rId${i + 1}`, R + 'worksheet', `worksheets/sheet${i + 1}.xml`]), [`rId${sheets.length + 1}`, R + 'styles', 'styles.xml']]));
    put('xl/styles.xml', STYLES);
    const now = new Date().toISOString().replace(/\.\d+Z$/, 'Z');
    put('docProps/core.xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>${esc(meta.title || 'Report')}</dc:title><dc:creator>${esc(meta.creator || 'Municipality of Esperanza')}</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">${now}</dcterms:created></cp:coreProperties>`);
    put('_rels/.rels', rels([['rId1', R + 'officeDocument', 'xl/workbook.xml'], ['rId2', 'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties', 'docProps/core.xml']]));
    put('[Content_Types].xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>${overrides.map(([p, t]) => `<Override PartName="${p}" ContentType="${t}"/>`).join('')}</Types>`);
    // CFB keeps a root entry of its own; the ZIP is written without it.
    return CFB.write(zip, { fileType: 'zip', type: 'array', compression: true });
}

/** Build and download. */
export async function downloadXlsx(sheets, filename, meta) {
    const bytes = await buildXlsx(sheets, meta);
    const blob = new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}
