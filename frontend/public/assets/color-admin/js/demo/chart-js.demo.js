/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/


var lineChart, barChart, radarChart, polarAreaChart, pieChart, doughnutChart;

var handleRenderChartJs = function() {
	Chart.defaults.font.family = getCssVar('--bs-body-font-family');
	Chart.defaults.font.size = 12;
	Chart.defaults.color = getCssVar('--bs-body-color');
	Chart.defaults.borderColor = getCssVar('--bs-component-border-color');
	Chart.defaults.plugins.legend.display = false;
	Chart.defaults.plugins.tooltip.padding = { left: 8, right: 12, top: 8, bottom: 8 };
	Chart.defaults.plugins.tooltip.cornerRadius = 8;
	Chart.defaults.plugins.tooltip.titleMarginBottom = 6;
	Chart.defaults.plugins.tooltip.color = getCssVar('--bs-component-bg');
	Chart.defaults.plugins.tooltip.multiKeyBackground = getCssVar('--bs-component-color');
	Chart.defaults.plugins.tooltip.backgroundColor = getCssVar('--bs-component-color');
	Chart.defaults.plugins.tooltip.titleFont.family = getCssVar('--bs-body-font-family');
	Chart.defaults.plugins.tooltip.titleFont.weight = getCssVar('--bs-body-font-weight');
	Chart.defaults.plugins.tooltip.footerFont.family = getCssVar('--bs-body-font-family');
	Chart.defaults.plugins.tooltip.displayColors = true;
	Chart.defaults.plugins.tooltip.boxPadding = 6;
	Chart.defaults.scale.grid.color = getCssVar('--bs-component-border-color');
	Chart.defaults.scale.beginAtZero = true;
	
	var ctx = document.getElementById('lineChart');
	lineChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
			datasets: [{
				color: getCssVar('--bs-app-theme'),
				borderColor: getCssVar('--bs-app-theme'),
				borderWidth: 1.5,
				pointBackgroundColor: getCssVar('--bs-component-bg'),
				pointBorderWidth: 1.5,
				pointRadius: 4,
				pointHoverBackgroundColor: getCssVar('--bs-app-theme'),
				pointHoverBorderColor: getCssVar('--bs-app-theme'),
				pointHoverRadius: 7,
				label: 'Total Sales',
				data: [12, 19, 4, 5, 2, 3]
			}]
		}
	});
	
	var ctx2 = document.getElementById('barChart');
	barChart = new Chart(ctx2, {
		type: 'bar',
		data: {
			labels: ['Jan','Feb','Mar','Apr','May','Jun'],
			datasets: [{
				label: 'Total Visitors',
				data: [37,31,36,34,43,31],
				backgroundColor: 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .25)',
				borderColor: getCssVar('--bs-app-theme'),
				borderWidth: 1.5
			},{
				label: 'New Visitors',
				data: [12,16,20,14,23,21],
				backgroundColor: 'rgba('+ getCssVar('--bs-secondary-rgb') +', .25)',
				borderColor: getCssVar('--bs-secondary'),
				borderWidth: 1.5
			}]
		}
	});
	
	var ctx3 = document.getElementById('radarChart');
	radarChart = new Chart(ctx3, {
		type: 'radar',
		data: {
			labels: ['United States', 'Canada', 'Australia', 'Netherlands', 'Germany', 'New Zealand', 'Singapore'],
			datasets: [
				{
					label: 'Mobile',
					backgroundColor: 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .25)',
					borderColor: getCssVar('--bs-app-theme'),
					pointBackgroundColor: getCssVar('--bs-component-bg'),
					pointBorderColor: getCssVar('--bs-app-theme'),
					pointHoverBackgroundColor: getCssVar('--bs-component-bg'),
					pointHoverBorderColor: getCssVar('--bs-app-theme'),
					data: [65, 59, 90, 81, 56, 55, 40],
					borderWidth: 1.5
				},
				{
					label: 'Desktop',
					backgroundColor: 'rgba('+ getCssVar('--bs-secondary-rgb') +', .25)',
					borderColor: getCssVar('--bs-secondary'),
					pointBackgroundColor: getCssVar('--bs-component-bg'),
					pointBorderColor: getCssVar('--bs-secondary'),
					pointHoverBackgroundColor: getCssVar('--bs-component-bg'),
					pointHoverBorderColor: getCssVar('--bs-secondary'),
					data: [28, 48, 40, 19, 96, 27, 100],
					borderWidth: 1.5
				}
			]
		}
	});
	
	var ctx4 = document.getElementById('polarAreaChart');
	polarAreaChart = new Chart(ctx4, {
		type: 'polarArea',
		data: {
			datasets: [{
				data: [11, 16, 7, 3, 14],
				backgroundColor: ['rgba('+ getCssVar('--bs-app-theme-rgb') +', .5)', 'rgba('+ getCssVar('--bs-secondary-rgb') +', .5)', 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .25)', 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .75)', 'rgba('+ getCssVar('--bs-secondary-rgb') +', .75)'],
				borderWidth: 0
			}],
			labels: ['IE', 'Safari', 'Chrome', 'Firefox', 'Opera']
		}
	});
	
	var ctx5 = document.getElementById('pieChart');
	pieChart = new Chart(ctx5, {
		type: 'pie',
		data: {
			labels: ['Total Visitor', 'New Visitor', 'Returning Visitor'],
			datasets: [{
				data: [300, 50, 100],
				backgroundColor: ['rgba('+ getCssVar('--bs-app-theme-rgb') +', .75)', 'rgba('+ getCssVar('--bs-warning-rgb') +', .75)', 'rgba('+ getCssVar('--bs-success-rgb') +', .75)'],
				hoverBackgroundColor: ['rgba('+ getCssVar('--bs-app-theme-rgb') +', .5)', 'rgba('+ getCssVar('--bs-warning-rgb') +', .5)', 'rgba('+ getCssVar('--bs-success-rgb') +', .5)'],
				borderWidth: 0
			}]
		}
	});
	
	var ctx6 = document.getElementById('doughnutChart');
	doughnutChart = new Chart(ctx6, {
		type: 'doughnut',
		data: {
			labels: ['Total Visitor', 'New Visitor', 'Returning Visitor'],
			datasets: [{
				data: [300, 50, 100],
				backgroundColor: ['rgba('+ getCssVar('--bs-app-theme-rgb') +', .75)', 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .25)', 'rgba('+ getCssVar('--bs-app-theme-rgb') +', .5)'],
				hoverBackgroundColor: [getCssVar('--bs-app-theme'), getCssVar('--bs-app-theme'), getCssVar('--bs-app-theme')],
				borderWidth: 0
			}]
		}
	});
};

/* Controller
------------------------------------------------ */
$(document).ready(function() {
	handleRenderChartJs();
	
	$(document).on('theme-reload', function() {
		lineChart.destroy();
		barChart.destroy();
		radarChart.destroy();
		polarAreaChart.destroy();
		pieChart.destroy();
		doughnutChart.destroy();
		
		handleRenderChartJs();
	});
	
});