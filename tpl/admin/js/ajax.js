function del_comment(id)
{
	var dataString = 'id='+id;
	$.ajax({type:"POST",url:"/admin/ajax/comments/del",dataType:'json', data:dataString,cache:false,success:
	function(data)
	{
		$('#comments_load').html(data.content);	
		$('#content_comments').show();
	}});	
}


function setStatCompare(obj, dataString)
{
    $.ajax({type:"POST",url:"/admin/ajax/statAmount/compare",dataType:'text', data:dataString,cache:false,success:
        function(data)
        {


        }
    });
}


$(document).ready(function(){

    ///Stat graph
    $('.compareStat').live('click',function()
    {
        var title = $(this).attr('dir');

        $(this).attr('dir', $(this).html());
        $(this).html(title);

        if($('iframe.compareStat').css('display')=='none')
        {
            $('iframe.compareStat').show();
        }
        else{
            $('iframe.compareStat').hide();
        }

        setStatCompare(this, '');
        $('.compareStat').attr('src', $('iframe.compareStat').attr('src'));
        return false;
    });

    /*$('.submitStatcompare').live('click',function()
    {
        var start=$("#startcompare").val();
        var end=$("#endcompare").val();
        var dataString = 'start='+start+'&end='+end;
        setStatCompare(this, dataString);
        location.reload();
        return false;
    });*/


	///////////////Sort
	$(".tb_sort").tableDnD({
		onDragClass:"hover"
	});
	$(".move").live('mouseup',function(){
		var id = $(this).parent().parent().attr('id');
		sortA(id);
	});
	
	///////Active
	$('.active_status').live('click',function()
	{
		var tb=$("#action").val();
		var tb2=$("#action2").val();
		var id=$(this).attr('id');
		var dataString = 'id='+id+'&tb='+tb+'&tb2='+tb2;
		$.ajax({type:"POST",url:"/admin/ajax/active",dataType:'json', data:dataString,cache:false,success:
		function(data)
		{
			if(!data.access)$('#'+id).html(data.active);//alert(tb);
			$('#message').html(data.message);	
			autoHide();
		}
	});});
	
	
	if($("div").is("#other_product"))
	{
		var id=$('input[name=id]').val();
		var dataString = 'id='+id;
		$.ajax({type:"POST",url:"/admin/ajax/product/otherproduct",dataType:'json', data:dataString,cache:false,success:
			function(data)
			{
				$('#other_product').html(data.content);
			}
		});
	}
	
	$('#product_add2').live('change',function()
    {
		var id=$('input[name=id]').val();
        var product_id=$(this).val();
        var dataString = 'id='+id+'&product_id='+product_id;
        $.ajax({type:"POST",url:"/admin/ajax/product/otherproduct",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
            	$('#other_product').html(data.content);
            }
        });
    });
	
	$('#catalog_add2').live('change',function()
    {
		var id=$(this).val();
		if(id!=0&&id!='')
		{
			var id2=$('input[name=id]').val();
			var dataString = 'id='+id+'&id2='+id2;
			$.ajax({type:"POST",url:"/admin/ajax/product/orderproduct2",dataType:'json', data:dataString,cache:false,success:
				function(data)
				{
					//alert(data.content);
					$('#drop-menu').html(data.content);
					$(".drop-menu").remove();
					//$("#drop-menu select[name=213]").remove();
					$("#drop-menu div").remove();
					$("#product_add2").searchable();
				}
			});
		}
    });
	
	$('.delother').live('click',function()
    {
		var id=$('input[name=id]').val();
        var del=$(this).attr('href');
        var dataString = 'id='+id+'&del='+del;
        $.ajax({type:"POST",url:"/admin/ajax/product/otherproduct",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
            	$('#other_product').html(data.content);
            }
        });
		return false;
    });
	
	///////Delete comment
	$('.delcomment').live('click',function()
	{
		var id=$(this).attr('href');
		del_comment(id);
		return false;
	});
	
	///////Delete comment
	$('.del_comments').live('click',function()
	{
		var items = [];
		$('.comment_id:checked').each(function () {
			var id = $(this).val();
			del_comment(id);
		});
		
		return false;
	});
	
	

    $('#add_module').live('change',function()
    {
        var id=$(this).val();
        var dataString = 'id='+id;
        $.ajax({type:"POST",url:"/admin/ajax/modules/addmodule",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
                if(data)
                {
					$('input[name=sort]').val(data.sort2);
					$('input[name=url]').val(data.url);
					$('#name_module').val(data.name);
                    $('#comment_module').val(data.comment);
                    $('#tables_module').val(data.tables);
					
					if(data.photo==1)$('input[name=create_dir]').attr('checked', 'checked');
					$("#menu_id [value="+data.sub2+"]").attr("selected", true);//alert(""+data.sub2+"#menu_id [value='']");
					/*
					$string = $row['sub']."\r\n".
					  $row['name']."\r\n".
					  $row['controller']."\r\n".
					  $row['url']."\r\n".
					  $row['tables']."\r\n".
					  $row['photo']."\r\n".
					  $row['comment']."\r\n".
					  $row['sort']."\r\n";
					*/
					if(data.config==1)$('input[name=get_config]').attr('disabled', false);
					if(data.translate==1)$('input[name=get_translate]').attr('disabled', false);
                }
                else{
                    $('#name_module').val('');
                    $('#comment_module').val('');
                    $('#tables_module').val('');
                }
            }
        });
    });
	
	$('#catalog_add').live('change',function()
    {
        var id=$(this).val();
        var dataString = 'id='+id;
        $.ajax({type:"POST",url:"/admin/ajax/orders/orderproduct",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
            	$('#product_add').html(data.content);
            }
        });
    });
	
	$('#product_add').live('change',function()
    {
        var id=$(this).val();
		var order_id=$(this).attr('name');
        var dataString = 'id='+id+'&order_id='+order_id;
        $.ajax({type:"POST",url:"/admin/ajax/orders/orderproductview",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
            	$('#order_product').html(data.content);
				$('#total').html(data.total);
            }
        });
		
    });
	
	$('.config_price').live('click',function()
    {
		var id=$(this).attr('href');
		if($('#config'+id).length>0)
		{
			$('#config'+id).remove();
		}
		else{
			var photo_id=$('#photo_price'+id).val();
			var texture_id=$('#texture_price'+id).val();
			var product_id=$("input[name=id]").val();
			var tr_id=$(this).parent().parent().parent().parent().attr('id');
			var dataString = 'id='+id+'&photo_id='+photo_id+'&texture_id='+texture_id+'&product_id='+product_id;
			$.ajax({type:"POST",url:"/admin/ajax/product/configprice",dataType:'json', data:dataString,cache:false,success:
				function(data)
				{
					$('#config'+id).remove();
					$('#'+tr_id).after(data.content);
				}
			});
		}
		return false;
    });

	$('.price_photo').live('click',function()
    {
		var id=$(this).parent().attr('dir');
		var photo_id=$(this).attr('alt');
		var check=$(this).hasClass('selected');
		$('#config'+id+' .price_photo').removeClass('selected');
		if(!check)
		{
			$(this).addClass('selected');
			$('#photo_price'+id).val(photo_id);
		}
		else $('#photo_price'+id).val('');
		return false;
    });
	
	$('.price_texture').live('click',function()
    {
		var id=$(this).parent().attr('dir');
		var photo_id=$(this).attr('alt');
		var check=$(this).hasClass('selected');
		$('#config'+id+' .price_texture').removeClass('selected');
		if(!check)
		{
			$(this).addClass('selected');
			$('#texture_price'+id).val(photo_id);
		}
		else $('#texture_price'+id).val('');
		return false;
    });

	$('#addprice').live('click',function()
    {
		var rel=$(this).attr('rel');
		var id=$("input[name=id]").val();
		var dataString = 'id='+id+'&rel='+rel;
		$.ajax({type:"POST",url:"/admin/ajax/product/addprice",dataType:'json', data:dataString,cache:false,success:
			function(data)
			{
                var price_price = $("input[name='price_price[]']").val();
                var purch_price = $("input[name='purch_price[]']").val();
                var price_margin = $("input[name='price_margin[]']").val();
                var price_discount = $("input[name='price_discount[]']").val();

				if(rel=='add')
                {
                    $('#load_price').append(data.content);
                }
				else $('#load_price').html(data.content);

                if($('#one_price').attr('checked'))
                {
                    $("input[name='price_price[]']").val(price_price);
                    $("input[name='purch_price[]']").val(purch_price);
                    $("input[name='price_margin[]']").val(price_margin);
                    $("input[name='price_discount[]']").val(price_discount);
                }
			}
		});
		return false;
    });
	
	$('.delprice').live('click',function()
    {
		var conf = confirm('Вы уверены что хотите удалить данную запись?');
		if(conf)
		{
			var rel=$(this).attr('rel');

			if(rel=='add')
			{
				var id=$(this).parent().parent().parent().parent();
				$(id).remove();
			}
			else{
				var id=$(this).attr('href');
				var dataString = 'id='+id;
				$.ajax({type:"POST",url:"/admin/ajax/product/delprice",dataType:'json', data:dataString,cache:false,success:
					function(data)
					{
						$('#load_price').html(data.content);
					}
				});
			}
		}
		return false;
    });

    $('select[name=provider]').live('change',function()
    {
        var dataString = 'id='+$(this).val();
        $.ajax({type:"POST",url:"/admin/ajax/product/getprovider",dataType:'json', data:dataString,cache:false,success:
            function(data)
            {
                if(!data.access)
                {
                    $("input[name='price_margin[]']").each(function(){
                        $(this).val(data.price);
                    });
                }
            }});

    });
	///////Active
	$('.del_image').live('click',function()
	{
		var id = $(this).attr('dir');
		var id2 = $(this).attr('id');
		var path = $(this).attr('href');
		var action = $('#action').val();

		var dataString = 'id='+id+'&path='+path+'&action='+action;
		$.ajax({type:"POST",url:"/admin/ajax/delimage",dataType:'json', data:dataString,cache:false,success:
		function(data)
		{
			if(!data.access)
			{
				$('#'+id+' img').attr('src', '/tpl/admin/images/no_image.jpg');
				$('#'+id2).hide();
			}
		}});
		return false; 
	});
});

function sortA(id)
{
	var tb=$("#action").val();
	var tb2=$("#action2").val();
	
	if(id=='load_price')
	{
		tb2='price';
		$('.price_config').remove();
	}
	var arr=$(".tb_sort").tableDnDSerialize();
	var dataString = 'arr='+arr+'&tb='+tb+'&tb2='+tb2;//alert(dataString);
	$.ajax({type: "POST",url: "/admin/ajax/sort",dataType:'json',data: dataString,cache: false,success:function(data){$('#message').html(data.message);autoHide();}});
}
