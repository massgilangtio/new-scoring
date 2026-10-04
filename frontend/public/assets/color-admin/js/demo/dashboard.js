/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var dashboardCharts = [];
var dashboardThemeReloadTimer;

var getDashboardCssVar = function(name, fallback) {
	return getCssVar(name) || fallback;
};

var getDashboardRgba = function(rgbVar, opacity, fallbackRgb) {
	return 'rgba(' + getDashboardCssVar(rgbVar, fallbackRgb) + ', ' + opacity + ')';
};

var handleDashboardChartRender = function(chart) {
	dashboardCharts.push(chart);
	chart.render();
};

var handleDashboardChartDestroy = function() {
	for (var i = 0; i < dashboardCharts.length; i++) {
		if (dashboardCharts[i]) {
			dashboardCharts[i].destroy();
		}
	}
	dashboardCharts = [];
	window.donutChart = null;
	window.sparklineCharts = null;
	$('#analyticChart, #donut-chart, #sparkline-unique-visitor, #sparkline-bounce-rate, #sparkline-total-page-views, #sparkline-avg-time-on-site, #sparkline-new-visits, #sparkline-return-visitors').empty();

	if ($('#world-map').length !== 0) {
		try {
			var mapObject = $('#world-map').vectorMap('get', 'mapObject');
			if (mapObject) {
				mapObject.remove();
			}
		} catch (err) {}
		$('#world-map').empty();
	}
};

var handleDashboardChartRestart = function() {
	clearTimeout(dashboardThemeReloadTimer);
	dashboardThemeReloadTimer = setTimeout(function() {
		handleDashboardChartDestroy();
		handleAnalyticChart();
		handleDashboardSparkline();
		handleDonutChart();
		handleVectorMap();
	}, 50);
};

var handleVectorMap = function() {
	"use strict";
	if ($('#world-map').length !== 0) {
		var fillColor = ($('#world-map').attr('data-theme') == 'transparent') ? getDashboardRgba('--bs-white-rgb', .25, app.color.whiteRgb) : getDashboardCssVar('--bs-gray-600', app.color.gray600);
		$('#world-map').empty();
		$('#world-map').vectorMap({
			map: 'world_mill',
			scaleColors: [getDashboardCssVar('--bs-gray-300', app.color.gray300), getDashboardCssVar('--bs-gray-600', app.color.gray600)],
			normalizeFunction: 'polynomial',
			hoverOpacity: 0.5,
			hoverColor: false,
			zoomOnScroll: false,
			markerStyle: {
				initial: {
					fill: getDashboardCssVar('--bs-teal', app.color.teal),
					stroke: 'transparent',
					r: 3
				}
			},
			regionStyle: {
				initial: {
					fill: fillColor,
					"fill-opacity": 1,
					stroke: 'none',
					"stroke-width": 0.4,
					"stroke-opacity": 1
				},
				hover: {
					"fill-opacity": 0.8
				},
				selected: {
					fill: 'yellow'
				},
				selectedHover: { }
			},
			focusOn: {
				x: 0.5,
				y: 0.5,
				scale: 0
			},
			backgroundColor: 'transparent',
			markers: [
				{latLng: [41.90, 12.45], name: 'Vatican City'},
				{latLng: [43.73, 7.41], name: 'Monaco'},
				{latLng: [-0.52, 166.93], name: 'Nauru'},
				{latLng: [-8.51, 179.21], name: 'Tuvalu'},
				{latLng: [43.93, 12.46], name: 'San Marino'},
				{latLng: [47.14, 9.52], name: 'Liechtenstein'},
				{latLng: [7.11, 171.06], name: 'Marshall Islands'},
				{latLng: [17.3, -62.73], name: 'Saint Kitts and Nevis'},
				{latLng: [3.2, 73.22], name: 'Maldives'},
				{latLng: [35.88, 14.5], name: 'Malta'},
				{latLng: [12.05, -61.75], name: 'Grenada'},
				{latLng: [13.16, -61.23], name: 'Saint Vincent and the Grenadines'},
				{latLng: [13.16, -59.55], name: 'Barbados'},
				{latLng: [17.11, -61.85], name: 'Antigua and Barbuda'},
				{latLng: [-4.61, 55.45], name: 'Seychelles'},
				{latLng: [7.35, 134.46], name: 'Palau'},
				{latLng: [42.5, 1.51], name: 'Andorra'},
				{latLng: [14.01, -60.98], name: 'Saint Lucia'},
				{latLng: [6.91, 158.18], name: 'Federated States of Micronesia'},
				{latLng: [1.3, 103.8], name: 'Singapore'},
				{latLng: [1.46, 173.03], name: 'Kiribati'},
				{latLng: [-21.13, -175.2], name: 'Tonga'},
				{latLng: [15.3, -61.38], name: 'Dominica'},
				{latLng: [-20.2, 57.5], name: 'Mauritius'},
				{latLng: [26.02, 50.55], name: 'Bahrain'},
				{latLng: [0.33, 6.73], name: 'S‹o TomŽ and Pr’ncipe'}
			]
		});
	}
};

