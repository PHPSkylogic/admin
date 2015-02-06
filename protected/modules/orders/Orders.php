<?
class Orders extends Model
{
    static $table='orders'; //Главная талица
    static $name='Заказы'; // primary key
	
	public function __construct($registry)
    {
        parent::getInstance($registry);
    }

    //для доступа к классу через статичекий метод
	public static function getObject($registry)
	{
		return new self::$table($registry);
	}

	public function add()
	{
		$message='';
		if(isset($_POST['email'], $_POST['username'], $_POST['phone'], $_POST['country'], $_POST['city'])&&$_POST['username']!="")
		{
            $err="";
			if($err=="")
            {
                $date_add=date("Y-m-d H:i:s");
                $id=$this->db->insert_id("INSERT INTO `".$this->table."` SET
                                                                         `username`=?,
                                                                         `email`=?,
                                                                         `country`=?,
                                                                         `city`=?,
                                                                         `phone`=?,
                                                                         `date_add`=?,
																		 `comment`=?,
																		 status_id=?", 
																		 array(
                                                                         $_POST['username'],
                                                                         $_POST['email'],
                                                                        $_POST['country'],
                                                                        $_POST['city'],
                                                                        $_POST['phone'],
                                                                        $date_add,
																		$_POST['comment'],
                                                                        1)
                );
				if(isset($_POST['delivery']))$this->db->query("UPDATE orders SET delivery_id=? WHERE id=?", array($_POST['delivery'], $id));	
				if(isset($_POST['payment']))$this->db->query("UPDATE orders SET payment_id=? WHERE id=?", array($_POST['payment'], $id));
	
                $message.= messageAdmin('Данные успешно добавлены');
            }
            else $message.= messageAdmin($err, 'error');
		}
		else $message.= messageAdmin('При добавление произошли ошибки', 'error');	
		return $message;
	}

