/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleDataTableScroller = function() {
	"use strict";
    
	if ($('#dataTableScroller').length !== 0) {
		$('#dataTableScroller').DataTable({
			ajax:           "../assets/js/demo/json/scroller_demo.json",
			deferRender:    true,
			scrollY:        300,
			scrollCollapse: true,
			scroller:       true,
			responsive: true
		}).columns.adjust().responsive.recalc();
	}
};

$(document).ready(function() {
	handleDataTableScroller();
});