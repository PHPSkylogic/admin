jQuery.fn.topLink = function(settings) {
	settings = jQuery.extend({
		min: 1,
		fadeSpeed: 200,
		ieOffset: 50
	}, settings);
	return this.each(function() {
		//listen for scroll
		var el = $(this);
		el.css('display','none'); //in case the user forgot
		$(window).scroll(function() {
			if(!jQuery.support.hrefNormalized) {
				el.css({
					'position': 'absolute',
					'top': $(window).scrollTop() + $(window).height() - settings.ieOffset
				});
			}
			if($(window).scrollTop() >= settings.min)
			{
				el.fadeIn(settings.fadeSpeed);
			}
			else
			{
				el.fadeOut(settings.fadeSpeed);
			}
		});
	});
};
	
$(document).ready(function() {
	$('#top-link').topLink({
		min: 400,
		fadeSpeed: 500
	});
	//smoothscroll
	$('#top-link').click(function(e) {
		e.preventDefault();
		$.scrollTo(0,300);
	});
});

function preloadImages(images)
{
	for(var i = 0; i < images.length; i++){
		var image = images[i];
		$("<img />").attr("src", image);//alert(image);
	}//for        
}

function mycarousel_initCallback(carousel)
{
    // Disable autoscrolling if the user clicks the prev or next button.
    carousel.buttonNext.bind('click', function() {
        carousel.startAuto(0);
    });

    carousel.buttonPrev.bind('click', function() {
        carousel.startAuto(0);
    });

    // Pause autoscrolling if the user moves with the cursor over the clip.
    carousel.clip.hover(function() {
        carousel.stopAuto();
    }, function() {
        carousel.startAuto();
    });
};

function showTab(id)
{
	$('#tabs div.div_tab').css('display','none');
	$('#'+id).css('display','block');
}

function onpage(cnt)
{
	$('#onpage').val(cnt);
	$('#onpage_form').submit();
}

function show_filters(id, hide_text, show_text)
{
	if($('#filters'+id).css('display')=='none')
	{	
		$('#filters'+id).show(200);
	}
	
}