var handleAnalyticChart = function() {

  function handleGetDate(minusDate) {
    const d = new Date();
    d.setDate(d.getDate() - minusDate);
    return d.getTime(); // ApexCharts uses timestamp
  }

  var analyticChartSeries = [{
		name: 'Unique Visitors',
		data: [
			[handleGetDate(77), 13], [handleGetDate(76), 13], [handleGetDate(75), 6 ],
			[handleGetDate(73), 6 ], [handleGetDate(72), 6 ], [handleGetDate(71), 5 ], [handleGetDate(70), 5 ],
			[handleGetDate(69), 5 ], [handleGetDate(68), 6 ], [handleGetDate(67), 7 ], [handleGetDate(66), 6 ],
			[handleGetDate(65), 9 ], [handleGetDate(64), 9 ], [handleGetDate(63), 8 ], [handleGetDate(62), 10],
			[handleGetDate(61), 10], [handleGetDate(60), 10], [handleGetDate(59), 10], [handleGetDate(58), 9 ],
			[handleGetDate(57), 9 ], [handleGetDate(56), 10], [handleGetDate(55), 9 ], [handleGetDate(54), 9 ],
			[handleGetDate(53), 8 ], [handleGetDate(52), 8 ], [handleGetDate(51), 8 ], [handleGetDate(50), 8 ],
			[handleGetDate(49), 8 ], [handleGetDate(48), 7 ], [handleGetDate(47), 7 ], [handleGetDate(46), 6 ],
			[handleGetDate(45), 6 ], [handleGetDate(44), 6 ], [handleGetDate(43), 6 ], [handleGetDate(42), 5 ],
			[handleGetDate(41), 5 ], [handleGetDate(40), 4 ], [handleGetDate(39), 4 ], [handleGetDate(38), 5 ],
			[handleGetDate(37), 5 ], [handleGetDate(36), 5 ], [handleGetDate(35), 7 ], [handleGetDate(34), 7 ],
			[handleGetDate(33), 7 ], [handleGetDate(32), 10], [handleGetDate(31), 9 ], [handleGetDate(30), 9 ],
			[handleGetDate(29), 10], [handleGetDate(28), 11], [handleGetDate(27), 11], [handleGetDate(26), 8 ],
			[handleGetDate(25), 8 ], [handleGetDate(24), 7 ], [handleGetDate(23), 8 ], [handleGetDate(22), 9 ],
			[handleGetDate(21), 8 ], [handleGetDate(20), 9 ], [handleGetDate(19), 10], [handleGetDate(18), 9 ],
			[handleGetDate(17), 10], [handleGetDate(16), 16], [handleGetDate(15), 17], [handleGetDate(14), 16],
			[handleGetDate(13), 17], [handleGetDate(12), 16], [handleGetDate(11), 15], [handleGetDate(10), 14],
			[handleGetDate(9) , 24], [handleGetDate(8) , 18], [handleGetDate(7) , 15], [handleGetDate(6) , 14],
			[handleGetDate(5) , 16], [handleGetDate(4) , 16], [handleGetDate(3) , 17], [handleGetDate(2) , 7 ],
			[handleGetDate(1) , 7 ], [handleGetDate(0) , 7 ]
		]
	}, {
		name: 'Page Views',
		data: [
			[handleGetDate(77), 14], [handleGetDate(76), 13], [handleGetDate(75), 15],
			[handleGetDate(73), 14], [handleGetDate(72), 13], [handleGetDate(71), 15], [handleGetDate(70), 16],
			[handleGetDate(69), 16], [handleGetDate(68), 14], [handleGetDate(67), 14], [handleGetDate(66), 13],
			[handleGetDate(65), 12], [handleGetDate(64), 13], [handleGetDate(63), 13], [handleGetDate(62), 15],
			[handleGetDate(61), 16], [handleGetDate(60), 16], [handleGetDate(59), 17], [handleGetDate(58), 17],
			[handleGetDate(57), 18], [handleGetDate(56), 15], [handleGetDate(55), 15], [handleGetDate(54), 15],
			[handleGetDate(53), 19], [handleGetDate(52), 19], [handleGetDate(51), 18], [handleGetDate(50), 18],
			[handleGetDate(49), 17], [handleGetDate(48), 16], [handleGetDate(47), 18], [handleGetDate(46), 18],
			[handleGetDate(45), 18], [handleGetDate(44), 16], [handleGetDate(43), 14], [handleGetDate(42), 14],
			[handleGetDate(41), 13], [handleGetDate(40), 14], [handleGetDate(39), 13], [handleGetDate(38), 10],
			[handleGetDate(37), 9 ], [handleGetDate(36), 10], [handleGetDate(35), 11], [handleGetDate(34), 11],
			[handleGetDate(33), 11], [handleGetDate(32), 10], [handleGetDate(31), 9 ], [handleGetDate(30), 10],
			[handleGetDate(29), 13], [handleGetDate(28), 14], [handleGetDate(27), 14], [handleGetDate(26), 13],
			[handleGetDate(25), 12], [handleGetDate(24), 11], [handleGetDate(23), 13], [handleGetDate(22), 13],
			[handleGetDate(21), 13], [handleGetDate(20), 13], [handleGetDate(19), 14], [handleGetDate(18), 13],
			[handleGetDate(17), 13], [handleGetDate(16), 19], [handleGetDate(15), 21], [handleGetDate(14), 22],
			[handleGetDate(13), 25], [handleGetDate(12), 24], [handleGetDate(11), 24], [handleGetDate(10), 22],
			[handleGetDate(9) , 16], [handleGetDate(8) , 15], [handleGetDate(7) , 12], [handleGetDate(6) , 12],
			[handleGetDate(5) , 15], [handleGetDate(4) , 15], [handleGetDate(3) , 15], [handleGetDate(2) , 18],
			[handleGetDate(2) , 18], [handleGetDate(0) , 17]
		]
	}];
	var analyticChartOptions = {
    series: analyticChartSeries,
		colors: [getDashboardCssVar('--bs-dark', app.color.dark), getDashboardCssVar('--bs-primary', app.color.primary)],
		fill: { opacity: .5, type: 'solid' },
		legend: {
			position: 'top',
			floating: true,
			horizontalAlign: 'right',
			offsetY: 30,
			offsetX: 500,
			labels: { colors: getDashboardCssVar('--bs-body-color', app.color.bodyColor) }
		},
		xaxis: {
			type: 'datetime',
			tickAmount: 6,
			labels: { style: { colors: getDashboardCssVar('--bs-body-color', app.color.bodyColor) } }
		},
		yaxis: { labels: { style: { colors: getDashboardCssVar('--bs-body-color', app.color.bodyColor) } } },
		tooltip: { y: { formatter: function (val) { return "$ " + val + " thousands" } } },
		chart: { height: '300px', type: 'area', toolbar: { show: false }, stacked: true, zoom: { enabled: false } },
		plotOptions: { bar: { horizontal: false, columnWidth: '55%', endingShape: 'rounded' } },
		dataLabels: { enabled: false },
		grid: {
			show: true, borderColor: getDashboardCssVar('--bs-border-color', app.color.borderColor),
			xaxis: { lines: { show: true } },
			yaxis: { lines: { show: true } },
			padding: { top: -10, right: 10, bottom: 0, left: 10 }
		},
		stroke: { show: true, width: 2, curve: 'straight' }
	};

  handleDashboardChartRender(new ApexCharts(
    document.querySelector('#analyticChart'),
    analyticChartOptions
  ));
};

