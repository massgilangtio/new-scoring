/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableRowReorder = function() {
	"use strict";
    
	if ($('#dataTableRowReorder').length !== 0) {
		$('#dataTableRowReorder').DataTable({
			responsive: true,
			rowReorder: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableRowReorder();
});