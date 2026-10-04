/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var dashboardV3Charts = [];
var dashboardV3ThemeObserver;
var dashboardV3ThemeReloadTimer;

var getDashboardV3ThemeElement = function(element) {
	return (element && element.closest('[data-bs-theme]')) || document.body;
};

var getDashboardV3Theme = function(element) {
	var themeElement = getDashboardV3ThemeElement(element);
	var theme = themeElement.getAttribute('data-bs-theme') || document.documentElement.getAttribute('data-bs-theme') || document.body.getAttribute('data-bs-theme');

	return (theme === 'dark') ? 'dark' : 'light';
};

var getDashboardV3CssVar = function(element, name, fallback) {
	var value = getCssVar(name, element);

	return value || getCssVar(name) || fallback;
};

var getDashboardV3Rgba = function(element, name, opacity, fallback) {
	var value = getDashboardV3CssVar(element, name, fallback);

	return 'rgba(' + value + ', ' + opacity + ')';
};

var handleDashboardV3ChartRender = function(chart) {
	dashboardV3Charts.push(chart);
	chart.render();
};

var handleDashboardV3ChartDestroy = function() {
	for (var i = 0; i < dashboardV3Charts.length; i++) {
		if (dashboardV3Charts[i]) {
			dashboardV3Charts[i].destroy();
		}
	}
	dashboardV3Charts = [];
	$('#total-sales-sparkline, #conversion-rate-sparkline, #store-session-sparkline, #visitorLineChart').empty();

	if ($('#visitorMap').length !== 0) {
		try {
			var mapObject = $('#visitorMap').vectorMap('get', 'mapObject');
			if (mapObject) {
				mapObject.remove();
			}
		} catch (err) {}
		$('#visitorMap').empty();
	}
};

var handleDashboardV3ThemeReload = function() {
	clearTimeout(dashboardV3ThemeReloadTimer);
	dashboardV3ThemeReloadTimer = setTimeout(function() {
		DashboardV3.restartCharts();
	}, 50);
};

var handleTotalSalesSparkline = function() {
	var chartElement = document.querySelector('#total-sales-sparkline');

	if (!chartElement) {
		return;
	}

	var options = {
		chart: {
			type: 'line',
			width: 200,
			height: 36,
			sparkline: {
				enabled: true
			},
			stacked: true
		},
		stroke: {
			curve: 'smooth',
			width: 3
		},
		fill: {
			type: 'gradient',
			gradient: {
				opacityFrom: 1,
				opacityTo: 1,
				colorStops: [{
					offset: 0,
					color: app.color.blue,
					opacity: 1
				},
				{
					offset: 100,
					color: app.color.indigo,
					opacity: 1
				}]
			},
		},
		series: [{
			data: [9452.37, 11018.87, 7296.37, 6274.29, 7924.05, 6581.34, 12918.14]
		}],
		tooltip: {
			theme: getDashboardV3Theme(chartElement),
			fixed: {
				enabled: false
			},
			x: {
				show: false
			},
			y: {
				title: {
					formatter: function (seriesName) {
						return ''
					}
				},
				formatter: (value) => { return '$'+ convertNumberWithCommas(value) },
			},
			marker: {
				show: false
			}
		},
		responsive: [{
			breakpoint: 3000,
			options: {
				chart: {
					width: 130
				}
			}
		},{
			breakpoint: 1300,
			options: {
				chart: {
					width: 100
				}
			}
		},{
			breakpoint: 1200,
			options: {
				chart: {
					width: 200
				}
			}
		},{
			breakpoint: 576,
			options: {
				chart: {
					width: 180
				}
			}
		},{
			breakpoint: 400,
			options: {
				chart: {
					width: 120
				}
			}
		}]
	};
	handleDashboardV3ChartRender(new ApexCharts(chartElement, options));
};

var handleConversionRateSparkline = function() {
	var chartElement = document.querySelector('#conversion-rate-sparkline');

	if (!chartElement) {
		return;
	}

	var options = {
		chart: {
			type: 'line',
			width: 160,
			height: 28,
			sparkline: {
				enabled: true
			}
		},
		stroke: {
			curve: 'smooth',
			width: 3
		},
		fill: {
			type: 'gradient',
			gradient: {
				opacityFrom: 1,
				opacityTo: 1,
				colorStops: [{
					offset: 0,
					color: app.color.red,
					opacity: 1
				},
				{
					offset: 50,
					color: app.color.orange,
					opacity: 1
				},
				{
					offset: 100,
					color: app.color.lime,
					opacity: 1
				}]
			},
		},
		series: [{
			data: [2.68, 2.93, 2.04, 1.61, 1.88, 1.62, 2.80]
		}],
		labels: ['Jun 23', 'Jun 24', 'Jun 25', 'Jun 26', 'Jun 27', 'Jun 28', 'Jun 29'],
		xaxis: {
			crosshairs: {
				width: 1
			},
		},
		tooltip: {
			theme: getDashboardV3Theme(chartElement),
			fixed: {
				enabled: false
			},
			x: {
				show: false
			},
			y: {
				title: {
					formatter: function (seriesName) {
						return ''
					}
				},
				formatter: (value) => { return value + '%' },
			},
			marker: {
				show: false
			}
		},
		responsive: [{
			breakpoint: 3000,
			options: {
				chart: {
					width: 120
				}
			}
		},{
			breakpoint: 1300,
			options: {
				chart: {
					width: 100
				}
			}
		},{
			breakpoint: 1200,
			options: {
				chart: {
					width: 160
				}
			}
		},{
			breakpoint: 900,
			options: {
				chart: {
					width: 120
				}
			}
		},{
			breakpoint: 576,
			options: {
				chart: {
					width: 180
				}
			}
		},{
			breakpoint: 400,
			options: {
				chart: {
					width: 120
				}
			}
		}]
	}
	handleDashboardV3ChartRender(new ApexCharts(chartElement, options));
};

