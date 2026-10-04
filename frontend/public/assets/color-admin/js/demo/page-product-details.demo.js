/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var ProductDetails = function () {
	"use strict";
	return {
		//main function
		init: function () {
			$('[data-render="summernote"]').summernote({
				height: 300
			});
			$("#tag-size, #tag-color, #tag-material, #tags").tagit();
		}
	};
}();

$(document).ready(function() {
	ProductDetails.init();
});