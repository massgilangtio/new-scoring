/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableFixedColumns = function() {
	"use strict";
    
	if ($('#dataTableFixedColumns').length !== 0) {
		$('#dataTableFixedColumns').DataTable({
			scrollY:        300,
			scrollX:        true,
			scrollCollapse: true,
			paging:         false,
			fixedColumns:   true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableFixedColumns();
});