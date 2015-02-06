$(document).ready(function () {
    bascket();
	//testing_mode();

	$('#load_delivery select').attr('disabled', 'disabled');
	$('.delivery_change').live('click', function (){
		$('#load_delivery div').hide();
		$('#load_delivery select').attr('disabled', 'disabled');
		var id = $(this).val();
       $('#delivery'+id).show();
	   $('#deliv'+id).attr('disabled', false);
    });
	
	/////Subscribers
    $('.sign_up_modal2').live('click', function ()
	{
        $.ajax({type: "POST", url: "/ajax/users/signup2", dataType:'json', cache: false, success: function (data)
		{
			$('#sign_up_modal .modal-body').html(data.content);
        }});
    });
	
	/////Add to shop cart
	$('.cart_update').live('change', function(){
		var id = $(this).attr('id');
		var cnt = $(this).val();
		update_cart(id, cnt, 'update');
	});
	
	

	/////Add to shop cart
	$('.add_compare').live('click', function(){
		
		var id = $(this).attr('rel');
		var cat_id = $('#curr_cat').attr('title');
		
		var dataString = 'id='+id+'&cat_id='+cat_id;
		
		$.ajax({type: "POST", url: "/ajax/compare/add", data: dataString, dataType:"json", cache: false, success: 
		function (data)
		{
			if(data.add==1)
			{
				$.stickr({note: data.message, className: 'next', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});
			}
			else $.stickr({note: data.message, className: 'prev', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});
		}});
		//$('#compare'+id).next().replaceWith('<a href="/compare">просмотр</a>');
			
		
		return false;
	});

    /////Subscribers
    $('#subscribe_form').live('submit', function () {

        var name = $('#subscribe-name').val();
		var email = $('#subscribe-email').val();
        var dataString = 'name='+name+'&email='+email;
        $.ajax({type: "POST", url: "/ajax/mailer/subscribers", dataType:'json', data: dataString, cache: false, success: function (data)
		{
			if(data.err)
			{
            	$.stickr({note: data.err, className: 'prev', position:{right:'42%',bottom:'60%'},time:2000,speed:300});
			}
			else{
				$.stickr({note: data.message, className: 'next', position:{right:'42%',bottom:'60%'},time:2000,speed:300});
			}
        }});
		return false;
    });
	
	/////Add to delivery prices
    $('#delivery').live('change', function (){
        var id = $(this).val();
        var dataString = 'id=' + id;
        $.ajax({type: "POST", url: "/ajax/delivery/deliveryprice", data: dataString, cache: false, success: function (html) {
            $('#deliver_price').html(html);
        }});
    });
	
	//Cnange view catalog
    $('.sort_view').on('click', function() {
		$('.sort_view').removeClass('active');
		$(this).addClass('active');
		
		var id=$(this).attr('id');
		var dataString = 'id='+id;
        $.ajax({type: "POST", url: "/ajax/catalog/changeview", data: dataString, dataType:"json", cache: false, success: function(data)
		{
			$('body').attr('class',id);
			init_black_white();
			get_width();
        }});
		
    });
	
    x = '';
	$("#search_query_top").live('keyup', function(e){
		clearTimeout(x);
		//alert(e.which);

		if($(this).val().length>1)
		{
			var word = $(this).val();
			x = setTimeout(function(){autosearch(word);}, 300); 
		}
		else $('#results_search').css('display', 'none');
	}); 
	
	$("#results_search li").live('click', function(){
		$('#results_search').hide(200); 
		//$("#search_query_top").val($(this).children('.search_name').html());
		//$("#search_form").submit();
	}); 
	
	/*$("#results_search li").live('hover', function(){
		$("#search_query_top").val($(this).children().children('.search_name').html());
		//$("#search_form").submit();
	}); */	


	$("body").live('click', function(){
		$('#results_search').hide(200); 
		
	});
	
	$(".add_fav").live('click', function(){
		var id = $(this).attr('rel');
		
		var dataString = 'id='+id;
        $.ajax({type: "POST", url: "/ajax/product/fav", data: dataString, dataType:"json", cache: false, success: function(data)
		{
			$.stickr({note: data.message, className: 'next', position:{right:'42%',bottom:'60%'},time:2000,speed:300});
        }});
		return false;
	});
	
	$('.param-sel').live({
		mouseenter: function () {
			$(this).children('.popover2').show();
		},
		mouseleave: function () {
			$(this).children('.popover2').hide();
		}
	});


	//Выбор цвета, размера в карточке товара
    $('.paramchoose .param-sel').on('click', function () {
		
		if($(this).hasClass('noactive'))
		{
			return false;	
		}

        /*$(this).parent().find('div').each(function (){
            $(this).removeClass('selected');
        })*/


		if($(this).hasClass('selected'))
		{
			$(this).removeClass('selected');
			var id = $('.selected').attr('id');
			type=1;
			var block_id = $('.selected').parent('div').attr('id');
			
			if($('.selected').length==0)
			{
				$('.param-sel').removeClass('noactive');
				return false;	
			}
            var id2=$('.selected:not('+id+')').attr('id');
		}
        else{

			var id = $(this).attr('id');	
			var block_id = $(this).parent('div').attr('id');
			$('#'+block_id+' .param-sel').removeClass('selected');
            var id2=$('.selected:not('+id+')').attr('id');
			$(this).addClass('selected');

		}
        if(!id2)id2='';//alert(id2);


		if($(".selected").length==1)
		{
			$('#'+block_id+' .param-sel').removeClass('noactive');	
		}
		
        var type = 'color';
        if ($(this).hasClass("size_select")) type = 'size';
        else if ($(this).hasClass("power_select"))type = 'power';


        
		
        var product_id = $('#product_id').val();
        var dataString = 'id=' + id + '&product_id=' + product_id + '&id2=' + id2;
        $.ajax({type: "POST", url: "/ajax/product/loadparam", data: dataString, dataType: "json", cache: false, success: function (data) {
			
			$('.paramchoose').each( function() {
				if($(this).attr('id')!=block_id)
				{
           			$('#'+$(this).attr('id')+' .param-sel').addClass('noactive');
				}
			});
            var available = data.option;

            /* проставляем макс.возможное для заказа -  кол-во товара на складе 
               сбрасываем кол-во и выставляем новую цену
            */

            $('#cnt').attr('max', data.max);
            $('#cnt').val('1');
            if(data.cur_price)
			{
                $('#fixprice').val(data.cur_price);
                $('#total').html(data.cur_price);
            } else {
                $('#total').html($('#fixprice').val());
            }
			
			$('#total').html(data.price);
			
			$('#price_id').attr('name', data.price_id);
			
			if(data.photo)
			{
				var str=$('#extra_photo'+data.photo+' img').attr('src');
				str=str.substring(1);
				$('#photo_basket').val(str);
				$('#extra_photo'+data.photo).trigger('click');	
			}
			
            $('.param-sel').each( function() {

                /* если параметр был выбран и доступен - 
                   не убираем с него selected
                */

                /*if($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                    for (var i = 0; i < available.length; i++) {
                        if('param-'+available[i] == $(this).attr('id')) {
                            $(this).addClass('selected');
                        }
                    }
                }*/

                /* с доступные параметров убираем класс noactive */
                for (var i = 0; i < available.length; i++)
				{
                    if('param-'+available[i] == $(this).attr('id'))
					{
                        $(this).removeClass('noactive');
                    }
                }
            });

            /* возвращаем выбраный параметр активным и выбраным */
            //$('#'+id).addClass('selected');
            //$('#'+id).removeClass('noactive');

            /* Не даём изменять кол-во, если не выбран размер и цвет */
            var color = $('.color_select.selected').attr('id');
            var size = $('.size_select.selected').attr('id');
            if ($('.color_select').length > 0 && color == undefined)  $('#cnt').attr('max', 1);
            if ($('.size_select').length > 0 && size == undefined)  $('#cnt').attr('max', 1);
        }});

    });
	
	$('.change_color_photo').live('hover', function(){
		var src = $(this).attr('rel');
		$(this).parents('.product').children('.photo_product').find('img').attr('src', src);
		return false;
    });
	
	$('.size_block2 .checkbox').live('click', function(){
		if($(this).hasClass('checked'))
		{
			$(this).removeClass('checked');
		}
		else $(this).addClass('checked');
    });
	
	$('.product').hover(function(){
		$(this).addClass('hov_product');
		init_black_white();
	},function(){
		$(this).removeClass('hov_product');
	});
});

