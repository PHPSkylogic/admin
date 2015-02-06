<?php
/*
 * вывод каталога компаний и их данных
 */
class OrdersController extends BaseController{

    protected $params;
    protected $db;

    function  __construct($registry, $params)
    {
		parent::__construct($registry, $params);
        $this->tb = "orders";
		$this->tb_b = "bascket";
		$this->tb_p = "product";
		$this->tb_p_lang = $this->key_lang.'_product';
        $this->registry = $registry;
        $this->orders = new Orders($this->sets);
    }

    public function indexAction()
    {
		/*$res = $this->db->rows("SELECT `file`, id FROM comments WHERE `file`!=''");
		foreach($res as $row)
		{
			$file=explode('/',$row['file']);
			IMAGES::resizeImage("files/comments/".$file[2], '', "files/comments/{$row['id']}.jpg", 435, 326);
		}
		*/
		if(!isset($_SESSION['catalog_contin']))$_SESSION['catalog_contin']=LINK."/catalog/all";
		//////////Update amount products
		if(isset($_POST['amount']))
		{
			for($i=0; $i<=count($_POST['id']) - 1; $i++)
			{
				if($_POST['amount'][$i]<=0)$this->db->query("DELETE FROM `bascket` WHERE `id`=?", array($_POST['id'][$i]));
				else $this->db->query("UPDATE `bascket` SET `amount`=? WHERE `id`=?", array($_POST['amount'][$i], $_POST['id'][$i]));
			}	
		}
		
		///Delete products
		if(isset($this->params['del']))
		{
			$this->db->query("DELETE FROM `bascket` WHERE `id`=?", array($this->params['del']));
		}
		
		$vars = $this->orders->showBasket();
		$vars['message'] = '';
		if(count($vars['product'])!=0)
		{
			$settings = Registry::get('user_settings');
			////Delivery
			$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('delivery'));
			if($row)$vars['delivery'] = $this->db->rows("SELECT * 
														 FROM `delivery` tb
														 
														 LEFT JOIN ".$this->key_lang."_delivery tb2
														 ON tb.id=tb2.delivery_id
														 
														 WHERE active=? 
														 ORDER  BY sort ASC", array(1));
			
			////Payment
			$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('payment'));
			if($row)$vars['payment'] = $this->db->rows("SELECT * 
														FROM `payment` tb
														
														LEFT JOIN ".$this->key_lang."_payment tb2
														ON tb.id=tb2.payment_id
														 
														WHERE active=? 
														ORDER  BY sort ASC", array(1));
														
														
			$user_id = 0;
			if(isset($_SESSION['user_id']))
			{
				$row = $this->db->row("SELECT * FROM `users` WHERE id=?", array($_SESSION['user_id']));
				$vars['discount'] = $row['discount'];
				$user_id = $row['id'];
				$vars['user_info'] = $row;
			}
			else{
				if(isset($_SESSION['order_info']))
				{
					foreach($_SESSION['order_info'] as $key => $value)
					{
						$vars['user_info'][$key] = $value;	
					}
				}
			}
			if(isset($_POST['name_sign']))
			{
				
				$where="";
				$error="";
				$error.=Validate::check($_POST['email_sign'], $this->translation, 'email');
				$error.=Validate::check($_POST['name_sign'], $this->translation);
				if($error=="")
				{
					if(!isset($_SESSION['user_id']))/////New users
					{
						$row=$this->db->row("SELECT id FROM users WHERE email=?", array($_POST['email_sign']));
						if(!$row)
						{
							$id = Users::getObject($this->sets)->signUp();
							if(isset($id['insert_id']))$user_id = $id['insert_id'];
						}
						else{
							$user_id = $row['id'];
						}
					}
					elseif(isset($_SESSION['user_id']))
					{
						if(!isset($_POST['mailer']))$_POST['mailer']=0;
						$this->db->query("UPDATE users SET 
															phone='{$_POST['phone_sign']}', 
															address='{$_POST['address_sign']}', 
															city='{$_POST['city_sign']}', 
															code_discount='{$_POST['code_discount']}', 
															name='{$_POST['name_sign']}',
															mailer='{$_POST['mailer']}'
										  WHERE id=?", array($_SESSION['user_id']));	
					}
					
					$info['name']=$_POST['name_sign'];
					if(isset($_POST['email_sign']))$info['email']=$_POST['email_sign'];
					if(isset($_POST['phone_sign']))$info['phone']=$_POST['phone_sign'];
					if(isset($_POST['address_sign']))$info['address']=$_POST['address_sign'];
					if(isset($_POST['mailer']))$info['mailer']=$_POST['mailer'];
					if(isset($_POST['city_sign']))$info['city']=$_POST['city_sign'];
					if(isset($_POST['post_index_sign']))$info['post_index']=$_POST['post_index_sign'];
					if(isset($_POST['destination_name']))$info['destination_name']=$_POST['destination_name'];
					if(isset($_POST['destination_phone']))$info['destination_phone']=$_POST['destination_phone'];
					if(isset($_POST['datetime']))$info['datetime']=$_POST['datetime'];
					if(isset($_POST['code_discount']))$info['code_discount']=$_POST['code_discount'];
					if(isset($_POST['delivery']))$info['delivery']=$_POST['delivery'];
					if(isset($_POST['payment']))$info['payment']=$_POST['payment'];
					if(isset($_POST['text']))$info['text']=$_POST['text'];

					$data['uni_analytics_code'] = $this->orders->sendOrder($vars['product'], $info, $user_id);
					$vars['send']=true;
					
					$body = $this->model->getBlock(3);
					$vars['message']=htmlspecialchars_decode($body['body']);
				}
				else $vars['message'] = $error;
			}
			
			if(isset($_POST['phone_fast']))
			{
				
				$where="";
				$error="";
				$error.=Validate::check($_POST['email_fast'], $this->translation, 'email');
				if($error=="")
				{
					if(!isset($_SESSION['user_id']))/////New users
					{
						$row=$this->db->row("SELECT id FROM users WHERE email=?", array($_POST['email_fast']));
						if(!$row)
						{
							$id = Users::getObject($this->sets)->signUp();
							if(isset($id['insert_id']))$user_id = $id['insert_id'];
						}
					}
					
					$info['name']='';
					$info['text']='Короткая форма заказа';
					if(isset($_POST['email_fast']))$info['email']=$_POST['email_fast'];
					if(isset($_POST['phone_fast']))$info['phone']=$_POST['phone_fast'];

					$this->orders->sendOrder($vars['product'], $info, $user_id);
					$vars['send']=true;
					
					$body = $this->model->getBlock(3);
					$vars['message']=htmlspecialchars_decode($body['body']);
				}
				else $vars['message'] = $error;
			}
			
			$data['styles'] = array('validationEngine.jquery.css', 'user.css', 'catalog.css');
			if($this->key_lang=='ru')$scr='jquery.validationEngine-ru.js';
			else $scr='jquery.validationEngine-en.js';
			$data['scripts'] = array('jquery.validationEngine.js', 'bootstrap-tooltip.js',$scr);
		}
		else{
			$vars['message'] = '<div class="err">'.$this->translation['cart_empty'].'</div>';
		}
		$data['content'] = $this->view->Render('cart.phtml', $vars);
		return $this->Index($data);
    }
	
	
	///Bascket shop cart
	function bascketAction()
	{
		$vars = $this->orders->bascket();
		$vars['translate']=$this->translation;
		$vars['settings']=$this->settings;
		echo $this->view->Render('bascket.phtml', $vars);
	}
	
	//////Put in shop cart
	function updatecartAction()
	{
		if(isset($_POST['id'], $_POST['cnt'], $_POST['action']))
		{
			$vars['translate']=$this->translation;
			$vars['product'] = $this->orders->updatecart($_POST['id'], $_POST['cnt'], $_POST['action']);
			$data['content'] = $this->view->Render('cart_popup_tb.phtml', $vars);
			echo json_encode($data);
		}
	}
	
	//////Put in shop cart
	function incartAction()
	{
		if(isset($_POST['id'], $_POST['amount']))
		{
			echo json_encode($this->orders->incart($_POST['id'], end(explode('-',$_POST['size'])), end(explode('-',$_POST['color'])), $_POST['photo'], $_POST['amount']));
		}
	}
	
	function loadcabinetAction()
	{
		if(isset($_POST['type']))
		{
			$arr=array();
			if($_POST['type']=='cabinet-show')
			{
				$vars = $this->orders->showUserInfo();
				$arr['content'] = $this->view->Render('cabinet-profile.phtml', $vars);
			}
			elseif($_POST['type']=='orders-show')
			{
				$vars = $this->orders->showOrders();
				$arr['content'] = $this->view->Render('cabinet-orders.phtml', $vars);
			}
			elseif($_POST['type']=='ordering-show')
			{
				$vars = $this->orders->showOrdering();
				$arr['content'] = $this->view->Render('cabinet-ordering.phtml', $vars);
			}
			else{
				$vars = $this->orders->showBasket();
				$arr['content'] = $this->view->Render('cabinet-basket.phtml', $vars);
				$arr['count'] = $vars['count'];
				$arr['total'] = $vars['total'];
			}    
			echo json_encode($arr);
		}
	}
	
	function changeviewAction()
	{
		if(isset($_POST['id'],$_POST['type']))
		{
			if($_POST['type']==1)$_SESSION['view_basket']=$_POST['id'];
			else{
				$_SESSION['expand_basket']=$_POST['id'];
			}
		}
	}
	
	function showbasketAction()
    {
		if(!isset($_SESSION['view_basket']))$_SESSION['view_basket']='gridview';
		if(!isset($_SESSION['expand_basket']))$_SESSION['expand_basket']=1;
		
        $vars['translate'] = $this->translation;
        echo $this->view->Render('cabinet.phtml', $vars);
    }
	
	function refreshbasketAction()
    {
		if(isset($_POST['count'], $_POST['pid']))
		{
        	echo json_encode($this->orders->refreshBasket($_POST['count'], $_POST['pid']));
		}
    }
	
	function removefrombasketAction()
    {
		if(isset($_POST['id']))
		{
        	echo json_encode($this->orders->removeFromBasket($_POST['id']));
		}
    }

    function getorderAction()
    {
		if(isset($_POST['id']))
		{
			$vars = $this->orders->getOrder($_POST['id']);
			echo $this->view->Render('cabinet-order.phtml', $vars);
		}
    }

    function saveprofileAction()
    {
		if(isset($_POST))
		{
        	echo $this->orders->saveProfile($_POST);
		}
    }
}
?>