/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var barChart;
var handleRenderChartJs = function() {
	Chart.defaults.font.family = app.font.bodyFontFamily;
	Chart.defaults.font.size = 12;
	Chart.defaults.font.weight = 500;
	Chart.defaults.color = app.color.bodyColor;
	Chart.defaults.borderColor = app.color.light;
	Chart.defaults.plugins.legend.display = true;
	Chart.defaults.plugins.tooltip.padding = { left: 8, right: 12, top: 8, bottom: 8 };
	Chart.defaults.plugins.tooltip.cornerRadius = 8;
	Chart.defaults.plugins.tooltip.titleMarginBottom = 6;
	Chart.defaults.plugins.tooltip.color = app.color.componentBg;
	Chart.defaults.plugins.tooltip.multiKeyBackground = app.color.componentColor;
	Chart.defaults.plugins.tooltip.backgroundColor = app.color.componentColor;
	Chart.defaults.plugins.tooltip.titleFont.family = app.font.bodyFontFamily;
	Chart.defaults.plugins.tooltip.titleFont.weight = app.font.bodyFontWeight;
	Chart.defaults.plugins.tooltip.footerFont.family = app.font.bodyFontFamily;
	Chart.defaults.plugins.tooltip.displayColors = true;
	Chart.defaults.plugins.tooltip.boxPadding = 6;
	Chart.defaults.scale.grid.color = app.color.light;
	Chart.defaults.scale.beginAtZero = true;
	
	var ctx = document.getElementById('barChart');
	barChart = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ['S','M','T','W','T','F','S'],
			datasets: [{
				label: 'Total Visitors',
				data: [37,31,36,34,43,31,50],
				backgroundColor: app.color.dark,
				borderColor: 'transparent'
			}, {
				label: 'Returning Visitors',
				data: [27,21,16,14,23,11,30],
				backgroundColor: app.color.secondary,
				borderColor: 'transparent'
			}]
		}
	});
};


/* Init Demo
------------------------------------------------ */
$(document).ready(function() {
	handleRenderChartJs();
	
	$(document).on('theme-reload', function() {
		barChart.destroy();
		
		handleRenderChartJs();
	});
});