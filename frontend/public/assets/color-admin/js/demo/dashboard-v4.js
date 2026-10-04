/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var getDashboardV4CssVar = function(element, name, fallback) {
	return getCssVar(name, element) || getCssVar(name) || fallback;
};

var getDashboardV4Rgba = function(element, rgbVar, opacity, fallbackRgb) {
	return 'rgba(' + getDashboardV4CssVar(element, rgbVar, fallbackRgb) + ', ' + opacity + ')';
};

var isDashboardV4TransparentMode = function(element) {
	return element && element.getAttribute('data-mode') === 'transparent';
};

var renderDashboardV4Chart = function(target, options) {
	var chartElement = (typeof target === 'string') ? document.querySelector(target) : target;

	if (!chartElement) {
		return;
	}

	var chart = new ApexCharts(chartElement, options);
	chart.render();
};

var generateDashboardV4HeatmapData = function(day, max) {
	var data = [];

	for (var i = 0; i < 24; i++) {
		if (i % 2 === 0) {
			data.push({
				x: i + ':00',
				y: Math.floor(Math.random() * max)
			});
		}
	}

	return { name: day, data: data };
};

var handleDashboardV4DateRangeFilter = function() {
	var dateRangeFilter = $('#daterange-filter');

	if (!dateRangeFilter.length) {
		return;
	}

	var start = moment().subtract(7, 'days');
	var end = moment();

	var updateLabel = function(start, end) {
		dateRangeFilter.find('span').html(start.format('D MMMM YYYY') + ' - ' + end.format('D MMMM YYYY'));
	};

	updateLabel(start, end);

	dateRangeFilter.on('click', function(e) {
		e.preventDefault();
	});

	dateRangeFilter.daterangepicker({
		startDate: start,
		endDate: end,
		maxSpan: { days: 60 },
		showDropdowns: true,
		parentEl: 'body',
		ranges: {
			'Today': [moment(), moment()],
			'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Last 7 Days': [moment().subtract(6, 'days'), moment()],
			'Last 30 Days': [moment().subtract(29, 'days'), moment()],
			'This Month': [moment().startOf('month'), moment().endOf('month')],
			'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
		},
		opens: 'left',
		drops: 'down',
		buttonClasses: ['btn', 'btn-sm'],
		applyButtonClasses: 'btn-primary',
		cancelButtonClasses: 'btn-default',
		locale: {
			applyLabel: 'Apply',
			cancelLabel: 'Cancel',
			customRangeLabel: 'Custom',
			daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
			monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			firstDay: 1
		}
	}, function(start, end) {
		updateLabel(start, end);
	});

	var picker = dateRangeFilter.data('daterangepicker');

	if (picker && picker.container && !picker.container.parent().length) {
		picker.container.appendTo('body');
	}
};

var handleDashboardV4RevenueChart = function() {
	var chartElement = document.querySelector('#revenueChart');

	if (!chartElement) {
		return;
	}

	var chartColor = getDashboardV4CssVar(chartElement, '--bs-component-color', '#ffffff');
	var chartGridColor = getDashboardV4Rgba(chartElement, '--bs-component-color-rgb', .1, '255, 255, 255');
	var chartSeriesColors = isDashboardV4TransparentMode(chartElement) ? [chartColor, getDashboardV4Rgba(chartElement, '--bs-component-color-rgb', .5, '255, 255, 255')] : [getCssVar('--bs-primary'), getCssVar('--bs-gray-400')];

	renderDashboardV4Chart(chartElement, {
		chart: {
			type: 'area',
			height: '100%',
			toolbar: { show: false },
			zoom: { enabled: false },
			animations: { easing: 'easeinout', speed: 600 }
		},
		series: [{
			name: 'Revenue',
			data: [15, 40, 58, 142, 55, 48, 60, 98, 115, 88, 102, 35, 70, 95, 78, 110, 130, 118, 92]
		}, {
			name: 'Profit',
			data: [20, 22, 38, 15, 22, 28, 35, 48, 62, 40, 50, 15, 12, 28, 18, 40, 58, 45, 60]
		}],
		stroke: {
			width: 2
		},
		colors: chartSeriesColors,
		dataLabels: {
			enabled: false
		},
		fill: {
			type: 'solid',
			opacity: 0.25
		},
		grid: {
			borderColor: chartGridColor,
			strokeDashArray: 4,
			padding: {
				top: 0,
				right: 10,
				bottom: 0,
				left: 10
			}
		},
		legend: {
			show: false
		},
		xaxis: {
			categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed'],
			tickAmount: 5,
			axisBorder: { show: false },
			axisTicks: { show: false },
			labels: {
				style: { colors: chartColor, fontSize: '12px' }
			}
		},
		yaxis: {
			tickAmount: 4,
			labels: {
				style: { colors: chartColor }
			}
		},
		tooltip: {
			shared: true,
			intersect: false
		},
		markers: {
			size: 0
		}
	});
};