var handleStoreSessionSparkline = function() {
	var chartElement = document.querySelector('#store-session-sparkline');

	if (!chartElement) {
		return;
	}

	var options = {
		chart: {
			type: 'line',
			width: 160,
			height: 28,
			sparkline: {
				enabled: true
			},
			stacked: false
		},
		stroke: {
			curve: 'smooth',
			width: 3
		},
		fill: {
			type: 'gradient',
			gradient: {
				opacityFrom: 1,
				opacityTo: 1,
				colorStops: [{
					offset: 0,
					color: app.color.teal,
					opacity: 1
				},
				{
					offset: 50,
					color: app.color.blue,
					opacity: 1
				},
				{
					offset: 100,
					color: app.color.cyan,
					opacity: 1
				}]
			},
		},
		series: [{
			data: [10812, 11393, 7311, 6834, 9612, 11200, 13557]
		}],
		labels: ['Jun 23', 'Jun 24', 'Jun 25', 'Jun 26', 'Jun 27', 'Jun 28', 'Jun 29'],
		xaxis: {
			crosshairs: {
				width: 1
			},
		},
		tooltip: {
			theme: getDashboardV3Theme(chartElement),
			fixed: {
				enabled: false
			},
			x: {
				show: false
			},
			y: {
				title: {
					formatter: function (seriesName) {
						return ''
					}
				},
				formatter: (value) => { return convertNumberWithCommas(value) },
			},
			marker: {
				show: false
			}
		},
		responsive: [{
			breakpoint: 3000,
			options: {
				chart: {
					width: 120
				}
			}
		},{
			breakpoint: 1300,
			options: {
				chart: {
					width: 100
				}
			}
		},{
			breakpoint: 1200,
			options: {
				chart: {
					width: 160
				}
			}
		},{
			breakpoint: 900,
			options: {
				chart: {
					width: 120
				}
			}
		},{
			breakpoint: 576,
			options: {
				chart: {
					width: 180
				}
			}
		},{
			breakpoint: 400,
			options: {
				chart: {
					width: 120
				}
			}
		}]
	};
	handleDashboardV3ChartRender(new ApexCharts(chartElement, options));
};

var handleVisitorsAreaChart = function() {
	var chartElement = document.querySelector('#visitorLineChart');

	if (!chartElement) {
		return;
	}

  function handleGetDate(minusDate) {
    const d = new Date();
    d.setDate(d.getDate() - minusDate);
    return d.getTime(); // ApexCharts uses timestamp
  }

  var visitorChartSeries = [{
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
	var bodyColor = getDashboardV3CssVar(chartElement, '--bs-body-color', '#ffffff');
	var borderColor = getDashboardV3CssVar(chartElement, '--bs-border-color', 'rgba(255,255,255, .15)');
	var visitorChartOptions = {
    series: visitorChartSeries,
		colors: [getDashboardV3CssVar(chartElement, '--bs-teal', '#00acac'), getDashboardV3CssVar(chartElement, '--bs-blue', '#348fe2')],
		fill: { opacity: .75, type: 'solid' },
		legend: {
			position: 'top',
			horizontalAlign: 'right',
			offsetY: 15,
			offsetX: 500,
			labels: { colors: bodyColor }
		},
		xaxis: {
			type: 'datetime',
			tickAmount: 6,
			labels: { style: { colors: bodyColor } }
		},
		yaxis: { labels: { style: { colors: bodyColor } } },
		tooltip: {
			theme: getDashboardV3Theme(chartElement),
			y: { formatter: function (val) { return "$ " + val + " thousands" } }
		},
		chart: { height: '100%', type: 'area', toolbar: { show: false }, stacked: true, zoom: { enabled: false } },
		plotOptions: { bar: { horizontal: false, columnWidth: '55%', endingShape: 'rounded' } },
		dataLabels: { enabled: false },
		grid: {
			show: true, borderColor: borderColor,
			xaxis: { lines: { show: true } },
			yaxis: { lines: { show: true } },
			padding: { top: -40, right: 3, bottom: 0, left: 10 }
		},
		stroke: {  show: false, curve: 'straight' }
	};

  handleDashboardV3ChartRender(new ApexCharts(chartElement, visitorChartOptions));
};

var handleVisitorsMap = function() {
	var mapElement = document.querySelector('#visitorMap');

	if (!mapElement) {
		return;
	}

	var fillColor = (getDashboardV3Theme(mapElement) === 'dark') ? getDashboardV3Rgba(mapElement, '--bs-component-color-rgb', .25, app.color.componentColorRgb) : getDashboardV3CssVar(mapElement, '--bs-component-border-color', '#dee2e6');
	var options = {
		map: 'world_mill',
		scaleColors: [getDashboardV3CssVar(mapElement, '--bs-component-color', app.color.black), getDashboardV3CssVar(mapElement, '--bs-component-color', app.color.black)],
		container: $('#visitorMap'),
		normalizeFunction: 'linear',
		hoverOpacity: 0.5,
		hoverColor: false,
		zoomOnScroll: false,
		zoomButtons: false,
		markerStyle: {
			initial: {
				fill: getDashboardV3CssVar(mapElement, '--bs-component-color', app.color.black),
				stroke: 'transparent',
				r: 3
			}
		},
		regions: [{
			attribute: 'fill'
		}],
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
			}
		},
		series: {
			regions: [{
				values: {
					IN: getDashboardV3CssVar(mapElement, '--bs-teal', app.color.teal),
					US: getDashboardV3CssVar(mapElement, '--bs-teal', app.color.teal),
					MN: getDashboardV3CssVar(mapElement, '--bs-teal', app.color.teal),
					RU: getDashboardV3CssVar(mapElement, '--bs-teal', app.color.teal)
				}
			}]
		},
		focusOn: {
			x: 0.7,
			y: 0.5,
			scale: 1
		},
		backgroundColor: 'transparent'
	};
	$('#visitorMap').vectorMap(options);
};

