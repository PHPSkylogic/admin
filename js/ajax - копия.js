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
	
	$("#results_search li").live('hover', function(){
		$("#search_query_top").val($(this).children().children('.search_name').html());
		//$("#search_form").submit();
	}); 	


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
	
	//Выбор цвета, размера в карточке товара
    $('.param-sel').on('click', function() {
        $(this).parent().find('div').each( function() {
            $(this).removeClass('selected');
        })
        $(this).addClass('selected');
		
		var type='color';
		if($(this).hasClass("size_select"))type='size';
		else if($(this).hasClass("power_select"))type='power';
		
		
		var id = $(this).attr('id');
		var product_id=$('#product_id').val();
		var dataString = 'id='+id+'&product_id='+product_id;
        $.ajax({type: "POST", url: "/ajax/product/loadparam", data: dataString, dataType:"json", cache: false, success: function(data)
		{
			$('.param-sel').addClass('noactive');
        	for (var i = 0; i < data.option.length; i++)
			{
				$('#param-'+data.option[i]).removeClass('noactive');
			}
			$('#'+id).removeClass('noactive');
        }});
		
    });
	
	 $('.clear_group').live('click', function() {
       
		
		var id = $(this).attr('rel');
		
		if(id=='groupprice')
		{
			$('input[name=price_from]').val(0);
			$('input[name=price_to]').val(0);
		}
		else $('#'+id+' div').removeClass('checked');
		
		
		get_params_url();
		get_product();
		
    });
	
	 $('.clear_group_all').live('click', function() {
		$('input[name=price_from]').val(0);
		$('input[name=price_to]').val(0);
		$('#load_filter div').removeClass('checked');
		get_params_url();
		get_product();
		
		
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
	var size = $('.size_select').val();

	if($('.color_select').length>0&&color==undefined)
	{
		$.stickr({note: 'Выберите цвет!', className: 'err', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});	
	}
	else if($('.size_select').length>0&&size==undefined)
	{
		$.stickr({note: 'Выберите размер!', className: 'prev', position: {right:'42%',bottom:'60%'}, time: 1000, speed: 300});	
	}
	else
	{
        var dataString = 'id=' + id + '&amount=' + amount + '&color=' + color + '&size=' + size;
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


/*Filters*/
function get_product() {
    show_loading();
    var price_from = $('input[name=price_from]').val();
	var price_to = $('input[name=price_to]').val();
	var items = $('#params_url').val();
    var cat_id = $('#curr_cat').attr('title');
    var url = $('#curr_cat').attr('url');
    var dataString = 'items=' + items + '&cat_id=' + cat_id + '&price_from=' + price_from + '&price_to=' + price_to+'&url=' + get_params_url2();

    $.ajax({type: "POST", url: "/ajax/catalog/getfilter", data: dataString, dataType: 'json', cache: false, success: function (data) {
        $('#load_filter').html(data.filters);
        $('#load_product').html(data.product);
        hide_loading();
		$.scrollTo(170,600);
    }});
}

function clear_ses(id)
{
    var cat_id = $('#curr_cat').attr('title');
    var dataString = 'clear_id=' + id + '&cat_id=' + cat_id+'&url=' + get_params_url2();

    $.ajax({type: "POST", url: "/ajax/catalog/getfilter", data: dataString, dataType: 'json', cache: false, success: function (data) {
        $('#load_filter').html(data.filters);
        $('#load_product').html(data.product);
		get_params_url();
    }});
}

function show_loading() {
    $('#load_product').css('opacity', 0.2);
    $('#load_product').append('<img src="/images/loading.gif" id="loading" />');
	return false;
}

function hide_loading() {
    $('#loading').remove();
    $('#load_product').css('opacity', 1);
}

$('.set_params').live('click', function () {
    if (!($(this).hasClass('uncheck'))) {
        if ($(this).hasClass("checked"))$(this).removeClass('checked');
        else $(this).addClass('checked');
        get_params_url();
		get_product();
    }
    else return false;
});

$('#groupprice input').live('change', function () {
        get_product();
});


$('.clear_param').live('click', function () {

    var id = $(this).attr('data-id');

    var cat_id = $('#curr_cat').attr('title');
    var dataString = 'clear_id=' + id + '&cat_id=' + cat_id+'&url=' + get_params_url2();

    $.ajax({type: "POST", url: "/ajax/catalog/getfilter", data: dataString, dataType: 'json', cache: false, success: function (data) {
        $('#load_filter').html(data.filters);
        $('#load_product').html(data.product);
        get_params_url();
		
    }});
});

$('.del_b img').live('click', function () {

	var id=$(this).attr('id');
    var dataString = 'id=' + id;
    $.ajax({type: "POST", url: "/ajax/orders/removefrombasket", data: dataString, dataType:"json", cache: false, success: 
	function (data)
	{
        bascket();
    }});	
});

function get_params_url2()
{
	var url=$('#curr_cat').attr('url');
	var params='';
	var sub_params='';
	/*$('.group_params').each(function(){
		sub_params='';
		$(this).find('.checked').each(function(){
			if(sub_params!='')sub_params+=',';
       		sub_params+=$(this).attr('pid');
			//alert($(this).html());
		});	
		if(sub_params!='')
		{
			if(params!='')params+=';';
			params+=$(this).attr('id')+'='+sub_params;
		}
    });	*/
	var group='';
	$('.checked').each(function()
	{
		var arr = $(this).attr('pid').split("-");
		if(group!=arr[0])
		{
			group=arr[0];
			if(sub_params!='')sub_params+=';';
			sub_params+=group+'='+arr[1];
		}
		else{
			if(sub_params!='')sub_params+=',';
			sub_params+=arr[1];
		}
    });
	;
	if(sub_params!='')
	{
		$('#params_url').val(sub_params);
		params='/params/'+sub_params;
	}
	else $('#params_url').val('');
	
	return '/catalog/'+url+params;
	//update_paging();
}

function get_params_url()
{
    history.pushState(null, null, get_params_url2());
}

$('#close_btn').live('click', function () {
    $('#registration_form').remove();
    $('#login_form').remove();
    $('#overlay').fadeOut('slow', function () {
        $('#overlay').css('display', 'none');
    });
});

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