var handleDashboardV4RevenueBreakdownChart = function() {
	var chartElement = document.querySelector('#revenueBreakdownChart');

	if (!chartElement) {
		return;
	}

	var chartColor = getDashboardV4CssVar(chartElement, '--bs-component-color', '#212529');
	var chartMutedColor = getDashboardV4Rgba(chartElement, '--bs-component-color-rgb', .5, '33, 37, 41');
	var chartAccentColor = isDashboardV4TransparentMode(chartElement) ? chartColor : getCssVar('--bs-primary');

	renderDashboardV4Chart(chartElement, {
		chart: {
			type: 'donut',
			height: 140
		},
		labels: ['Subscriptions', 'One-time', 'Services'],
		series: [28430, 49020, 19490],
		colors: [chartAccentColor, chartColor, chartMutedColor],
		stroke: {
			width: 0
		},
		plotOptions: {
			pie: {
				donut: {
					size: '70%',
					labels: {
						show: true,
						name: {
							show: true,
							fontSize: '12px',
							fontWeight: 600,
							color: chartMutedColor,
							offsetY: -5
						},
						value: {
							show: true,
							fontSize: '16px',
							fontWeight: 700,
							color: chartColor,
							offsetY: 5,
							formatter: function(val) {
								return '$' + val.toLocaleString();
							}
						},
						total: {
							show: true,
							label: 'Total Revenue',
							fontSize: '12px',
							fontWeight: 700,
							color: chartMutedColor,
							formatter: function() {
								return '$96,940';
							}
						}
					}
				}
			}
		},
		dataLabels: {
			enabled: false
		},
		legend: {
			show: false
		}
	});
};

var handleDashboardV4CampaignBarChart = function() {
	var chartElement = document.querySelector('#campaignBarChart');

	if (!chartElement) {
		return;
	}

	var chartColor = getDashboardV4CssVar(chartElement, '--bs-component-color', '#6c757d');
	var chartGridColor = getDashboardV4CssVar(chartElement, '--bs-component-border-color', 'rgba(0, 0, 0, .05)');
	var chartAccentColor = isDashboardV4TransparentMode(chartElement) ? chartColor : getCssVar('--bs-primary');

	renderDashboardV4Chart(chartElement, {
		chart: {
			type: 'bar',
			height: '100%',
			toolbar: { show: false }
		},
		series: [{
			name: 'Spend',
			data: [3200, 4100, 3800, 4500, 5200, 6100, 5500, 8250, 6700, 4600, 7320, 6100]
		}],
		labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		colors: [chartAccentColor],
		plotOptions: {
			bar: {
				borderRadius: 6,
				columnWidth: '45%'
			}
		},
		dataLabels: {
			enabled: false
		},
		xaxis: {
			labels: {
				style: {
					colors: chartColor,
					fontSize: '12px'
				}
			},
			axisBorder: { show: false },
			axisTicks: { show: false }
		},
		yaxis: {
			labels: {
				style: { colors: chartColor },
				formatter: function(val) {
					return '$' + (val / 1000) + 'k';
				}
			}
		},
		grid: {
			borderColor: chartGridColor,
			strokeDashArray: 4
		}
	});
};

