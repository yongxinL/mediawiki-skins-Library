$(function () {
	'use strict';
	// Mobile First support so active menu in navbar
	// has submenu displayed by default but not in desktop
	// so the code below will hide the active menu if it's desktop
	if ($('.sidebar-icon .nav .nav-link.active').length) {
		var targ = $('.sidebar-icon .nav .nav-link.active').attr('href');
		console.log(targ);

		$(targ).addClass('show');
		if (window.matchMedia('(min-width: 1200px)').matches) {
			$('.sidebar-aside').addClass('show');
		}
		if (window.matchMedia('(min-width: 992px)').matches && window.matchMedia('(max-width: 1199px)').matches) {
			$('.sidebar-icon').removeClass('active');
		}
	}
	// showing submenu in aside while hiding previous open submenu
	$('.sidebar-icon .nav .nav-link').on('click', function (e) {
		e.preventDefault();

		$(this).addClass('active');
		$(this).siblings().removeClass('active');

		$('.sidebar-aside').addClass('show');

		var targ = $(this).attr('href');
		console.log(targ);

		$(targ).addClass('show');
		$(targ).siblings().removeClass('show');
	});
	// showing submenu when clicking on toggle-menu
	$('.sidebar-toggle-menu').on('click', function (e) {
		e.preventDefault();

		if (window.matchMedia('(min-width: 992px)').matches) {
			$('.sidebar-icon .nav .nav-link.active').removeClass('active');
			$('.sidebar-aside').removeClass('show');
		} else {
			$('body').removeClass('sidebar-show');
		}
	})
	// this will show sidebar in left for mobile only
	$('#sidebarshow').on('click', function (e) {
		e.preventDefault();
		$('body').toggleClass('sidebar-show');
	});

	// table of content (#toc)
	if( $('#toc').length == 1 ) {
		// move to media-right
		$('#toc').appendTo($('.media-right'));
		// required by bootstrap scrollspy
		// $('#toc ul').addClass('nav');
		// $('#toc ul li').addClass('nav-item');
		// $('#toc ul a').addClass('nav-link');
		// var scrollSpy = new bootstrap.ScrollSpy(document.body, {
		// 	target: '#toc ul'
		// });
	}
});