	public function save()
	{
		$message='';
		if(isset($this->registry['access']))$message = $this->registry['access'];
		else
		{
            if(isset($_POST['status'], $_POST['email'], $_POST['username'], $_POST['phone'], $_POST['country'], $_POST['city']))
            {
                $err='';
				
                if($err=="")
                {
					
					/////Update orders product
					$total=0;
					$amount=0;
					
					if(isset($_POST['delivery']))
					{
						$row = $this->db->row("SELECT * FROM delivery WHERE id=?", array($_POST['delivery']));
						$total +=$row['price'];	
						$this->db->query("UPDATE orders SET delivery_id=? WHERE id=?", array($_POST['delivery'], $_POST['id']));	
					}

					if(isset($_POST['payment']))$this->db->query("UPDATE orders SET payment_id=? WHERE id=?", array($_POST['payment'], $_POST['id']));
					
					if(isset($_POST['product_id']))
                    for($i=0; $i<=count($_POST['product_id']) - 1; $i++)
					{
						/*if($_POST['discount'][$i]!=0)$sum=discount($_POST['discount'][$i], $sum=$_POST['price'][$i]*$_POST['amount'][$i]);
						else $sum=$_POST['price'][$i]*$_POST['amount'][$i];*/
						
						$sum=$_POST['price'][$i]*$_POST['amount'][$i];
						$total+=$sum;
						$amount+=$_POST['amount'][$i];
						$this->db->query("UPDATE `orders_product` SET `name`=?, `price`=?, `discount`=?, `amount`=?, `sum`=? WHERE `id`=?", 
										array($_POST['name'][$i],
											  $_POST['price'][$i],
											  $_POST['discount'][$i],
											  $_POST['amount'][$i],
											  $sum,
											  $_POST['product_id'][$i]
										));
					}
					if(!isset($_POST['mailer']))$_POST['mailer']=0;
                    $this->db->insert_id("UPDATE `".$this->table."` 
										  SET
											 `username`=?,
											 `status_id`=?,
											 `email`=?,
											  `phone`=?,
											  `address`=?,
											  `post_index`=?,
											 `country`=?,
											 `city`=?,
											 `code_discount`=?,
											 `mailer`=?,
											 `comment`=?,
											 `sum`=?,
											 `amount`=?
											 
                                          WHERE id=?", array(
                            $_POST['username'],
                            $_POST['status'],
                            $_POST['email'],
                            $_POST['phone'],
							$_POST['address'],
							$_POST['post_index'],
                            $_POST['country'],
                            $_POST['city'],
							$_POST['code_discount'],
							$_POST['mailer'],
							$_POST['comment'],
							$total,
							$amount,
                            $_POST['id'])
                    );
					
					
                    $message.= messageAdmin('Данные успешно сохранены');
                }
            }
            else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
		}
		return $message;
	}

    public function recalc($order_id)
    {
        $total=0;
        $res = $this->db->rows("SELECT * FROM orders_product WHERE orders_id=?", array($order_id));
        foreach($res as $row)
        {
            $sum=$row['price']*$row['amount'];
            $total+=$sum;
            $this->db->query("UPDATE `orders_product` SET `sum`=? WHERE `id`=?", array($sum, $row['id']));
        }
        $this->db->query("UPDATE `orders` SET `amount`=?, `sum`=? WHERE `id`=?", array($res[0]['amount'], $total, $order_id));

        return $total;
    }
	
	function orderProduct()
    {
		if($_POST['id'])
		{
			$data=array();
			$data['content']='<option value="0">Выберите товар...</option>';
			$res = Product::getObject($this->sets)->find(array('order'=>'tb.`sort` ASC, tb.id DESC', 
															   'select'=>'tb.*, tb_lang.name',
															   'where'=>'__tb.active:=1__ AND __tb3.catalog_id:='.$_POST['id'].'__',
															   'join'=>' LEFT JOIN product_catalog tb3 ON tb3.product_id=tb.id',
															   'type'=>'rows'));
			if(count($res)!=0)
			{
				foreach($res as $row)
				{
					$data['content'].='<option value="'.$row['id'].'">'.$row['name'].'</option>';
				}
			}
			else $data['content']='<option value="0">Товаров нет...</option>';
			
			return json_encode($data);
		}
    }
	
	function orderProductView()
    {
		if(isset($_POST['id'],$_POST['order_id']))
		{
			$data=array();
			$row = Product::getObject($this->sets)->find(array('select'=>'tb.*, tb_lang.name, tb_price.price, tb_price.discount', 
															   'join'=>"LEFT JOIN price tb_price ON tb_price.product_id=tb.id",
															   'where'=>'__tb.id:='.$_POST['id'].'__'));
															   
			$row2 = $this->db->row("SELECT id FROM orders_product WHERE orders_id=? AND product_id=?", array($_POST['order_id'], $_POST['id']));	
			$param = array($_POST['order_id'], $row['name'], $row['price'], $row['discount'], 1, $row['price'], $_POST['id']);		   
			
			if(!$row2)$this->db->query("INSERT INTO orders_product SET orders_id=?, name=?, price=?, discount=?, amount=?, `sum`=?, `product_id`=?", $param);
			else $this->db->query("UPDATE orders_product SET amount=amount+1, `sum`=`sum`*amount WHERE id=?", array($row2['id']));
			
			$total=0;
			$res = $this->db->rows("SELECT * FROM orders_product WHERE orders_id=?", array($_POST['order_id']));
			foreach($res as $row)
			{
				$sum=$row['price']*$row['amount'];
				$total+=$sum;
				
			}	
			$this->recalc($_POST['order_id']);
			$data['total']=$total;
			$data['res'] = $this->db->rows("SELECT tb.*, p.photo, p.url FROM orders_product tb
											LEFT JOIN product p
											ON p.id=tb.product_id WHERE orders_id=?", array($_POST['order_id']));
			$data['currency'] = $this->db->row("SELECT icon FROM currency WHERE `base`='1'");
			
			//$data['amount'] = count($res)-1;
			$data['total'] = 'Итого: '.$total;
			return $data;
		}
    }
	
	function del_product($id)
	{
		$row = $this->db->row("SELECT orders_id FROM orders_product WHERE id=?", array($id));
		$this->db->query("DELETE FROM `orders_product` WHERE id=?", array($id));
		$this->recalc($row['orders_id']);
	}
	
	function sendOrder($query, $info, $user_id)
	{
		if(!isset($_SESSION['order_info']))$_SESSION['order_info']=array();
		foreach($info as $key => $value) 
		{
			//echo $key.'='.$value.'<br />';	
			if($value!=''&$key!='')
			{
				$_SESSION['order_info'][$key]=$value;
			}
		}
		
		///Add order
		$date=date("Y-m-d H:i:s");
		$summa2=0;
		$summa3=0;
		$summa4=0;
		$uni_analytics='';	
		$text="<h4>Информация о отправителе</h4>
			   ФИО: {$info['name']}<br />
			   Контактный телефон: {$info['phone']}<br />";
				
		$q="INSERT INTO `".$this->table."` SET
						__username:=".$info['name']."__,
						__status_id:=1__,
						__date_add:=".$date."__,
						__currency:={$_SESSION['currency'][1]['id']}__,
						__rate:={$_SESSION['currency'][1]['rate']}__";
		
		if(isset($info['email']))
		{
			$q.=", __email:=".$info['email']."__";
			$text.="Контактный E-mail: {$info['email']}<br />";
		}
		if(isset($info['code_discount']))
		{
			$q.=", __code_discount:=".$info['code_discount']."__";
			$text.="Код скидки: {$info['code_discount']}<br />";
		}
		
		
		///////////Destination info
		$d_info='';
		if(isset($info['destination_name']))
		{
			$q.=", __destination_name:=".$info['destination_name']."__";
			$d_info.="ФИО получателя: {$info['destination_name']}<br />";
		}
		if(isset($info['destination_phone']))
		{
			$q.=", __destination_phone:=".$info['destination_phone']."__";
			$d_info.="Телефон получателя: {$info['destination_phone']}<br />";
		}
		if(isset($info['datetime']))
		{
			$q.=", __destination_date:=".$info['datetime']."__";
			$d_info.="Дата и время доставки: {$info['datetime']}<br />";
		}
		if(isset($info['address']))
		{
			$q.=", __address:=".$info['address']."__";
			$d_info.="Адрес: {$info['address']}<br />";
		}

		if(isset($info['city']))
		{
			$q.=", __city:=".$info['city']."__";
			$d_info.="Город: {$info['city']}<br />";
		}
		if(isset($info['text']))
		{
			$q.=", __comment:=".$info['text']."__";
			$d_info.="Примечание: <br />{$info['text']}<br /><br />";
		}

	
		
		if($d_info!='')$text.="<br /><h4>Информация о получателе</h4>".$d_info;
		///////////////
		
		if(isset($info['phone']))$q.=", __phone:=".$info['phone']."__";

		if($user_id!=0)$q.=", __user_id:=".$user_id."__";
		
		//echo $q;
		$order_id = $this->query($q, true);			
		$path=$_SERVER['HTTP_HOST'];
		

		if(isset($info['delivery']))
		{
			$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('delivery'));
			if($row)
			{
				$row = Delivery::getObject($this->sets)->find((int)$info['delivery']);
				$price = Numeric::viewPrice($row['price']);
				$summa4 +=$row['price'];	
				$summa2 =$price['price'];	
				$text.="Способ доставки: {$row['name']}<br />";
				$this->db->query("UPDATE orders SET delivery_id=? WHERE id=?", array($row['id'], $order_id));	
			}
		}
		if(isset($info['payment']))
		{
			$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('payment'));
			if($row)
			{
				$row = Payment::getObject($this->sets)->find((int)$info['payment']);
				$text.="Способ оплаты: {$row['name']}<br />";	
				$this->db->query("UPDATE orders SET payment_id=? WHERE id=?", array($info['payment'], $order_id));
			}
		}

		$text.="<br /><br />
		Товары:<br />
		<table cellpadding='0' cellspacing='0' width='700' style='border-collapse:collapse;'>
			<tr>
				<th width='60px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Артикул</th>
				<th style='border:1px solid #cccccc;' width='100'>Фото</th>
				<th style='border:1px solid #cccccc;'>Наименование</th>
				<th width='60px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Кол-во</th>
				<th width='100px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Цена</th>
				<th width='100px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Сумма</th>
			</tr>";
		$i=0;
		$res = $query;
		foreach($res as $row)
		{
			////////
			$price=Numeric::viewPrice($row['price'], $row['discount']);		
			$summa = Numeric::formatPrice($price['cur_price'] * $row['amount']);
			$summa2 +=$price['base_price'] * $row['amount'];
			$summa4 +=$price['base_price'] * $row['amount'];
			
			if($row['photo_basket']!=''&&file_exists($row['photo_basket']))
			{
                $src='/'.$row['photo_basket'];
			}
            elseif(file_exists($row['photo']))
			{
                $src='/'.$row['photo'];
			}
            else $src='/files/default.jpg';
			
			
			$color="";
			if(isset($row['color'],$row['size'])&&($row['color']!=''||$row['size']!=''))$color=" (".$row['color'].", ".$row['size'].")";

            if($row['color']==NULL)$row['color']='';
            if($row['size']==NULL)$row['size']='';
			///Add orders products
			$query="INSERT INTO orders_product SET color=?, size=?, product_id=?, code=?, name=?, price=?, sum=?, discount=?, amount=?, photo=?, orders_id=?";
			$this->db->query($query, array($row['color'], $row['size'], $row['id'], $row['code'], $row['name'].$color, $price['base_price'], $price['base_price'] * $row['amount'], $row['discount'], $row['amount'], substr($src,1), $order_id));
			
			if(isset($this->settings['uni_analytics'])&&$this->settings['uni_analytics']==1)
			{
				$uni_analytics.="ga('ecommerce:addItem', {
								 'id': '".$order_id."',                      // ID заказа
								 'name': '".$row['name'].$color."',          // Название товара
								 'sku': '".$row['code']."',                  // Артикул или SKU
								 'category': 'Скидка ".$row['discount']."%', // Размер, модель, категория или еще какая-то информация
								 'price': '".$price['base_price']."',        // Стоимость товара
								 'quantity': '".$row['amount']."'            // Количество товара
								});
								
								";
			}

            $price_text = '';
            if($row['discount']!=0)
                $price_text.="<br /><font style='color:red;'>Скидка ".$row['discount']."%</font><br />
										<font style='text-decoration:line-through;'>".$price['old_price']."</font>";

			$text.="<tr>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$row['code']."</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>
							<a href='http://$path/product/".$row['url']."'><img src='http://".$path.$src."' width='100' /></a>
						</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>
							<a href='http://".$path."/product/".$row['url']."'>".$row['name']." $color</a>
						</td>
			            <td>
			                {$price['price']}
			                {$price_text}
			            </td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$row['amount']."</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$summa."</td>
					</tr>";	
			$i+=$row['amount'];		
		}
		
		if(isset($this->settings['uni_analytics'])&&$this->settings['uni_analytics']==1)
		{
			$uni_analytics="ga('require', 'ecommerce', 'ecommerce.js');
								ga('ecommerce:addTransaction', {
								
								 'id': '".$order_id."',                   	   // ID заказа
								 'affiliation': '".$_SERVER['HTTP_HOST']."',   // Название магазина
								 'revenue': '".$summa2."',              	   // Общая стоимость заказа
								 'shipping': '0',                              // Стоимость доставки
								 'tax': ''                     	 			   // Налог
								
								});".$uni_analytics."
								
								ga('ecommerce:send');             // Отправка данных
							";
		}
			
		$text.="<tr>
					<td colspan='6' align='right'>
						<div style='font-size:14px;'>
							<div style='font-size:18px;'>Итого: ".Numeric::formatPrice($summa2)."</div>
						</div>
					</td>
				</tr>
			</table>";

		$this->db->query("UPDATE `orders` SET `sum`=?, `amount`=? where `id`=?", array($summa2, $i, $order_id));
		
		
		if(isset($this->settings['notify_users'])&&$this->settings['notify_users']==1)
		{
			//////Send to the admin
			Mail::send($this->settings['sitename'], // имя отправителя
							"info@".$_SERVER['HTTP_HOST'], // email отправителя
							$this->settings['sitename'], // имя получателя
							$this->settings['email'], // email получателя
							"utf-8", // кодировка переданных данных
							"utf-8", // кодировка письма
							"Новый заказ на сайте ".$this->settings['sitename'], // тема письма
                            "<h2>Заказ №{$order_id}</h2>".$text // текст письма
							);//echo $text;
		}
						
		//////Send to the client
		if(isset($info['email']))
		{
			$letter = $this->getBlock(11);
			$letter = str_replace('{{name}}', $info['name'], htmlspecialchars_decode($letter['body']));
			$letter = str_replace('{{date}}', $date, $letter);
			$letter = str_replace('{{order_id}}', $order_id, $letter);
			$letter = str_replace('{{products}}', $text, $letter);
			
			Mail::send($this->settings['sitename'], // имя отправителя
							"info@".$_SERVER['HTTP_HOST'], // email отправителя
							$info['name'], // имя получателя
							$info['email'], // email получателя
							"utf-8", // кодировка переданных данных
							"utf-8", // кодировка письма
							"Вы оформили заказ на сайте ".$this->settings['sitename'], // тема письма
							$letter // текст письма
							);//echo $text;	
			
			$where="WHERE ".$this->get_user_id('bascket');
			$this->db->query("DELETE FROM `bascket` $where");
		}
		
		return $uni_analytics;
	}

    function sendOrder2($order_id, $type=0)
    {
        $info = $this->db->row("SELECT orders.*, d.name AS delivery, p.name AS payment
                                FROM orders

                                LEFT JOIN ru_delivery d
                                ON d.delivery_id=orders.delivery_id

                                LEFT JOIN ru_payment p
                                ON p.payment_id=orders.payment_id

                                WHERE id='$order_id'");

        $products = $this->db->rows("SELECT op.*, p.photo, p.brend_id, p.url
                                     FROM orders_product op

                                     LEFT JOIN product p
                                     ON p.id=op.product_id

                                     WHERE op.orders_id='$order_id'");
        $d_info="";
        ///Add order
        $date=date("Y-m-d H:i:s");
        $text="<h4>Информация о отправителе</h4>
			   ФИО: {$info['username']}<br />
			   Контактный телефон: {$info['phone']}<br />";

        if(isset($info['email'])&&$info['email']!='')
        {
            $text.="Контактный E-mail: {$info['email']}<br />";
        }
        if(isset($info['code_discount'])&&$info['code_discount']!='')
        {
            $text.="Код скидки: {$info['code_discount']}<br />";
        }

        if(isset($info['address'])&&$info['address']!='')
        {
            $d_info.="Адрес: {$info['address']}<br />";
        }

        if(isset($info['city'])&&$info['city']!='')
        {
            $d_info.="Город: {$info['city']}<br />";
        }
        if(isset($info['text'])&&$info['text']!='')
        {
            $d_info.="Примечание: <br />{$info['text']}<br /><br />";
        }

        if(isset($info['mailer'])&&$info['mailer']==1)
        {
            $d_info.="Рассылка акций: <br />ДА<br /><br />";
        }

        $discount_text='';
        if(isset($info['discount'])&&$info['discount']>0)
        {
            $discount_text='<br /><span style="color:red;">Скидка: '.$info['discount'].'%</span>';
        }
        if(isset($info['discount2'])&&$info['discount2']>0)
        {
            $discount_text.='<br /><span style="color:red;">Скидка накопительная: '.$info['discount2'].'%</span>';
        }



        if($d_info!='')$text.="<br /><h4>Информация о получателе</h4>".$d_info;
        ///////////////


        $order_id = $info['id'];
        $path=$_SERVER['HTTP_HOST'];


        if(isset($info['delivery'])&&$info['delivery']!='')
        {
            $text.="Способ доставки: {$info['delivery']}<br />";
        }
        if(isset($info['payment'])&&$info['payment']!='')
        {
            $text.="Способ оплаты: {$info['payment']}<br />";
        }

        $text.="<br /><br />
		Товары:<br />
		<table border='1' cellpadding='0' cellspacing='0' width='700' style='border-collapse:collapse;'>
			<tr>
				<th width='60px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Артикул</th>
				<th style='border:1px solid #cccccc;' width='100'>Фото</th>
				<th style='border:1px solid #cccccc;'>Название товара</th>
				<th width='60px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Кол-во</th>
				<th style='border:1px solid #cccccc;' width='100'>Цена</th>
				<th width='100px' style='text-align:center; border:1px solid #cccccc; padding:10px;'>Сумма</th>
			</tr>";
        $i=0;
        $total=0;
        foreach($products as $row)
        {
            ////////
            $price=$row['price'];
            $summa = $row['sum'];
            $total+=$row['sum'];

            $src="/".$row['photo'];

            $status='';
            $name=$row['name'];
            if($row['brend_id']==4)$status='<div style="color: #317edd; font-size:10px; font-style:italic; margin-top:10px;">Под заказ</div>';
            elseif($row['brend_id']==3)
            {
                $status='<div style="color: #317edd; font-size:10px; font-style:italic; margin-top:10px;">Сообщить цену клиенту </div>';
                $name.=' (Сообщить цену клиенту)';
            }

            $text.="<tr>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$row['code']."</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>
							<a href='http://$path/product/".$row['url']."'><img src='http://".$path.$src."' width='100' /></a>
						</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>
							<a href='http://".$path."/product/".$row['url']."'>".$row['name']."</a>$status
						</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$row['amount']."</td>
						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>";

            $text.="{$price}
						</td>

						<td style='text-align:center; border:1px solid #cccccc; padding:10px;'>".$summa."</td>
					</tr>";
            $i+=$row['amount'];
        }
        if($discount_text!='')$discount_text.='<br /><b>Итого к оплате: '.Numeric::formatPrice($info['sum']).'</b>';

        $text.="<tr>
					<td colspan='7' align='right' style='border:1px solid #fff;'>
						<div style='font-size:15px;'>
							".$this->translation['all2'].": ".Numeric::formatPrice($total)."
							{$discount_text}
						</div>
					</td>
				</tr>
			</table>";
        $text="<h2>№ заказа $order_id</h2>".$text;

        if($type==0)
        {
            $subject='Ваш заказ изменен на сайте '.$_SERVER['HTTP_HOST'];
            $letter = $this->getBlock(11);
        }
        else{
            $subject='Выписан счет на сайте '.$_SERVER['HTTP_HOST'];
            $letter = $this->getBlock(18);
        }




        $letter = str_replace('{{name}}', $info['username'], htmlspecialchars_decode($letter['body']));
        $letter = str_replace('{{date}}', $date, $letter);
        $letter = str_replace('{{order_id}}', $order_id, $letter);
        $letter = str_replace('{{products}}', $text, $letter);

        $link='http://'.$_SERVER['HTTP_HOST'].'/orders/payment/orderid/'.$order_id;
        $letter = str_replace('{{link}}', '<a href="'.$link.'" target="_blank">'.$link.'</a>', $letter);


        if(isset($this->settings['notify_orders'])&&$this->settings['notify_orders']==1)
        {
            //////Send to the admin
            Mail::send($this->settings['sitename'], // имя отправителя
                "info@".$_SERVER['HTTP_HOST'], // email отправителя
                $this->settings['sitename'], // имя получателя
                $this->settings['email'], // email получателя
                "utf-8", // кодировка переданных данных
                "utf-8", // кодировка письма
                $subject.". Заказ №{$order_id}", // тема письма
                $letter // текст письма
            );//echo $text;
        }

        //////Send to the client
        if(isset($info['email']))
        {
            Mail::send($this->settings['sitename'], // имя отправителя
                "info@".$_SERVER['HTTP_HOST'], // email отправителя
                $info['username'], // имя получателя
                $info['email'], // email получателя
                "utf-8", // кодировка переданных данных
                "utf-8", // кодировка письма
                $subject.". Заказ №{$order_id}", // тема письма
                $letter // текст письма

            );//echo $text;

        }
    }
	
	function updatecart($id, $cnt, $action)
	{
		if($action=='update')
		{
			$where="AND ".$this->get_user_id();
			$id = explode('_', $id);
			$this->db->query("UPDATE bascket b SET amount=? WHERE id=? $where", array($cnt, $id[1]));	
		}
		elseif($action=='delete')
		{
			$this->db->query("DELETE FROM bascket WHERE id=?", array($id));	
		}

		return $this->db->rows($this->query_basket());
	}
	
	function incart($id, $size, $color, $photo, $amount=1)
	{
		$where="AND ".$this->get_user_id();
		if($amount=='undefined')$amount=1;
		if($photo=='undefined')$photo='';
		
		if($size!='undefined'&&$size!='')$where.=" AND size='$size' ";
		if($color!='undefined'&&$color!='')$where.=" AND color='$color' ";
		
		$row=$this->db->row("SELECT `id` FROM `bascket` b WHERE `price_id`=? $where", array($id));
		if(!$row)
		{
			if($amount==0)$amount = 1;
			$date = date("Y-m-d H:i:s");
			$row = $this->db->row("SELECT id, product_id, price, code, discount FROM `price` WHERE id=?", array($id));
			
			if($row['discount']!=0)$row['price']=Numeric::discount($row['discount'], $row['price']);
			$param = array($row['price'], $_COOKIE['session_id'], $row['product_id'], $row['id'], $row['code'], $row['discount'], $date, $amount, $size, $color, $photo);
			
			$where="";
			if(isset($_SESSION['user_id']))$where=", user_id='{$_SESSION['user_id']}'";
			
			$this->db->query("INSERT into bascket SET price=?, session_id=?, product_id=?, price_id=?, code=?, discount=?, date=?, amount=?, size=?, color=?, photo=? $where", $param);
		}
		else{
			$this->db->query("UPDATE bascket b SET amount=amount+? WHERE price_id=? AND size=? AND color=? $where", array($amount, $id, $size, $color));
		}
		$vars = $this->bascket();
		$vars['product'] = $this->db->rows($this->query_basket());
		$vars['translate']=$this->translation;
		$view =  new View($this->registry);
        $vars['content'] = $view->Render('cart_popup_tb.phtml', $vars);
		return $vars;
	}
	
	///Bascket shop cart
	function bascket($with_discount=true)
	{
		$vars = array();
		$where = "WHERE ".$this->get_user_id();
		//$vars['currency'] = $this->currency();
		$res = $this->db->rows("SELECT tb_price.`price`, tb_price.`discount`, b.amount
							    FROM `bascket` b 
			   
							    LEFT JOIN product p
							    ON p.id=b.product_id
								
							    LEFT JOIN `price` tb_price
						 	    ON `tb_price`.id=b.price_id
							
							    $where
								GROUP BY b.id
								");
		$sum=0;
		$amount=0;
		foreach($res as $row)
		{
			$price = Numeric::viewPrice($row['price'], $row['discount']);
			$sum+=$price['cur_price'] * $row['amount'];
			$amount+=$row['amount'];
		}
		
		$vars['count'] = $amount;
		$vars['total'] = Numeric::formatPrice($sum);
		return $vars;
	}
	
	function removeFromBasket($id)
    {
		$where="AND ".$this->get_user_id('bascket');
        $this->db->query("DELETE FROM `bascket` WHERE `id` = ? $where", array($id));
		return $this->bascket();
    }
	
	function refreshBasket($count, $pid)
    {
		$where="AND ".$this->get_user_id('bascket');
        $this->db->query("UPDATE `bascket` SET amount=? WHERE id=? $where", array($count, $pid));
		$row = $this->db->row("SELECT `price`, `amount` FROM `bascket` WHERE id='$pid' $where");
		$sum = Numeric::formatPrice($row['price'] * $row['amount']);
        return array_merge($this->bascket(), array('sum'=>$sum));
    }
	
	function showBasket()
    {
        $total=0;
		$vars = $this->bascket(false);
		$vars['product'] = $this->db->rows($this->query_basket());
		$vars['translate']=$this->translation;
        return $vars;
    }
	
	function showUserInfo()
    {
        if(isset($_SESSION['user_id']))
		{
			$vars['translate']=$this->translation;
            $vars['user']=$this->db->row("SELECT * FROM `users` WHERE id=?", array($_SESSION['user_id']));
        }
        return $vars;
    }
	
	function showOrders()
    {
        if(isset($_SESSION['user_id']))
		{
			$vars['translate']=$this->translation;
            $vars['orders'] = $this->db->rows("SELECT tb.id, tb.sum, tb.date_add, tb2.name as status
												FROM `orders` tb
												
												LEFT JOIN orders_status tb2
												ON tb.status_id=tb2.id
		
												WHERE tb.user_id=?
												ORDER BY tb.`date_add` DESC", array($_SESSION['user_id']));

            $vars['user']=$this->db->row("SELECT * FROM `users` WHERE id=?", array($_SESSION['user_id']));
        }
        return $vars;
    }
	
	function showOrdering()
    {
        if(isset($_SESSION['user_id']))
		{
            $vars['user']=$this->db->row("SELECT * FROM `users` WHERE id=?", array($_SESSION['user_id']));
        }
		$vars['translate']=$this->translation;

        ////Delivery
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('delivery'));
		if($row)$vars['delivery'] = Delivery::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		
		////Payment
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('payment'));
		if($row)$vars['payment'] = Payment::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
        return $vars;
    }
	
	function getOrder($id)
    {
        $vars['order'] = $this->db->row("SELECT tb.*, tb2.name, tb3.name as delivery
										 FROM orders tb
	
										 LEFT JOIN orders_status tb2
										 ON tb.status_id=tb2.id
	
										 LEFT JOIN ".$this->registry['key_lang']."_delivery tb3
										 ON tb.delivery_id=tb3.delivery_id
	
										 WHERE tb.id=? AND tb.user_id=?", array($id, $_SESSION['user_id']));

        if(isset($vars['order']['id']))
        {
			$vars['translate']=$this->translation;
            $vars['product'] = $this->db->rows("SELECT * FROM orders_product WHERE orders_id=?", array($vars['order']['id']));
			return $vars;
        }
    }
	
	function allOrders()
    {
		$vars=array();
		$vars['orders'] = $this->db->rows("SELECT
											tb.id,
											tb.sum,
											tb.date_add,
											tb2.name
										 FROM `orders` tb
											LEFT JOIN
												orders_status tb2
											ON tb.status_id=tb2.id
		
										 WHERE tb.user_id=?
										 ORDER BY tb.`date_add` DESC", array($_SESSION['user_id']));

        $vars['translate'] = $this->translation;
        echo $view->Render('all_orders.phtml', $vars);
    }

    function saveProfile($data)
    {
		if(isset($_SESSION['user_id']))
		{
			$pass_q='';
			$err='';
			if($data['new_pass']!=''&&$data['old_pass']!='')
			{
				$row = $this->db->row("SELECT id FROM `users` WHERE `id`=? AND pass=?", array($_SESSION['user_id'], md5($data['old_pass'])));
				if($row)
				{
					$pass_q=", pass='".md5($data['new_pass'])."'";	
				}
			}

			$err.=Validate::check($data['name']);
			if($err=='')
			{
				$param = array($data['name'], $data['phone'], $data['city'], $data['address'], $data['post_index'], $_SESSION['user_id']);
				$this->db->query("UPDATE `users` SET name=?, phone=?, city=?, address=?, post_index=? $pass_q WHERE id=?", $param);
				return "<div class='done'>".$this->translation['save_profile']."</div>";
			}
			else{
				return $err;
			}
		}
    }

	function get_user_id($tb='b')
    {
		if(isset($_SESSION['user_id']))$ssid = "($tb.user_id='".$_SESSION['user_id']."' OR $tb.session_id='".$_COOKIE['session_id']."')";
        else $ssid = "$tb.session_id='".$_COOKIE['session_id']."'";
		return $ssid;
	}
	
	function query_basket()
	{
		$where="WHERE ".$this->get_user_id('b');
		$q="SELECT b.`amount`,
						b.`id` as cart_id,
						p.id,
						b.code,
						p.url,
						tb_price.`price`,
						p.`photo`,
						b.photo AS photo_basket,
						tb_price.discount,
						p2.name,
						size.name as size,
						color.name as color
						
			  FROM `bascket` b
			  
			  LEFT JOIN product p
			  ON p.id=b.product_id
								
			  LEFT JOIN `price` tb_price
			  ON `tb_price`.id=b.price_id
								
			  LEFT JOIN ".$this->registry['key_lang']."_product p2
			  ON p.id=p2.product_id
	
			  LEFT JOIN ".$this->registry['key_lang']."_params size
			  ON b.size = size.params_id
	
			  LEFT JOIN ".$this->registry['key_lang']."_params color
			  ON b.color = color.params_id
	
			  $where
			  GROUP BY b.id
			  ";
		return $q;	  
	}
	
	function last_order()
	{
		return $this->db->row("SELECT p.url, p.photo, p2.name, o.city
								  FROM `orders` o
								  
								  LEFT JOIN orders_product op
								  ON op.orders_id=o.id
								  
								  LEFT JOIN product p
								  ON op.product_id=p.id
								  
								  LEFT JOIN ".$this->registry['key_lang']."_product p2
								  ON p.id=p2.product_id
								  
								  WHERE (o.status_id!='3' OR o.status_id!='5') 
								  ORDER BY o.date_add DESC");	
	}
	
	function incomplete($user_id=0)
	{
		## Start Statistics
        $cur_start_date=getdate(mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
		$cur_end_date = date("Y-m-d H:i:s");
		if(strlen($cur_start_date['mon'])==1)$cur_start_date['mon'] = '0'.$cur_start_date['mon'];
		if(strlen($cur_start_date['mday'])==1)$cur_start_date['mday'] = '0'.$cur_start_date['mday'];
		$cur_start_date = $cur_start_date['year'].'-'.$cur_start_date['mon'].'-'.$cur_start_date['mday'];

		
		if(isset($_POST['start']))
		{
			$_SESSION['date_start'] = $_POST['start'];
			$_SESSION['date_end'] = $_POST['end'];
		}
		elseif(!isset($_SESSION['date_start']))
		{
			$_SESSION['date_start'] = $cur_start_date;
			$_SESSION['date_end'] = $cur_end_date;
		}

		$vars['message'] = '';
		$vars['name'] = 'Незавершенные заказы';
		if(isset($this->params['delete'])||isset($_POST['delete']))
		{
			$vars['message'] = $this->delete('bascket');
		}
		
		$where='';
		if($user_id!=0)$where="AND b.user_id='{$user_id}'";
		$vars['product'] = $this->db->rows("SELECT p.*, p2.name, b.*, u.name AS username, u.email
											FROM `bascket` b 
										   
																		   
											LEFT JOIN product p
											ON p.id=b.product_id
											
											LEFT JOIN ".$this->registry['key_lang_admin']."_product p2
											ON p.id=p2.product_id
											
											LEFT JOIN `price` tb_price
											ON `tb_price`.id=b.price_id
											
											LEFT JOIN users u 
											ON u.id=b.user_id 
											
											WHERE b.`date` BETWEEN '{$_SESSION['date_start']}' AND '{$_SESSION['date_end']}' $where
											
											GROUP BY b.id
											ORDER BY session_id DESC, b.date DESC
											");
											
		return $vars;									
	}


    public function add_orders_status()
    {
        $message='';
        if(isset($this->registry['access']))$message = $this->registry['access'];
        else
        {
            $this->db->query("INSERT INTO `orders_status` SET `name`='Новый статус'");
            $message .= messageAdmin('Данные успешно сохранены');
        }
        return $message;
    }

    public function save_orders_status()
    {
        $message='';
        if(isset($this->registry['access']))$message = $this->registry['access'];
        else
        {
            if(isset($_POST['name'], $_POST['id']))
            {
                $count=count($_POST['id']) - 1;
                for($i=0; $i<=$count; $i++)
                {
                    $param = array($_POST['name'][$i], $_POST['id'][$i]);
                    $this->db->query("UPDATE `orders_status` SET `name`=? WHERE id=?", $param);
                }
                $message .= messageAdmin('Данные успешно сохранены');
            }
            else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
        }
        return $message;
    }


    public function orderInXml($order_id)
    {
        $res = $this->db->rows("SELECT tb.*, tb2.*, d.name AS delivery, p.name AS payment
                                FROM `orders_product` tb


                                LEFT JOIN orders tb2
                                ON tb2.id=tb.orders_id

                                LEFT JOIN ru_delivery d
                                ON d.delivery_id=tb2.delivery_id

                                LEFT JOIN ru_payment p
                                ON p.payment_id=tb2.payment_id

                                WHERE orders_id='$order_id'

                                GROUP BY tb.id
                                ORDER BY name ASC
                                ");

        $xml = new DomDocument('1.0', 'utf-8');
        $order = $xml->appendChild($xml->createElement('order'));

        $xml_order_pid = $order->appendChild($xml->createElement('id'));
        $xml_order_pid->appendChild($xml->createTextNode($order_id));

        $xml_date = $order->appendChild($xml->createElement('date'));
        $xml_date->appendChild($xml->createTextNode($res[0]['date_add']));

        $xml_username = $order->appendChild($xml->createElement('username'));
        $xml_username->appendChild($xml->createTextNode($res[0]['username']));

        $xml_userid = $order->appendChild($xml->createElement('userid'));
        $xml_userid->appendChild($xml->createTextNode($res[0]['user_id']));

        $xml_email = $order->appendChild($xml->createElement('email'));
        $xml_email->appendChild($xml->createTextNode($res[0]['email']));

        $xml_phone = $order->appendChild($xml->createElement('phone'));
        $xml_phone->appendChild($xml->createTextNode($res[0]['phone']));

        $xml_city = $order->appendChild($xml->createElement('city'));
        $xml_city->appendChild($xml->createTextNode($res[0]['city']));

        $xml_address = $order->appendChild($xml->createElement('address'));
        $xml_address->appendChild($xml->createTextNode($res[0]['address']));

        $xml_delivery = $order->appendChild($xml->createElement('delivery'));
        $xml_delivery->appendChild($xml->createTextNode($res[0]['delivery']));

        $xml_payment = $order->appendChild($xml->createElement('payment'));
        $xml_payment->appendChild($xml->createTextNode($res[0]['payment']));

        $products = $order->appendChild($xml->createElement('products'));
        foreach($res as $row)
        {
            // Adding data to XML
            $product = $products->appendChild($xml->createElement('product'));

            $xml_product_id = $product->appendChild($xml->createElement('id'));
            $xml_product_id->appendChild($xml->createTextNode($row['product_id']));

            $xml_product_name = $product->appendChild($xml->createElement('name'));
            $xml_product_name->appendChild($xml->createTextNode($row['name']));

            $xml_product_ones = $product->appendChild($xml->createElement('code'));
            $xml_product_ones->appendChild($xml->createTextNode($row['code']));

            $xml_product_price = $product->appendChild($xml->createElement('price'));
            $xml_product_price->appendChild($xml->createTextNode($row['price']));

            $xml_product_amount = $product->appendChild($xml->createElement('amount'));
            $xml_product_amount->appendChild($xml->createTextNode($row['amount']));

            $xml_product_sum = $product->appendChild($xml->createElement('sum'));
            $xml_product_sum->appendChild($xml->createTextNode($row['price'] * $row['amount']));

            $xml_product_size = $product->appendChild($xml->createElement('size'));
            $xml_product_size->appendChild($xml->createTextNode($row['size']));

            $xml_product_color = $product->appendChild($xml->createElement('color'));
            $xml_product_color->appendChild($xml->createTextNode($row['color']));
        }

        $xml->formatOutput = true;
        $xml = $xml->saveXML();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=order_'.$order_id.'.xml');

        echo $xml;
        exit();
    }
}
?>