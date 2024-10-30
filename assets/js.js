[].forEach.call(document.querySelectorAll(".mfs_slider"), function (el) {
	tns({
		container: el,
		lazyload: true,
		lazyloadSelector: ".tns-lazy",
		autoplay: true,
		autoplayHoverPause: true,
		nav: false,
		mouseDrag: true,
		autoplayButtonOutput: false,
		speed: 700,
	});
});
