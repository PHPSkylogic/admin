$(document).ready(function(){
	 $('.clear_group').live('click', function(){
		if($('#loading').length==0)
		{
			var id = $(this).attr('rel');
			
			if(id=='groupprice')
			{
				$('input[name=price_from]').val(0);
				$('input[name=price_to]').val(0);
			}
			else $('#'+id+' div').removeClass('checked');

			get_params_url();
			get_product();
		}
    });
	
	$('.clear_group_all').live('click', function() {
		if($('#loading').length==0)
		{
			$('#price_from').val(0);
			$('#price_to').val(0);
			$('#submit_price').removeClass('change_price');
			$('#load_filter div').removeClass('checked');
			get_params_url();
			get_product();
		}
    });
	
	
	$('.clear_price_range').live('click', function() {
		$('#submit_price').removeClass('change_price');
		
		$('#price_from').val(0);
		$('#price_to').val(0);

		get_params_url();
		get_product();
		
    });
	
	$('#submit_price').live('click', function() {
		if($('#price_from').val()>=0&&$('#price_to').val()!='')
		{
			$('#submit_price').addClass('change_price');
			
			get_params_url();
			get_product();
		}
    });
});

/*Filters*/

////UI range
function init_range(start, end, maximum)
{
	$( "#slider-range" ).slider({
      range: true,
      min: 0,
      max: maximum,
	  step:3,
      values: [ start, end ],
      slide: function( event, ui )
	  {
		$( "#price_from" ).val(ui.values[ 0 ]);
		$( "#price_to" ).val(ui.values[ 1 ]);
      }
    });

	$( "#price_from" ).val($( "#slider-range" ).slider( "values", 0 ));
	$( "#price_to" ).val($( "#slider-range" ).slider( "values", 1 ));
}


function get_product(only_filters)
{
    show_loading();
    var price_from = $('#price_from').val();
	var price_to = $('#price_to').val();
	var items = $('#params_url').val();
    var cat_id = $('#curr_cat').attr('title');
    var url = $('#curr_cat').attr('url');
	var change_price = $('#submit_price').attr('class');
	
	if(only_filters==1)only_filters='&only_filters=1';
	else only_filters='';
    var dataString = 'items=' + items + '&cat_id=' + cat_id + '&price_from=' + price_from + '&price_to=' + price_to+'&url=' + get_params_url2()+'&change_price=' + change_price;

    $.ajax({type: "POST", url: "/ajax/catalog/getfilter", data: dataString, dataType: 'json', cache: false, success: function (data)
	{
		if(data.filters)$('#load_filter').html(data.filters);//alert(data.product);
        if(data.product)$('#load_product').html(data.product);
        hide_loading();
		$.scrollTo(170,600);
		$('.product').hover(function(){
			$(this).addClass('hov_product');
			init_black_white();
		},function(){
			$(this).removeClass('hov_product');
		});
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

function show_loading()
{
    $('#load_filter').css('opacity', 0.2);
	//$('#load_product').css('opacity', 0);
    $('#filters').append('<img src="/images/loading.gif" id="loading" />');
	var left=($('.left_col').width()/2)-30;
	var top = $("body").scrollTop()+250;
	$('#loading').css({'left': left+'px', 'top': top+'px'});
	return false;
}

function hide_loading()
{
    $('#loading').remove();
    $('#load_filter').css('opacity', 1);
	//$('#load_product').css('opacity', 1);
}

$('.set_params').live('click', function () {
    if ((!$(this).hasClass('uncheck')||($(this).hasClass('uncheck')&&$(this).hasClass('checked')))&&$('#loading').length==0)
	{
        if ($(this).hasClass("checked"))$(this).removeClass('checked');
        else $(this).addClass('checked');
        get_params_url();
		get_product();
    }
    else return false;
});

$('#groupprice input').live('change', function(){
	if($('#loading').length==0)
	{
		get_product();
	}
});


$('.clear_param').live('click', function () {
	
	if($('#loading').length==0)
	{
		var id = $(this).attr('data-id');
		$('#filter'+id).trigger('click');
	}
});

function get_params_url2()
{
	var url=$('#curr_cat').attr('url');
	var params='';
	var sub_params='';
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

	if(sub_params!='')
	{
		$('#params_url').val(sub_params);
		params='/params/'+sub_params;
	}
	else $('#params_url').val('');
	
	var price_url=$('#price_from').val()+'-'+$('#price_to').val();

	if($('#submit_price').hasClass('change_price'))url='/catalog/'+url+params+'/price-range/'+price_url;
	else url='/catalog/'+url+params;
	
	if($('#sort_form select').val()!='')url+='/sort/'+$('#sort_form select').val();
	return url;
}

function get_params_url()
{
    history.pushState(null, null, get_params_url2());
}