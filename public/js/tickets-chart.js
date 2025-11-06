// Wait for DOM and Highcharts to be ready
function initTicketChart() {
    try {
        // Get current week data from the page
        const currentWeekData = window.ticketChartData;
        const container = document.getElementById('container');

        if (!currentWeekData || !currentWeekData.dailyData) {
            container.innerHTML = '<div class="alert alert-danger text-center p-3">Error: No chart data available.</div>';
            return;
        }

        // Check if Highcharts is loaded
        if (typeof Highcharts === 'undefined') {
            container.innerHTML = '<div class="alert alert-danger text-center p-3">Error: Highcharts library failed to load. Please refresh the page.</div>';
            return;
        }

        // Prepare daily data
        const dailyData = currentWeekData.dailyData;
        const isFiltered = window.isFiltered || false;
        
        // Format categories based on whether it's filtered or not
        let categories;
        if (isFiltered) {
            // For filtered results, show dates (format: "Jan 1" or "Jan 1<br>2025")
            categories = dailyData.map(day => {
                // Use dateFull to format properly
                if (day.dateFull) {
                    const dateObj = new Date(day.dateFull);
                    const month = dateObj.toLocaleDateString('en-US', { month: 'short' });
                    const dayNum = dateObj.getDate();
                    return month + ' ' + dayNum;
                }
                return day.date; // Fallback to existing date format
            });
        } else {
            // For weekly view, show day name + date
            categories = dailyData.map(day => day.day + '<br>' + day.date);
        }
        
        const createdData = dailyData.map(day => day.created);
        const resolvedData = dailyData.map(day => day.resolved);
        const totalData = dailyData.map(day => day.totalTickets);

        // Render the chart
        Highcharts.chart('container', {
            chart: {
                width: null,
                spacingBottom: 30,
                spacingRight: 20,
                spacingLeft: 60,
                events: {
                    load: function() {
                        // Add watermark in bottom-right corner
                        const watermarkText = 'developed by Duan Truong from Service Team';
                        const textElement = this.renderer.text(
                            watermarkText,
                            this.chartWidth - 20,
                            this.chartHeight - 10
                        )
                        .attr({
                            align: 'right'
                        })
                        .css({
                            color: '#999999',
                            fontSize: '11px',
                            opacity: 0.6,
                            fontStyle: 'italic'
                        })
                        .add();
                    }
                }
            },
            title: {
                text: 'Ticket Movement for Week: ' + currentWeekData.weekLabel
            },
            xAxis: {
                categories: categories,
                title: {
                    text: isFiltered ? 'Date' : 'Days of Week (Monday - Sunday)'
                },
                labels: {
                    rotation: isFiltered ? -45 : 0,
                    style: {
                        fontSize: '11px'
                    },
                    step: isFiltered ? Math.ceil(categories.length / 20) : 1 // Show every nth label if too many dates
                }
            },
            yAxis: {
                title: {
                    text: 'Number of Tickets'
                },
                allowDecimals: false,
                min: 0
            },
            tooltip: {
                shared: true,
                pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y}</b><br/>'
            },
            plotOptions: {
                column: {
                    grouping: true,
                    dataLabels: {
                        enabled: false
                    }
                },
                line: {
                    marker: {
                        enabled: true,
                        radius: 4
                    },
                    zIndex: 5
                }
            },
            series: [
                {
                    name: 'Tickets Created',
                    type: 'column',
                    data: createdData,
                    color: '#0d6efd'
                },
                {
                    name: 'Tickets Resolved',
                    type: 'column',
                    data: resolvedData,
                    color: '#dc3545'
                },
                {
                    name: 'Total Tickets',
                    type: 'line',
                    data: totalData,
                    color: '#198754',
                    marker: {
                        enabled: true,
                        radius: 5
                    }
                }
            ],
            credits: {
                enabled: false
            }
        }, function (chart) {
            if (!chart || !chart.series || chart.series.length === 0) {
                console.error('Chart rendered but no series found');
                container.innerHTML = '<div class="alert alert-warning text-center p-3">Chart rendered but no data to display.</div>';
            } else {
                console.log('Chart rendered successfully');
            }
        });

    } catch (error) {
        console.error('Error rendering chart:', error);
        const container = document.getElementById('container');
        if (container) {
            container.innerHTML = '<div class="alert alert-danger text-center p-3">Error rendering chart: ' + error.message + '</div>';
        }
    }
}

// Wait for Highcharts to load, then initialize chart
function initializeTicketChart() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Highcharts !== 'undefined') {
                initTicketChart();
            } else {
                setTimeout(function () {
                    if (typeof Highcharts !== 'undefined') {
                        initTicketChart();
                    } else {
                        const container = document.getElementById('container');
                        if (container) {
                            container.innerHTML = '<div class="alert alert-danger text-center p-3">Error: Highcharts library failed to load. Please refresh the page.</div>';
                        }
                    }
                }, 500);
            }
        });
    } else {
        if (typeof Highcharts !== 'undefined') {
            initTicketChart();
        } else {
            const checkHighcharts = setInterval(function () {
                if (typeof Highcharts !== 'undefined') {
                    clearInterval(checkHighcharts);
                    initTicketChart();
                }
            }, 100);

            setTimeout(function () {
                clearInterval(checkHighcharts);
                if (typeof Highcharts === 'undefined') {
                    const container = document.getElementById('container');
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger text-center p-3">Error: Highcharts library failed to load. Please refresh the page.</div>';
                    }
                }
            }, 5000);
        }
    }
}

// Auto-initialize when script loads
initializeTicketChart();

