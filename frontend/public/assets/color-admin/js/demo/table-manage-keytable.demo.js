/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableKeyTable = function() {
	"use strict";
    
	if ($('#dataTableKeytable').length !== 0) {
		$('#dataTableKeytable').DataTable({
			autoWidth: true,
			keys: true,
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableKeyTable();
});