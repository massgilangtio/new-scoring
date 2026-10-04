/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleRenderScrollSpy = function() { 
	var scrollSpyTarget = '[data-init="scrollspy"]';
	var scrollSpyOffset = $('#appHeader').height();
	var scrollSpy = new bootstrap.ScrollSpy(document.body, {
		target: scrollSpyTarget,
		offset: scrollSpyOffset
	});
};

var handleScrollTo = function() {
	$(document).on('click', '[data-toggle="scroll-to"]', function(e) {
		e.preventDefault();
		
		var targetId = $(this).attr('href');
		
		$('html, body').animate({
			scrollTop: $(targetId).offset().top - $('#appHeader').height() - 20
		}, 0);
	});
};

var ScrollSpy = function () {
	"use strict";
	return {
		//main function
		init: function () {
			handleRenderScrollSpy();
			handleScrollTo();
		}
	};
}();

$(document).ready(function() {
	ScrollSpy.init();
});