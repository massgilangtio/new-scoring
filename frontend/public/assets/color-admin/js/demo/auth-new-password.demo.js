/*
Template Name: Color Admin - Responsive Admin Dashboard Template build with Twitter Bootstrap 5
Version: 6.0.0
Author: Sean Ngu
Website: http://www.seantheme.com/color-admin/
*/

var checkPasswordLength = function(tabActive, input) {
	const passwordInput = document.getElementById('password');
	const bars = [
		document.getElementById('bar1'),
		document.getElementById('bar2'),
		document.getElementById('bar3'),
		document.getElementById('bar4')
	];
	
	passwordInput.addEventListener('input', () => {
		const val = passwordInput.value;
	
		const lengthOK = val.length >= 8;
		const hasLower = /[a-z]/.test(val);
		const hasUpper = /[A-Z]/.test(val);
		const hasNumber = /\d/.test(val);
		const hasSymbol = /[^A-Za-z0-9]/.test(val);
	
		// Count how many types are present
		const typeCount = [hasLower, hasUpper, hasNumber, hasSymbol].filter(Boolean).length;
	
		// Bar 1: red if <8 chars, green if ≥8
		bars[0].className = 'progress-bar ' + (lengthOK ? 'bg-success' : 'bg-danger');
	
		// Bar 2: green if ≥8 chars and at least 2 types
		bars[1].className = 'progress-bar ' + (lengthOK && typeCount >= 2 ? 'bg-success' : 'bg-dark bg-opacity-10');
	
		// Bar 3: green if ≥8 chars and at least 3 types
		bars[2].className = 'progress-bar ' + (lengthOK && typeCount >= 3 ? 'bg-success' : 'bg-dark bg-opacity-10');
	
		// Bar 4: green if ≥8 chars and all 4 types
		bars[3].className = 'progress-bar ' + (lengthOK && typeCount === 4 ? 'bg-success' : 'bg-dark bg-opacity-10');
	});
};

$(document).ready(function() {
	checkPasswordLength();
});