var handleDonutChart = function () {
    "use strict";

    if ($('#donut-chart').length !== 0) {
        // Clear previous chart if exists
        if (window.donutChart) {
            window.donutChart.destroy();
        }

        var donutData = [
            { label: "Chrome",  data: 35, color: getDashboardRgba('--bs-primary-rgb', .75, app.color.primaryRgb) },
            { label: "Firefox",  data: 30, color: getDashboardRgba('--bs-primary-rgb', 1, app.color.primaryRgb) },
            { label: "Safari",  data: 15, color: getDashboardRgba('--bs-primary-rgb', .5, app.color.primaryRgb) },
            { label: "Opera",  data: 10, color: getDashboardCssVar('--bs-dark', app.color.dark) },
            { label: "IE",  data: 5, color: getDashboardRgba('--bs-dark-rgb', .75, app.color.darkRgb) }
        ];

        // Extract labels, series data, and colors
        var labels = donutData.map(function(item) { return item.label; });
        var series = donutData.map(function(item) { return item.data; });
        var colors = donutData.map(function(item) { return item.color; });

        var options = {
            series: series,
            chart: {
                type: 'donut',
                height: 300
            },
            colors: colors,
            labels: labels,
            plotOptions: {
                pie: {
                    donut: {
                        size: '45%', // Equivalent to innerRadius: 0.5
                        background: 'transparent'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.labels[opts.seriesIndex] + ': ' + Math.round(val) + '%';
                },
                dropShadow: {
                    enabled: false
                }
            },
            stroke: {
                width: 2,
                colors: [getDashboardCssVar('--bs-component-bg', app.color.componentBg)]
            },
            legend: {
                position: 'bottom',
                show: true
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2) + '%';
                    }
                }
            }
        };

        window.donutChart = new ApexCharts(document.querySelector("#donut-chart"), options);
        handleDashboardChartRender(window.donutChart);
    }
};

