/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableColReorder = function() {
	"use strict";
    
	if ($('#dataTableColReorder').length !== 0) {
		$('#dataTableColReorder').DataTable({
			colReorder: true,
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableColReorder();
});