$(document).ready(function()
{
	if($('.help_o').length>0)$('.help_o').tooltip();
	//$("select").selectBox();

	$('.nostock').live('click', function(){
		$('#inform_id').val($(this).attr('rel'));
	});
	
	
	$('.show_filters').live('click', function(){
		var arr=$(this).attr('rel').split('_');

		$(this).attr('rel', arr[0]+'_'+$(this).html());
		$(this).html(arr[1])
		if($('#filters'+arr[0]).css('display')=='none')$('#filters'+arr[0]).show(200);
		else $('#filters'+arr[0]).hide(200);
		return false;
	});
	
	if($(".product_in").length)
	{
		var photos = [];
		photos.push($('#img_load a').attr('href'));
		$('.more-photo a').each(function(){
			photos.push($(this).attr('href'));
		});
		preloadImages(photos);
		$(".easyzoom img").elevateZoom({
		  zoomType				: "inner",
		  cursor: "crosshair"
		});
	}

	$('.load_more_photo').live('click', function(){
		$('#img_load a').attr('href', $(this).attr('href'));
		$('#img_load img').attr('src', $(this).attr('href'));
		$('#zoom').parent('a').attr('href', $(this).attr('href'));
		$('.zoomContainer').remove();
		$(".easyzoom img").elevateZoom({
		  zoomType				: "inner",
		  cursor: "crosshair"
		});
		return false;
	});


	$('.show_auth').live('click', function(){
		if($(this).val()=='1')
		{
			$('#formID').show();
			$('#formID2').hide();
		}
		else{
			$('#formID2').show();
			$('#formID').hide();	
		}
	});
	
	// Изменение количества в карточке товара
    $('.countchange').live('click', function() {

		var id = $(this).attr('rel');
        var min = 1;
        var max = parseInt( $('#'+id).attr('max'));
        var current = parseInt( $('#'+id).val());
        var cnt = current;

        var price = parseInt($('#fixprice').val());

        if($(this).hasClass('cnt-up') && (current < max)) {
                var cnt = current + 1;
        }

        if($(this).hasClass('cnt-down') && (current > min)) {
                var cnt = current - 1;
        }

        var newprice = cnt*price;
        $('#'+id).val(cnt);
        //$('#total span').html(numToPrice(newprice));
		if($('#update_order').length>0)$('#update_order').submit();
		else if($('.cart_popup_tb').length>0)update_cart(id, cnt, 'update');

    });
	
	
	/////Add to shop cart
	$('.cart_update').live('change', function(){
		var id = $(this).attr('id');
		var cnt = $(this).val();
		update_cart(id, cnt, 'update');
	});

	$('#accept_sign').live('click',function(){
		if($(this).attr('checked')=='checked')
			$('input[name=sign_up]').attr('disabled', false);
		else
			$('input[name=sign_up]').attr('disabled', true);	
	});
	
	$('#tabs li').live('click',function(){
		$('#tabs li').removeClass('hov_tab');
		$(this).addClass('hov_tab');
	});
		
	$('#sort_block a').live('click', function(){
		var id = $(this).attr('class');
		$('#sort_form input').val(id);
		$('#sort_form').submit();
		return false;
	});
	
	$('.amount').live('change', function(){
		$('#update_order').submit();
	});
	
	
	
	///Lightbox
	$("a[rel=lightbox]").fancybox({
			'transitionIn'		: 'none',
			'transitionOut'		: 'none',
			'titlePosition' 	: 'over',
			'titleFormat'		: function(title, currentArray, currentIndex, currentOpts) {
				return '<span id="fancybox-title-over">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';
			}
	});

   
	
    function numToPrice(x)
	{
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
	
	var curr_cat = $('#curr_cat').attr('title');
	if(curr_cat!='')
	{
		$("#"+curr_cat).parents("ul").show(200); // показать подчиненые одно последующее
		$("#"+curr_cat+' ul:first').show(200);  // показать подчененый каталог
		$("#"+curr_cat).parents("li").addClass('hov_left'); // показать подчиненые одно последующее
		$("#"+curr_cat).addClass('hov_left');
	}
	init_black_white();


    $('.dirClass span').click(function(){
       if($(this).next().css('display')=='none')$(this).next().show(200);
       else $(this).next().hide(200);
    });
});

function post_currency(id)
{
	$('#form_currency input').val(id);
	$('#form_currency').submit();
	return false;	
}

function windowHeight()
{
	var de = document.documentElement;
	return self.innerHeight || ( de && de.clientHeight ) || document.body.clientHeight;
}
function windowWidth()
{
	var de = document.documentElement;
	return self.innerWidth || ( de && de.clientWidth ) || document.body.clientWidth;
}

function IsValidateEmail(email)
{
      var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,6})$/;
      return reg.test(email);
}

function init_black_white()
{
	$('.BWfade').remove();
	$('.bwWrapper').each( function(){
		var width=$(this).width()-30;
		//alert(width);
		$(this).children('ul').width(width);
    });
	
	$('.bwWrapper').BlackAndWhite({
		hoverEffect : true, // default true
		// set the path to BnWWorker.js for a superfast implementation
		webworkerPath : false,
		// for the images with a fluid width and height 
		responsive:true,
		// to invert the hover effect
		invertHoverEffect: false,
		// this option works only on the modern browsers ( on IE lower than 9 it remains always 1)
		intensity:1,
		speed: { //this property could also be just speed: value for both fadeIn and fadeOut
			fadeIn: 0, // 200ms for fadeIn animations
			fadeOut: 0 // 800ms for fadeOut animations
		},
		onImageReady:function(img) {
			// this callback gets executed anytime an image is converted
		}
	});	
}


function show_video()
{
	showTab('Video');
	$('#tabs li').removeClass('hov_tab');
	$('#tabs li').last().addClass('hov_tab');	
}