document.addEventListener('DOMContentLoaded', function () {
    // Check if ApexCharts is defined
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts library is not loaded. Ensure apexcharts.min.js is included and the path is correct.');
        return;
    }

    // Define chart configurations for all charts
    const chartConfigs = [
        {
            id: 'population_chart',
            color: '#405189',
            data: [10, 20, 15, 30, 25, 40, 35]
        },
        {
            id: 'staff_chart',
            color: '#f7b84b',
            data: [5, 15, 10, 20, 15, 25, 20]
        },
        {
            id: 'male_chart',
            color: '#6c757d',
            data: [8, 18, 12, 25, 20, 30, 28]
        },
        {
            id: 'female_chart',
            color: '#0ab39c',
            data: [12, 22, 18, 28, 23, 35, 30]
        },
        {
            id: 'mini-chart-6',
            color: '#6c757d',
            data: [8, 12, 10, 15, 14, 18, 16]
        },
        {
            id: 'mini-chart-7',
            color: '#405189',
            data: [10, 15, 20, 25, 20, 30, 28]
        },
        {
            id: 'mini-chart-8',
            color: '#f7b84b',
            data: [5, 10, 8, 12, 10, 15, 14]
        },
        {
            id: 'mini-chart-9',
            color: '#0ab39c',
            data: [12, 18, 15, 20, 18, 25, 22]
        }
    ];

    // Initialize ApexCharts for each chart
    chartConfigs.forEach(function (config) {
        const chartElement = document.getElementById(config.id);
        if (chartElement) {
            console.log(`Initializing chart for ${config.id}`);
            const options = {
                chart: {
                    type: 'line',
                    height: 60,
                    sparkline: {
                        enabled: true
                    }
                },
                series: [{
                    name: config.id.replace('mini-chart-', 'Chart ').replace('_chart', ''),
                    data: config.data
                }],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                colors: [config.color],
                tooltip: {
                    enabled: true,
                    x: { show: false },
                    y: {
                        formatter: function (val) {
                            return val;
                        }
                    }
                }
            };

            try {
                new ApexCharts(chartElement, options).render();
            } catch (error) {
                console.error(`Error rendering chart ${config.id}:`, error);
            }
        } else {
            console.warn(`Chart element with ID ${config.id} not found in DOM.`);
        }
    });

    // Initialize counter animations for .counter-value elements
    const counters = document.querySelectorAll('.counter-value');
    console.log(`Found ${counters.length} counter elements`);
    counters.forEach(function (counter) {
        const target = parseInt(counter.getAttribute('data-target'), 10);
        if (isNaN(target)) {
            console.warn(`Invalid data-target value for counter: ${counter.getAttribute('data-target')}`);
            return;
        }

        let count = 0;
        const step = target / 50;
        const updateCounter = setInterval(function () {
            count += step;
            if (count >= target) {
                count = target;
                clearInterval(updateCounter);
            }
            counter.innerText = Math.round(count);
        }, 20);
    });
});