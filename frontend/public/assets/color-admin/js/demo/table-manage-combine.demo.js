/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableCombinationSetting = function() {
	"use strict";
    
	if ($('#dataTableCombine').length !== 0) {
		var options = {
			layout: {
				topStart: 'buttons',
				topEnd: 'search',
				bottomStart: 'info',
				bottomEnd: 'paging'
			},
			buttons: [
				{ extend: 'copy', className: 'btn-sm' },
				{ extend: 'csv', className: 'btn-sm' },
				{ extend: 'excel', className: 'btn-sm' },
				{ extend: 'pdf', className: 'btn-sm' },
				{ extend: 'print', className: 'btn-sm' }
			],
			responsive: true,
			colReorder: true,
			keys: true,
			select: true
		};

		if ($(window).width() <= 767) {
			options.rowReorder = false;
			options.colReorder = false;
		}
		$.extend($.fn.dataTable.ext.pager, {
			numbers_length: 5
		});
		$('#dataTableCombine').DataTable(options).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableCombinationSetting();
});