var handleDateRangeFilter = function() {
	$('#daterange-filter span').html(moment().subtract(7, 'days').format('D MMMM YYYY') + ' - ' + moment().format('D MMMM YYYY'));
	$('#daterange-prev-date').html(moment().subtract(15, 'days').format('D MMMM') + ' - ' + moment().subtract(8, 'days').format('D MMMM YYYY'));

	$('#daterange-filter').daterangepicker({
		format: 'MM/DD/YYYY',
		startDate: moment().subtract(7, 'days'),
		endDate: moment(),
		minDate: '01/06/2023',
		maxDate: '07/06/2023',
		dateLimit: { days: 60 },
		showDropdowns: true,
		showWeekNumbers: true,
		timePicker: false,
		timePickerIncrement: 1,
		timePicker12Hour: true,
		ranges: {
			'Today': [moment(), moment()],
			'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Last 7 Days': [moment().subtract(6, 'days'), moment()],
			'Last 30 Days': [moment().subtract(29, 'days'), moment()],
			'This Month': [moment().startOf('month'), moment().endOf('month')],
			'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
		},
		opens: 'right',
		drops: 'down',
		buttonClasses: ['btn', 'btn-sm'],
		applyClass: 'btn-primary',
		cancelClass: 'btn-default',
		separator: ' to ',
		locale: {
			applyLabel: 'Submit',
			cancelLabel: 'Cancel',
			fromLabel: 'From',
			toLabel: 'To',
			customRangeLabel: 'Custom',
			daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
			monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			firstDay: 1
		}
	}, function(start, end, label) {
		$('#daterange-filter span').html(start.format('D MMMM YYYY') + ' - ' + end.format('D MMMM YYYY'));

		var gap = end.diff(start, 'days');
		$('#daterange-prev-date').html(moment(start).subtract(gap, 'days').format('D MMMM') + ' - ' + moment(start).subtract(1, 'days').format('D MMMM YYYY'));
	});
};

var DashboardV3 = function () {
	"use strict";
	return {
		//main function
		init: function () {
			handleTotalSalesSparkline();
			handleConversionRateSparkline();
			handleStoreSessionSparkline();
			handleVisitorsMap();
			handleDateRangeFilter();

			handleVisitorsAreaChart();
		},
		restartCharts: function() {
			handleDashboardV3ChartDestroy();
			handleTotalSalesSparkline();
			handleConversionRateSparkline();
			handleStoreSessionSparkline();
			handleVisitorsMap();
			handleVisitorsAreaChart();
		}
	};
}();

var handleDashboardV3ThemeWatch = function() {
	if (dashboardV3ThemeObserver) {
		return;
	}

	dashboardV3ThemeObserver = new MutationObserver(function(mutations) {
		for (var i = 0; i < mutations.length; i++) {
			if (mutations[i].attributeName === 'data-bs-theme') {
				handleDashboardV3ThemeReload();
				break;
			}
		}
	});
	dashboardV3ThemeObserver.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['data-bs-theme'] });
};

$(document).ready(function() {
	DashboardV3.init();
	handleDashboardV3ThemeWatch();

	$(document).on(app.darkMode.eventName, function() {
		handleDashboardV3ThemeReload();
	});
});
