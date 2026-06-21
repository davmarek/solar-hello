const el = document.querySelector("[data-echarts]");
const chart = window.echarts.init(el, getColorModeLabel());
const opts = {
    title: {
        text: "Data Graph",
        subtext: "Collected in the last 24 hours",
    },
    legend: {},
    tooltip: {
        trigger: "axis",
    },
    toolbox: {
        feature: {
            dataZoom: {
                yAxisIndex: false,
            },
            saveAsImage: {},
        },
    },
    xAxis: {
        type: "time",
        data:
            $wire.chartSeries.length > 0
                ? $wire.chartSeries[0].data.map((i) => i[0])
                : [],
    },
    yAxis: [
        {
            type: "value",
            name: "Watts",
            position: "left",
        },
        {
            type: "value",
            name: "%",
            position: "right",
            min: 0,
            max: 100,
        },
    ],
    series: $wire.chartSeries,
};
chart.setOption(opts);

// LIVEWIRE JAVASCRIPT FUNCTIONS
$js.resizeChart = () => {
    if (chart) {
        chart.resize();
    }
};

// DARK MODE OBSERVER
const observer = new MutationObserver(() => {
    if (chart) {
        chart.setTheme(getColorModeLabel());
    }
});

observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
});

// Safely dispose the chart on navigating via wire:navigate
document.addEventListener("livewire:navigate", () => {
    chart.dispose();
});

function isDarkMode() {
    return document.documentElement.classList.contains("dark");
}

function getColorModeLabel() {
    return isDarkMode() ? "dark" : "default";
}
