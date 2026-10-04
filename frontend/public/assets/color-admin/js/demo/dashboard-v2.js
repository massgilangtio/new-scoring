/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var dashboardV2Charts = [];

var getDashboardV2CssVar = function(name, fallback) {
	return getCssVar(name) || fallback;
};

var getDashboardV2Blue = function() {
	return getDashboardV2CssVar('--bs-blue', app.color.blue);
};

var getDashboardV2Teal = function() {
	return getDashboardV2CssVar('--bs-teal', app.color.teal);
};

var handleDashboardV2ChartRender = function(chart) {
	dashboardV2Charts.push(chart);
	chart.render();
};

var handleDashboardV2ChartDestroy = function() {
	for (var i = 0; i < dashboardV2Charts.length; i++) {
		if (dashboardV2Charts[i]) {
			dashboardV2Charts[i].destroy();
		}
	}
	dashboardV2Charts = [];
	$('#visitorLineChart, #visitorDonutChart').empty();
	
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

var getMonthName = function(number) {
	var month = [];
	month[0] = "JAN";
	month[1] = "FEB";
	month[2] = "MAR";
	month[3] = "APR";
	month[4] = "MAY";
	month[5] = "JUN";
	month[6] = "JUL";
	month[7] = "AUG";
	month[8] = "SEP";
	month[9] = "OCT";
	month[10] = "NOV";
	month[11] = "DEC";

	return month[number];
};

var getDate = function(date) {
	var currentDate = new Date(date);
	var dd = currentDate.getDate();
	var mm = currentDate.getMonth() + 1;
	var yyyy = currentDate.getFullYear();

	if (dd < 10) {
		dd = '0' + dd;
	}
	if (mm < 10) {
		mm = '0' + mm;
	}
	currentDate = yyyy+'-'+mm+'-'+dd;

	return currentDate;
};

var handleVisitorsAreaChart = function() {

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
	var visitorChartOptions = {
    series: visitorChartSeries,
		colors: [getDashboardV2Teal(), getDashboardV2Blue()],
		fill: { opacity: .75, type: 'solid' },
		legend: {
			position: 'top',
			horizontalAlign: 'right',
			offsetY: 15,
			offsetX: 500,
			labels: { colors: '#ffffff' }
		},
		xaxis: {
			type: 'datetime',
			tickAmount: 6,
			labels: { style: { colors: '#ffffff' } }
		},
		yaxis: { labels: { style: { colors: '#ffffff' } } },
		tooltip: { y: { formatter: function (val) { return "$ " + val + " thousands" } } },
		chart: { height: '100%', type: 'area', toolbar: { show: false }, stacked: true, zoom: { enabled: false } },
		plotOptions: { bar: { horizontal: false, columnWidth: '55%', endingShape: 'rounded' } },
		dataLabels: { enabled: false },
		grid: { 
			show: true, borderColor: 'rgba(255,255,255, .15)',
			xaxis: { lines: { show: true } },   
			yaxis: { lines: { show: true } },
			padding: { top: -40, right: 3, bottom: 0, left: 10 }
		},
		stroke: {  show: false, curve: 'straight' }
	};

  handleDashboardV2ChartRender(new ApexCharts(
    document.querySelector('#visitorLineChart'),
    visitorChartOptions
  ));
};

var handleVisitorsDonutChart = function() {
	var pieChartSeries = [416747,784466];
	var pieChartOptions = {
    series: pieChartSeries,
		labels: ['New Visitors', 'Return Visitors'],
		chart: { type: 'donut' },
		dataLabels: { dropShadow: { enabled: false }, style: { colors: ['#fff'] } },
		stroke: { show: false },
		colors: [getDashboardV2Teal(), getDashboardV2Blue()],
		legend: { show: false }
	};

  handleDashboardV2ChartRender(new ApexCharts(
    document.querySelector('#visitorDonutChart'),
    pieChartOptions
  ));
};

var handleVisitorsVectorMap = function() {
	if ($('#visitorMap').length !== 0) {
		var color1 = ($('#visitorMap').attr('data-color') == 'black') ? app.color.black : getDashboardV2Blue();
		var color2 = ($('#visitorMap').attr('data-color') == 'black') ? 'rgba('+ app.color.blackRgb + ', .5)' : getDashboardV2Teal();
		var scaleColor = ($('#visitorMap').attr('data-color')) ? ['rgba('+ app.color.blackRgb + ', .5)', 'rgba('+ app.color.blackRgb + ', .75)'] : [app.color.gray600, app.color.dark];
		var fillColor = ($('#visitorMap').attr('data-color') == 'black') ? 'rgba('+ app.color.blackRgb + ', .25)' : app.color.gray600;
				fillColor = ($('#visitorMap').attr('data-color') == 'white') ? 'rgba('+ app.color.whiteRgb + ', .25)' : fillColor;
		$('#visitorMap').vectorMap({
			map: 'world_mill',
			scaleColors: scaleColor,
			container: $('#visitorMap'),
			normalizeFunction: 'linear',
			hoverOpacity: 0.5,
			hoverColor: false,
			zoomOnScroll: false,
			markerStyle: {
				initial: {
					fill: app.color.dark,
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
						IN: color1,
						US: color2,
						MN: app.color.dark
					}
				}]
			},
			focusOn: {
				x: 0.5,
				y: 0.5,
				scale: 1
			},
			backgroundColor: 'transparent'
		});
	}
};

var handleCalendar = function() {
	$('#calendar').datepicker({
		format: 'yyyy-mm-dd',
		todayHighlight: true,
		autoclose: false
	}).on('changeDate', function (e) {
		$('#selectedDate').val(e.format());
	});
};

var handleNotification = function() {
	$('.toast').toast('show');
};

var DashboardV2 = function () {
	"use strict";
	return {
		//main function
		init: function () {
			handleVisitorsAreaChart();
			handleVisitorsDonutChart();
			handleVisitorsVectorMap();
			handleCalendar();
			handleNotification();
		},
		restartCharts: function() {
			handleDashboardV2ChartDestroy();
			handleVisitorsAreaChart();
			handleVisitorsDonutChart();
			handleVisitorsVectorMap();
		}
	};
}();

$(document).ready(function() {
	DashboardV2.init();
	
	$(document).on(app.darkMode.eventName, function() {
		DashboardV2.restartCharts();
	});
});