var handleDashboardSparkline = function() {
    "use strict";

    // Common data for all sparklines
    var sparklineData = [50, 30, 45, 40, 50, 20, 35, 40, 50, 70, 90, 40];

    // Function to create sparkline options
    function createSparklineOptions(color, elementId) {
        var element = document.getElementById(elementId);
        if (!element) return null;

        var width = element.clientWidth || 200;
        width = width > 200 ? 200 : width;

        return {
            dashboardElementId: elementId,
            series: [{
                name: elementId.replace('sparkline-', '').replace('-', ' '),
                data: sparklineData
            }],
            chart: {
                type: 'line',
                height: 23,
                width: width,
                sparkline: {
                    enabled: true
                },
                animations: {
                    enabled: false
                }
            },
            stroke: {
                width: 2,
                curve: 'straight',
                colors: [color]
            },
            colors: [color],
            markers: {
                size: 0,
                colors: [color],
                strokeColors: color,
                hover: {
                    size: 5
                }
            },
            tooltip: {
                fixed: {
                    enabled: false
                },
                x: {
                    show: false
                },
                y: {
                    title: {
                        formatter: function (seriesName) {
                            return '';
                        }
                    }
                },
                marker: {
                    show: true
                }
            },
            xaxis: {
                type: 'category',
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                labels: {
                    show: false
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                min: 0,
                max: 100,
                labels: {
                    show: false
                }
            },
            grid: {
                show: false,
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            }
        };
    }

    // Create sparkline charts
    var sparklineCharts = {};

    sparklineCharts.uniqueVisitor = new ApexCharts(
        document.querySelector("#sparkline-unique-visitor"),
        createSparklineOptions(getDashboardCssVar('--bs-red', app.color.red), 'sparkline-unique-visitor')
    );

    sparklineCharts.bounceRate = new ApexCharts(
        document.querySelector("#sparkline-bounce-rate"),
        createSparklineOptions(getDashboardCssVar('--bs-orange', app.color.orange), 'sparkline-bounce-rate')
    );

    sparklineCharts.totalPageViews = new ApexCharts(
        document.querySelector("#sparkline-total-page-views"),
        createSparklineOptions(getDashboardCssVar('--bs-green', app.color.green), 'sparkline-total-page-views')
    );

    sparklineCharts.avgTimeOnSite = new ApexCharts(
        document.querySelector("#sparkline-avg-time-on-site"),
        createSparklineOptions(getDashboardCssVar('--bs-blue', app.color.blue), 'sparkline-avg-time-on-site')
    );

    sparklineCharts.newVisits = new ApexCharts(
        document.querySelector("#sparkline-new-visits"),
        createSparklineOptions(getDashboardCssVar('--bs-gray', app.color.gray), 'sparkline-new-visits')
    );

    sparklineCharts.returnVisitors = new ApexCharts(
        document.querySelector("#sparkline-return-visitors"),
        createSparklineOptions(getDashboardCssVar('--bs-black', app.color.black), 'sparkline-return-visitors')
    );

    // Render all charts
    Object.values(sparklineCharts).forEach(function(chart) {
        if (chart) handleDashboardChartRender(chart);
    });

    // Store charts for resizing
    window.sparklineCharts = sparklineCharts;

    // Resize handler
    function handleResize() {
        if (window.sparklineCharts) {
            Object.values(window.sparklineCharts).forEach(function(chart) {
                if (chart) {
                    chart.updateOptions({
                        chart: {
                            width: (document.getElementById(chart.opts.dashboardElementId) && document.getElementById(chart.opts.dashboardElementId).clientWidth) || 200
                        }
                    });
                }
            });
        }
    }

    // Add resize listener
    var resizeTimer;
    $(window).off('resize.dashboard').on('resize.dashboard', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
    });
};

var handleDashboardDatepicker = function() {
	"use strict";
	$('#datepicker-inline').datepicker({
		todayHighlight: true
	});
};

var handleDashboardTodolist = function() {
	"use strict";
	$('[data-change=todolist]').click(function() {
		if ($(this).is(':checked')) {
			$(this).closest('.todolist-item').addClass('active');
		} else {
			$(this).closest('.todolist-item').removeClass('active');
		}
	});
};

var handleNotification = function() {
	//$('.toast').toast('show');
};

var Dashboard = function () {
	"use strict";
	return {
		//main function
		init: function () {
			handleNotification();
			handleAnalyticChart();
			handleDashboardSparkline();
			handleDonutChart();
			handleDashboardTodolist();
			handleVectorMap();
			handleDashboardDatepicker();
		}
	};
}();

$(document).ready(function() {
	Dashboard.init();

	$(document).on(app.darkMode.eventName, function() {
		handleDashboardChartRestart();
	});
});