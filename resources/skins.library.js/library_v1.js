$(function(){
  'use strict'

	if($('.mw-sidebar .nav-link.active').length) {
		var targ = $('.mw-sidebar .nav-link.active').attr('href');
		$(targ).addClass('show');

		if(window.matchMedia('(min-width: 1600px)').matches) {
			$('.mw-sidebar-aside').addClass('show');
		}

		if(window.matchMedia('(min-width: 992px)').matches && window.matchMedia('(max-width: 1199px)').matches) {
			$('.mw-sidebar .nav-link.active').removeClass('active');
		}
	}

	$('.mw-sidebar .nav-link').on('click', function(e){
		e.preventDefault();

		$(this).addClass('active');
		$(this).siblings().removeClass('active');

		$('.mw-sidebar-aside').addClass('show');

		var targ = $(this).attr('href');
		$(targ).addClass('show');
		$(targ).siblings().removeClass('show');
	});

	$('.mw-sidebar-toggle-menu').on('click', function(e){
		e.preventDefault();

		if(window.matchMedia('(min-width: 992px)').matches) {
			$('.mw-sidebar .nav-link.active').removeClass('active');
			$('.mw-sidebar-aside').removeClass('show');
		} else {
			$('body').removeClass('mw-sidebar-show');
		}
	})

	$('#mw-sidebarShow').on('click', function(e){
		e.preventDefault();
		$('body').toggleClass('mw-sidebar-show');
	});

	// Shows topbar dropdown while hiding others
	$('.mw-topbar .dropdown > a').on('click', function(e) {
		e.preventDefault();
		$(this).parent().toggleClass('show');
		$(this).parent().siblings().removeClass('show');
	});
	
	if (!window.xMode) {
		var node = document.createElement('div');
		node.className = 'to-top';
		document.body.appendChild(node);
		node.addEventListener('mousedown', function() {
			this.classList.add('active');
			$('html, body').stop().animate({
				scrollTop: 0
			}, 500, 'swing', function() {
				this.classList.remove('active')
			}.bind(this))
		});
		document.addEventListener('scroll', function() {
			if (window.scrollY > window.innerHeight) node.classList.add('show');
			else node.classList.remove('show')
		})
	}
});