var handleDashboardV4CustomerTrendChart = function() {
	var chartElement = document.querySelector('#customer-trend-chart');

	if (!chartElement) {
		return;
	}

	var chartColor = getDashboardV4CssVar(chartElement, '--bs-component-color', '#ffffff');
	var chartAccentColor = isDashboardV4TransparentMode(chartElement) ? getDashboardV4CssVar(chartElement, '--bs-component-color', '#ffffff') : getCssVar('--bs-primary');
	var chartGridColor = getDashboardV4Rgba(chartElement, '--bs-component-color-rgb', .1, '255, 255, 255');
	
	renderDashboardV4Chart(chartElement, {
		chart: {
			type: 'line',
			height: '100%',
			toolbar: { show: false },
			sparkline: { enabled: false },
			zoom: { enabled: false },
			pan: { enabled: false }
		},
		grid: {
			borderColor: chartGridColor,
			strokeDashArray: 4,
			padding: {
				top: 0,
				right: 10,
				bottom: 0,
				left: 10
			}
		},
		stroke: {
			width: 2
		},
		series: [{
			name: 'Customers',
			data: [8200, 8300, 8450, 8600, 8800, 8950, 9103]
		}],
		xaxis: {
			labels: {
				style: { colors: chartColor, fontSize: '12px' }
			}
		},
		yaxis: {
			labels: {
				style: { colors: chartColor }
			}
		},
		colors: [chartAccentColor],
		tooltip: {
			enabled: true,
			y: {
				formatter: function(val) {
					return val.toLocaleString() + ' users';
				}
			}
		}
	});
};

var handleDashboardV4WorldMap = function() {
	var worldMap = $('#worldMap');

	if (!worldMap.length) {
		return;
	}

	var customerDensity = {
		US: 39,
		DE: 26,
		FR: 26,
		IT: 26,
		ES: 26,
		CN: 35,
		JP: 35,
		SG: 35,
		AU: 35
	};

	worldMap.vectorMap({
		map: 'world_mill',
		backgroundColor: 'transparent',
		zoomOnScroll: false,
		regionStyle: {
			initial: {
				fill: ($(worldMap).attr('data-mode') == 'transparent') ? getCssVar('--bs-component-bg') : getCssVar('--bs-gray-500'),
				stroke: 'none'
			},
			hover: {
				fillOpacity: 0.7
			}
		},
		series: {
			regions: [{
				values: customerDensity,
				scale: ($(worldMap).attr('data-mode') == 'transparent') ? ['#ffffff', '#f2f2f2'] : ['#cfe2ff', '#0d6efd'],
				normalizeFunction: 'polynomial'
			}]
		},
		onRegionTipShow: function(e, el, code) {
			if (customerDensity[code]) {
				el.html(el.html() + ' · ' + customerDensity[code] + '%');
			}
		}
	});
};

var handleDashboardV4SupportHeatmap = function() {
	renderDashboardV4Chart('#supportHeatmap', {
		chart: {
			type: 'heatmap',
			height: '115%',
			toolbar: { show: false },
			offsetY: -30
		},
		series: [
			generateDashboardV4HeatmapData('Mon', 10),
			generateDashboardV4HeatmapData('Tue', 8),
			generateDashboardV4HeatmapData('Wed', 12),
			generateDashboardV4HeatmapData('Thu', 6),
			generateDashboardV4HeatmapData('Fri', 5),
			generateDashboardV4HeatmapData('Sat', 4),
			generateDashboardV4HeatmapData('Sun', 3)
		],
		legend: {
			show: false
		},
		dataLabels: {
			enabled: false
		},
		colors: ['#198754'],
		plotOptions: {
			heatmap: {
				shadeIntensity: 0.5,
				colorScale: {
					ranges: [
						{ from: 0, to: 2, color: getCssVar('--bs-gray-200'), name: 'Low' },
						{ from: 3, to: 5, color: getCssVar('--bs-gray-400'), name: 'Medium' },
						{ from: 6, to: 9, color: getCssVar('--bs-gray-600'), name: 'High' },
						{ from: 10, to: 20, color: getCssVar('--bs-green'), name: 'Critical' }
					]
				}
			}
		},
		xaxis: {
			labels: {
				show: false,
				style: { colors: '#6c757d', fontSize: '10px' }
			}
		},
		yaxis: {
			labels: {
				show: false,
				style: { colors: '#6c757d' }
			}
		},
		grid: {
			borderColor: ($(worldMap).attr('data-mode') == 'transparent') ? 'transparent' : '#f1f3f5',
			padding: {
				top: 0,
				right: 0,
				bottom: 0,
				left: 0
			}
		},
		tooltip: {
			y: {
				formatter: function(val) {
					return val + ' tickets';
				}
			}
		}
	});
};

var DashboardV4 = function() {
	return {
		init: function() {
			handleDashboardV4DateRangeFilter();
			handleDashboardV4RevenueChart();
			handleDashboardV4RevenueBreakdownChart();
			handleDashboardV4CampaignBarChart();
			handleDashboardV4CustomerTrendChart();
			handleDashboardV4WorldMap();
			handleDashboardV4SupportHeatmap();
		}
	};
}();

$(document).ready(function() {
	DashboardV4.init();
});