function autosearch(word)
{
	var dataString = 'word='+word;
	$.ajax({type: "POST",url: "/ajax/catalog/search",data: dataString,cache: false,success: function(data)
	{
		$('#results_search').html(data);
		$('#results_search').show(200);         
	}});	
}

/////Add to shop cart
$('.buy').live('click', function(){
    var id = $(this).attr('name');
    var amount = $('#cnt_'+id).val();
    var color = $('.color_select.selected').attr('id');	
	
	if($(this).hasClass('buy_q'))
	{
		var sizes = $('#size'+id).val();
	}
	else{
		var sizes = $('.size_select.selected').attr('id');	
	}
	
	if($('.color_select').length>0&&color==undefined)
	{
		$.stickr({note: 'Выберите цвет!', className: 'prev', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});	
	}
	else if($('.size_select').length>0&&sizes==undefined)
	{
		$.stickr({note: 'Выберите размер!', className: 'prev', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});	
	}
	else
	{
		var photo = $('#photo_basket').val();
        var dataString = 'id=' + id + '&amount=' + amount + '&color=' + color + '&size=' + sizes + '&photo=' + photo;
        $.ajax({type: "POST", url: "/ajax/orders/incart", data: dataString, dataType:"json", cache: false, success: 
		function (data)
		{
			bascket();
			$('#count_bascket').html(data.count);
			$('#sum_bascket').html(data.total);
            $.stickr({note: 'Товар добавлен!', className: 'next', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});
			$('#cart_content').html(data.content);
			$('#cart_popup').modal();
        }});
    }
});

