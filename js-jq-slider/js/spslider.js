/**
 * jQuery simple plugin for image rotation
 * 
 * @author	Eugene Smith <easmith@mail.ru>
 * @company	Moneta.ru, LLC
 */

$.fn.spslider = function(options) { return new SPSlider(this.get(0), options); };

function SPSlider(context, options) { this.init(context, options); };

SPSlider.prototype = {

	options:{
		wRatio		: 1,
		hRatio		: 1,
		width			: 900,
		height		: 600,
		minImg		: 1, // %
		maxImg		: 100, // %
		minOpacity	: 1,
		maxOpacity	: 1,
		n					: 0,
		total			: 0
	},

	lock: false,

	init: function(context, options){
		var self = this;

		this.options = $.extend({
			wRatio		: 1,
			hRatio		: 1,
			width			: parseInt($(context).css('width')),
			height		: parseInt($(context).css('height')),
			minImg		: 1, // %
			maxImg		: 100, // %
			minOpacity	: 0,
			maxOpacity	: 1,
			n					: 1,
			total			: $(context).find("li.slide").length
		}, options || {});

		$(context).find(".arrow-left").click(function(){
			self.rotateSlide(-1);
			return false;
		})

		$(context).find(".arrow-right").click(function(){
			self.rotateSlide(1);
			return false;
		})
		console.log(this.options);
		this.rotateSlide(1);
	},

	getStyles:	function (n, c)
	{
		return {
			s : Math.sin(Math.PI * 2 * n / c),
			c : Math.cos(Math.PI * 2 * n / c),
			l : n % c
		}
	},

	getImgSize: function (img)
	{
		if (typeof img.naturalWidth == "undefined") { 
			// IE 6/7/8 
			var i = new Image(); 
			i.src = img.attr('src'); 
			var rw = i.width; 
			var rh = i.height; 
		}
		else { 
			// HTML5 browsers 
			var rw = img.naturalWidth; 
			var rh = img.naturalHeight; 
		}
		return {w: rw, h: rh}
	},

	showImg: function (obj, step)
	{
		this.lock = true;
		var self = this;
		var img = $(obj);
		var n = (img.attr('position') ? parseInt(img.attr('position')) + step : this.options.n++ ) % this.options.total 
		var s = this.getStyles(n, this.options.total);

		var imgSize = this.getImgSize(img)

		var toAnimImg = {
			width: Math.round(imgSize.w*(this.options.minImg + (1 + s.c)/2 * (this.options.maxImg - this.options.minImg))/100),
			height: Math.round(imgSize.h*(this.options.minImg + (1 + s.c)/2 * (this.options.maxImg - this.options.minImg))/100)
		}

		var toAnimLi = {
			left: Math.round((this.options.width-toAnimImg.width)/2 + (this.options.width - toAnimImg.width)/2 * s.s * this.options.wRatio) + "px",
			top: Math.round((this.options.height-toAnimImg.height)/2 + (this.options.height - toAnimImg.height)/2 * s.c * this.options.hRatio) + "px",
			opacity: this.options.minOpacity + (1 + s.c)/2 * (this.options.maxOpacity - this.options.minOpacity)
		}

		img.animate(toAnimImg, 2000, function() {
			// Animation complete.
		}).attr('position', n).attr('cos', Math.round(50 + s.c*50))

		var li = img.parent("li.slide");
		var zIndex	= Math.round((1 + s.c)/2 * this.options.total);

		li.animate(toAnimLi, 2000, function() {
			// Animation complete
			$(this).css('zIndex', zIndex);
			self.lock = false;
		}).css('zIndex', Math.round(zIndex + li.css('zIndex')))
	},

	rotateSlide: function (step)
{
	var self = this
	if (self.lock) return false;
	$("li.slide").each(function(){
		self.showImg($(this).find("img"), step);
	});
}
}
