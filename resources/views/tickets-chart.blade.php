<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Tickets Chart</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <style>
    #container {
        height: 560px
    }
    </style>
</head>

<body>
    <div id="container"></div>
    <script>
    const payload = @json($payload);

    Highcharts.chart('container', {
        title: {
            text: 'Tickets Created vs Resolved (Daily)'
        },
        xAxis: {
            categories: payload.categories,
            title: {
                text: payload.xTitle
            }
        },
        yAxis: [{
            title: {
                text: payload.yTitle
            },
            allowDecimals: false
        }, {
            title: {
                text: 'Hours'
            },
            opposite: true
        }],
        tooltip: {
            shared: true
        },
        plotOptions: {
            series: {
                marker: {
                    enabled: false
                }
            }
        },
        series: payload.series,
        credits: {
            enabled: false
        }
    });
    </script>
</body>

</html>