///Bascket
function bascket()
{
    $.ajax
    ({
        type: "POST",
        url: "/ajax/orders/bascket",
        cache: false,
        success: function (html) {
            $("#basket_block").html(html);
        }
    });
}

function testing_mode() {    $.ajax    ({        type: "POST",        url: "/ajax/testingmode",        cache: false,        success: function (html) {            $("body").append(html);			$('#testing-block').slideToggle(150);        }    });}


////Add comments
$('#form_comment').live('submit', function(){
    var id = $("#id_comment").val();
	var type = $("#type_comment").val();
	var name = $("#name_comment_form").val();
	var email = $("#email_comment_form").val();
	var phone = $("#phone_comment_form").val();
	var message = $("#text_comment_form").val();
	var photo = $("#avatar").val();
	var err=false;
	if(name=='')
	{
	    $("#name_comment_form").css('border', '1px solid red');	
	    err=true;
	}
	else $("#name_comment_form").attr('style', '');	
	if(message=='')
	{
	    $("#text_comment_form").css('border', '1px solid red');	
	    err=true;
	}
	else $("#text_comment_form").attr('style', '');	

	if(!err)
	{
	    add_comment(id, type, name, email, phone, message, photo, '');
	}

    return false;
});


function add_comment(id, type, name, email, phone, message, photo, image)
{
	$('#load_comments').show();
	$('#send-rev').css({'opacity':'0.5'});
	var dataString = 'id=' + id + '&type=' + type + '&name=' + name + '&email=' + email + '&phone=' + phone + '&message=' + message + '&photo=' + photo + '&image=' + image;
	$.ajax({type: "POST", url: "/ajax/comments/addcomment", data: dataString, dataType: 'json', cache: false, success: function (data)
	{
		if(data.list!=null)
		{
			$("#comment_block").replaceWith(data.list);	
		}
		else{
			$('#load_comments').hide();
			$('#send-rev').css({'opacity':'1'});	
		}
		$("#input").html(data.message);
		$("#text_comment_form").val('');
		var pos = $('#header').offset();
		$.scrollTo(pos.top, 500);
		//e.preventDefault();
	}});		
}

