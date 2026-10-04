/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var handleEmailToInput = function() {
	$('#email-to').tagit({
		availableTags: ["admin2@seantheme.com", "admin3@seantheme.com", "admin4@seantheme.com", "admin5@seantheme.com", "admin6@seantheme.com", "admin7@seantheme.com", "admin8@seantheme.com"]
	});
	$('#email-cc').tagit({
		availableTags: ["admin2@seantheme.com", "admin3@seantheme.com", "admin4@seantheme.com", "admin5@seantheme.com", "admin6@seantheme.com", "admin7@seantheme.com", "admin8@seantheme.com"]
	});
	$('#email-bcc').tagit({
		availableTags: ["admin2@seantheme.com", "admin3@seantheme.com", "admin4@seantheme.com", "admin5@seantheme.com", "admin6@seantheme.com", "admin7@seantheme.com", "admin8@seantheme.com"]
	});
};

var handleEmailContent = function() {
	$(".summernote").summernote({
    placeholder: 'Type your message here'
  });
};

var handleAddCc = function() {
	$(document).on('click', '[data-click="add-cc"]', function(e) {
		e.preventDefault();
		
		var targetName = $(this).attr('data-name');
		var targetId = 'email-cc-'+ targetName +'';
		var targetHtml = `
			<div class="row mb-2">
				<label class="col-form-label w-100px ps-2 pe-2 fw-500 text-end">${targetName}:</label>
				<div class="col">
					<ul id="${targetId}" class="form-control tagit">
					</ul>
				</div>
			</div>
		`;
		
		$('[data-id="extra-cc"]').append(targetHtml);
		$('#' + targetId).tagit();
		$(this).remove();
	});
};

var EmailCompose = function () {
	"use strict";
	return {
		//main function
		init: function () {
			handleEmailToInput();
			handleEmailContent();
			handleAddCc();
		}
	};
}();

$(document).ready(function() {
	EmailCompose.init();
});