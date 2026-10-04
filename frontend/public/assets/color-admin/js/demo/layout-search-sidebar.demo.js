/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/



var LayoutSearchSidebar = function () {
	"use strict";
	return {
		//main function
		init: function () {
			var elm = document.querySelector('.'+ app.sidebar.class +':not(.'+ app.sidebarEnd.class +') ['+ app.scrollBar.attr +']');
			if (elm) {
				var osInstance = OverlayScrollbarsGlobal.OverlayScrollbars(elm, {
					scrollbars: {
						autoHide: 'leave'
					}
				});
				osInstance.elements().viewport.scrollTop = 0;
			}
		}
	};
}();

$(document).ready(function() {
	LayoutSearchSidebar.init();
});