//login pop-up
function check_auth()
{
	var email=$('#email_auth').val();
	var pass=$('#pass_auth').val();
    var dataString = 'email='+email+'&pass='+pass;
    $.ajax({type: "POST", url: "/ajax/users/checkauth", data: dataString, dataType:"json", cache: false, success: 
	function(data)
	{
		if(data.auth==1)
		{
			window.setTimeout('location.reload()', 3000);	
		}
        $('#auth_message').html(data.message);
    }});
	return false;
}


///Send form feedback
function sendFeedback()
{
    //$("#loader").css('display', 'block');
    var name = $("#f_name").val();
    var email = $("#f_email").val();//alert(email);
    var phone = $("#f_phone").val();//alert(email);
    var message = $("#f_message").val();

    var dataString = 'name=' + name + '&email=' + email + '&phone=' + phone + '&message=' + message;
    $.ajax({type: "POST", url: "/ajax/feedback/feedback", data: dataString, dataType: 'json', cache: false, success: function (html) {
        $("#message").html(html[1]);

        if (html[0] == 1) {
            $("#f_name").val('');
            $("#f_email").val('');//alert(email);
            $("#f_phone").val('');
            $("#f_message").val('');
            //closeFeedback(5000);
        }

    }});
    //$("#message").html(html);
    $("#loader").css('display', 'none');
    return false;
}


////Add Mailto
function mail_to() {
    var email = $("input[name=mailer]").val();
    var dataString = 'email=' + email;
    $.ajax({type: "POST", url: "/ajax/mailer/mailto", data: dataString, cache: false, success: function (html) {
        $("#message_mailer").html(html);
    }});
    return false;
}


function showList(id)
{
    if ($(id).css('display') == 'none')
	{
        $(id).show(200);
    }
    else{
        $(id).hide(200);
    }
    return false;
}

function showList2(id)
{
    if ($('.' + id).css('display') == 'none')
	{
        $('.sub_ul').hide(200);
        $('.' + id).show(200);
    }
	else{
        $('.' + id).hide(200);
    }
    return false;
}

function update_cart(id, cnt, action)
{
	var dataString = 'id=' + id + '&cnt=' + cnt + '&action=' + action;
	$.ajax({type: "POST", url: "/ajax/orders/updatecart", data: dataString, dataType:"json", cache: false, success: 
	function (data)
	{
		$('#cart_content').html(data.content);
		bascket();
	}});	
}

function checkExt(ext)
{
	//alert(ext);
	var regex = /^(JPG|jpg|jpeg|png|PNG|bmp|BMP|gif|GIF)$/i;
	if (!regex.test(ext))
	{
		$('.error').html('Please select a valid image file (jpg, png, gif, bmp are allowed)').fadeIn(500);
		return false;
	}	
	else{
		$('.error').html('').fadeOut(500);
		return true;
	}
}

function auth_check2()
{
    var email = $("#email_auth2").val();
    var password = $("#pass_auth2").val();
    var dataString = 'email='+email+'&password='+password;
    $.ajax({type: "POST",url: "/ajax/users/auth", data: dataString,cache: false,success: function(data){
        if(data!='')$("#message_auth2").html(data);
        else{
            location.reload();
        }
    }});
    return false;
}