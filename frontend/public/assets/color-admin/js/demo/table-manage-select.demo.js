/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableSelect = function() {
	"use strict";
    
	if ($('#dataTableSelect').length !== 0) {
		$.extend($.fn.dataTable.ext.pager, {
			numbers_length: 5
		});
		$('#dataTableSelect').DataTable({
			select: true,
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableSelect();
});