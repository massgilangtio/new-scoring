/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableFixedHeader = function() {
	"use strict";
    
	if ($('#dataTableFixedHeader').length !== 0) {
		const table = $('#dataTableFixedHeader').DataTable({
			lengthMenu: [20, 40, 60],
			fixedHeader: {
				header: true,
				headerOffset: $('#appHeader').height()
			},
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableFixedHeader();
});