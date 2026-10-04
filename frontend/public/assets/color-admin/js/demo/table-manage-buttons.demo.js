/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableButtons = function() {
	"use strict";
    
	if ($('#dataTableButtons').length !== 0) {
		$('#dataTableButtons').DataTable({
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
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableButtons();
});