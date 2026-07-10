import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { Chart, ArcElement, DoughnutController, LineElement, LineController, PointElement, LinearScale, CategoryScale, Filler, Tooltip, Legend } from 'chart.js';

Chart.register(ArcElement, DoughnutController, LineElement, LineController, PointElement, LinearScale, CategoryScale, Filler, Tooltip, Legend);
Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#64748b';

window.Alpine = Alpine;
window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    Alpine.data('dropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));

    Alpine.data('sidebar', () => ({
        collapsed: false,
        mobileOpen: false,
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
        },
    }));

    Alpine.data('donutChart', (config) => ({
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: config.labels,
                    datasets: [{
                        data: config.values,
                        backgroundColor: config.colors,
                        borderWidth: 0,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    cutout: '72%',
                    plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } },
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    maintainAspectRatio: false,
                },
            });
        },
    }));

    Alpine.data('lineChart', (config) => ({
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: config.labels,
                    datasets: config.datasets.map((set) => ({
                        label: set.label,
                        data: set.data,
                        borderColor: set.color,
                        backgroundColor: set.fill ?? 'transparent',
                        tension: 0.4,
                        fill: !!set.fill,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: set.color,
                        borderWidth: 2.5,
                    })),
                },
                options: {
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v) => config.yPrefix ? config.yPrefix + v : v } },
                    },
                },
            });
        },
    }));

    Alpine.data('passwordField', () => ({
        visible: false,
        toggle() {
            this.visible = !this.visible;
            window.renderIcons?.();
        },
    }));

    Alpine.data('authForm', (redirectUrl = null) => ({
        loading: false,
        done: false,
        submit() {
            if (this.loading) return;
            this.loading = true;
            setTimeout(() => {
                this.loading = false;
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    this.done = true;
                    this.$dispatch('toast', { message: 'This portal is a frontend preview — sign-in is mocked for now.', variant: 'info' });
                    setTimeout(() => (this.done = false), 2600);
                }
            }, 900);
        },
    }));

    Alpine.data('toast', () => ({
        items: [],
        push(message, variant = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, variant });
            this.$nextTick(() => window.renderIcons?.());
            setTimeout(() => this.dismiss(id), 4000);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    }));
});

// Lucide swaps each <i data-lucide> for a brand new <svg> node. Any Alpine
// directive on that icon (x-show, :class) only binds if Alpine walks the
// DOM *after* the swap — so icons must render before Alpine.start().
function renderIcons() {
    createIcons({ icons });
}

window.renderIcons = renderIcons;

renderIcons();
Alpine.start();
