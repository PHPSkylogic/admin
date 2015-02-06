<?php
/*
 * вывод каталога компаний и их данных
 */
class UsersController extends BaseController{

    protected $params;
    protected $db;

    function  __construct($registry, $params)
    {
		parent::__construct($registry, $params);
		$this->registry = $registry;
        $this->tb = "users";       
		$this->users = new Users($this->sets);
        $this->orders = new Orders($this->sets);
    }

    public function indexAction()
    {
		//$this->users->recalcPrice($_SESSION['user_id']);
        $vars['translate'] = $this->translation;
		if(!isset($_SESSION['user_id'])&&(isset($this->params['users'])&&$this->params['users']!="sign-up"&&$this->params['users']!="active"))
		{
			/*header("Location: /users/sign-up");	
			exit();*/
		}

		if(isset($this->params['users'])&&$this->params['users']=="orders"&&isset($_SESSION['user_id']))///Users orders
        {
            $data=$this->ordersAction();
        }
		elseif(isset($this->params['users'])&&$this->params['users']=="logout"&&isset($_SESSION['user_id']))///Users orders
        {
            $data=$this->users->logout();
        }
		elseif(isset($this->params['users'])&&$this->params['users']=="active"&&!isset($_SESSION['user_id']))///Users orders
        {
            $data['content']=$this->users->active();
        }
		elseif(isset($this->params['users'])&&$this->params['users']=="forgotpass"&&!isset($_SESSION['user_id']))///Users orders
        {
            $data=$this->forgotAction();
        }
        elseif(isset($_SESSION['user_id']))/////Edit user info
		{
			if(isset($_POST['save_data']))
			{
				if($_POST['old_pass']!=""&&$_POST['new_pass']!="")
				{
					$row = $this->db->row("SELECT id FROM users WHERE pass=? AND id=?", array(md5($_POST['old_pass']), $_SESSION['user_id']));	
					if($row)
					{
						$row=$this->db->query("UPDATE users SET pass=? WHERE id=?", array(md5($_POST['new_pass']), $_SESSION['user_id']));	
					}
				}
				
				if(!isset($_POST['mailer']))$_POST['mailer']=0;
				$row = $this->db->query("UPDATE users SET name=?, phone=?, 
																  address=?, 
																  city=?,
																  code_discount=?,
																  mailer=?
										 WHERE id=?",
				array($_POST['name_save'], $_POST['phone_save'], 
						$_POST['address'], 
						$_POST['city'],
						$_POST['code_discount'],
						$_POST['mailer'],
						$_SESSION['user_id']));
				$vars['message']="<div class='done'>".$this->translation['saved']."</div>";
			}
            $vars['user_info']=$this->users->find((int)$_SESSION['user_id']);
            $data['content'] = $this->view->Render($this->tb.'.phtml', $vars);
        }
		else////Registration or authorization
        {
            $data=$this->signUpAction();
        }
		
		$data['styles']=array('user.css', 'validationEngine.jquery.css', 'catalog.css');
		$data['scripts']=array('jquery.validationEngine.js', 'jquery.validationEngine-ru.js');
        return $this->Index($data);
    }

    public function signUpAction()
    {
		if(isset($_SESSION['user_id']))header("Location: /users/cabinet");
		elseif(isset($_POST['email_auth']))
        {
			$vars['message'] = $this->users->auth($_POST['email_auth'], $_POST['pass_auth']);
		}
        else
        {
			$arr = $this->users->signUp();
			$vars['message'] = $arr['message'];
        }
		
		$vars['body'] = $this->model->getPage('sign-up');
        $vars['translate'] = $this->translation;
        $data['content'] = $this->view->Render('sign_up.phtml', $vars);
        return $data;
    }

    public function authAction()
    {
        $error="";
        $error.=Validate::check($_POST['email'], $this->translation, 'email');
        $error.=Validate::check($_POST['password'], $this->translation);

        $row=$this->db->row("SELECT id, status_id, name FROM users WHERE email=? AND pass=?", array($_POST['email'], md5($_POST['password'])));
        if(!$row)$error.="<font style='color:red;'>".$this->translation['email_no_exists']."</font>";
        else{
            $row=$this->db->row("SELECT id, status_id, name FROM users WHERE id=? AND active=?", array($row['id'], 1));
            if(!$row)$error.="<font style='color:red;'>".$this->translation['no_active']."</font>";
        }
        if($error=="")
        {
            $admin_info=array();
            $admin_info['agent'] = $_SERVER['HTTP_USER_AGENT'];
            $admin_info['referer'] = $_SERVER['HTTP_REFERER'];
            $admin_info['ip'] = $_SERVER['REMOTE_ADDR'];
            $admin_info['id'] = $row['id'];
            $admin_info['name'] = $row['name'];
            $admin_info['status'] = $row['status_id'];
            $_SESSION['user_info'] = $admin_info;
            $_SESSION['user_id']=$row['id'];
            //$vars['message'] = "<font style='color:green;'>".$this->translation['auth_yes']."</font>";
        }
        echo $error;
    }
	
	public function ordersAction()
    {
		$vars['currency'] = $this->db->row("SELECT * FROM currency WHERE `base`='1'");
		if(isset($this->params['id'])&&$this->params['id']!="")
		{
			$vars['order'] = Orders::getObject($this->sets)->find(array('select'=>'tb.*, tb2.name, tb3.name as delivery, tb4.name as payment',
																		 'join'=>'LEFT JOIN orders_status tb2 ON tb.status_id=tb2.id 
																		 		  LEFT JOIN '.$this->registry['key_lang'].'_payment tb4 ON tb.payment_id=tb4.payment_id
																				  LEFT JOIN '.$this->registry['key_lang'].'_delivery tb3 ON tb.delivery_id=tb3.delivery_id',
																		 'where'=>'__tb.id:='.$this->params['id'].'__ AND __tb.user_id:='.$_SESSION['user_id'].'__ '));
			if(isset($vars['order']['id']))
			{
				$vars['product'] = $this->db->rows("SELECT orders_product.*, product.url 
													FROM orders_product 
													
													LEFT JOIN product
													ON product.id=orders_product.product_id
													
													WHERE orders_id=?", array($vars['order']['id']));
			}
		}
		else{

            if(isset($_POST['start_date']))
            {
                $_SESSION['start_date']=$_POST['start_date'];
                $_SESSION['end_date']=$_POST['end_date'];
            }
            elseif(!isset($_SESSION['start_date']))
            {
                $_SESSION['start_date']=date("Y-m-d", strtotime('-1 month'));
                $_SESSION['end_date']=date("Y-m-d");
            }


            $vars['info'] = $this->users->find(array('select'=>'tb.*, COUNT(tb2.id) AS cnt, SUM(tb2.sum) AS total',
                                            'join'=>'LEFT JOIN orders tb2 ON tb.id=tb2.user_id',
                                            'where'=>'__tb.id:='.$_SESSION['user_id'].'__',
                                            'group'=>'tb.id'));

			$vars['orders'] = Orders::getObject($this->sets)->find(array('select'=>'tb.id, tb.sum, tb.date_add, tb2.name',
																		 'join'=>'LEFT JOIN orders_status tb2 ON tb.status_id=tb2.id',
																		 'where'=>"tb.user_id='{$_SESSION['user_id']}' AND (tb.date_add>='{$_SESSION['start_date']} 00:00:00' AND tb.date_add<='{$_SESSION['end_date']} 23:59:59')",
																		 'order'=>'tb.date_add DESC',
																		 'group'=>'tb.id',
																		 'type'=>'rows'));


            $vars['product'] = $this->db->rows("SELECT
													tb.*,
													tb2.id,
													tb2.sum,
													tb2.currency,
													tb2.date_add,
													product.url,
													product.code,
													product.id AS product_id
												 FROM `orders_product` tb

                                                 LEFT JOIN orders tb2
                                                 ON tb.orders_id=tb2.id

                                                 LEFT JOIN price
                                                 ON price.id=tb.product_id

                                                 LEFT JOIN product
                                                 ON product.id=price.product_id

												 WHERE tb2.user_id=? AND (tb2.date_add>='{$_SESSION['start_date']} 00:00:00' AND tb2.date_add<='{$_SESSION['end_date']} 23:59:59')
												 ORDER BY tb2.`date_add` DESC", array($_SESSION['user_id']));


            //$sum_user = $this->orders->getSumUser($_SESSION['user_id']);
/*
            $sum_user['total']=$sum_user['total']+$sum_user['amount_orders'];
            $vars['discount_text2']='';
            $vars['discount2']=0;
            $arr = explode(';', $this->settings['discount2']);
            foreach($arr as $row)
            {
                $arr2 = explode('-', $row);
                if(isset($arr2[1]))
                {
                    $text = $arr2[0] - $sum_user['total'];
                    if($arr2[0]<=$sum_user['total'])
                    {
                        $vars['discount2']=$arr2[1];
                    }
                    elseif($text<=$arr2[0]&&$text>0)
                    {
                        $vars['discount_text2']='<div class="discount_more"> - Для получения накопительной скидка '.$arr2[1].' добавьте товар на сумму '.Numeric::formatPrice($text).'</div>';
                    }
                }
            }*/
		}
        
		//$vars['body'] = $this->db->row("SELECT tb.body FROM `".$this->key_lang."_pages` tb LEFT JOIN pages tb2 ON tb.pages_id=tb2.id WHERE tb2.url=?", array('sign-up'));
        $vars['translate'] = $this->translation;
        $data['content'] = $this->view->Render('orders.phtml', $vars);
        return $data;
    }
	
    function checkauthAction()
    {
        $vars['message'] = $this->users->auth($_POST['email'], $_POST['pass']);
		if(isset($_SESSION['user_id']))$vars['auth']=1;
        return json_encode($vars);
    }

	public function forgotAction()
    {
		$vars['translate'] = $this->translation;
		$settings = Registry::get('user_settings');
		$vars['message'] = '';
		if(isset($_POST['email']))
		{
			$error="";
			if(!preg_match('|([a-z0-9_\.\-]{1,20})@([a-z0-9\.\-]{1,20})\.([a-z]{2,4})|is', $_POST['email']))
				$error.="<div class='error'>".$this->translation['wrong_email']."</div>";
				
			$row = $this->db->row("SELECT id FROM users WHERE email=?", array($_POST['email']));
			if(!$row)$error.="<div class='error'>".$this->translation['email_exists2']."</div>";
			if($error=="")
			{
				$pass=md5(uniqid());
				$this->db->query("UPDATE users SET active_email=? WHERE `id`=?", array($pass, $row['id']));
				$text="Для смены пароля пройдите пожалуйста по ссылке <a href='http://{$_SERVER['HTTP_HOST']}/users/forgotpass/changepass/$pass' target='_blank'>http://{$_SERVER['HTTP_HOST']}/users/forgotpass/changepass/$pass</a>";
				Mail::send($settings['sitename'], // имя отправителя
									"info@".$_SERVER['HTTP_HOST'], // email отправителя
									"Пользователь на сайте ".$settings['sitename'], // имя получателя
									$_POST['email'], // email получателя
									"utf-8", // кодировка переданных данных
									"windows-1251", // кодировка письма
									"Запрос о смене пароля на сайте ".$settings['sitename'], // тема письма
									$text // текст письма
									);
				$vars['message'] = "<div class='done'>".$this->translation['change_pass']."</div>";					
			}
			else $vars['message'] = $error;
		}
		
		
		if(isset($this->params['changepass']))
		{
			$row=$this->db->row("SELECT id, email, name FROM users WHERE active_email=?", array($this->params['changepass']));
			if(!$row)$vars['message'] = "<div class='err'>".$this->translation['wrong_active']."!</div>";
			else{
				$pass2=genPassword();
				$pass=md5($pass2);
				$code=md5(mktime());
				$this->db->query("UPDATE users SET pass=?, active_email=? WHERE `id`=?", array($pass, $code, $row['id']));
				$text="Ваш новый пароль: $pass2";
				Mail::send($settings['sitename'], // имя отправителя
							   "info@".$_SERVER['HTTP_HOST'], // email отправителя
							   $row['name'], // имя получателя
							   $row['email'], // email получателя
							   "utf-8", // кодировка переданных данных
							   "windows-1251", // кодировка письма
							   "Ваш пароль изменен на сайте ".$settings['sitename'], //тема письма
							   $text // текст письма
								);
				$vars['message'] = "<div class='done'>".$this->translation['change_new_pass']."</div>";	
			}
		}
		$data['content'] = $this->view->Render('forgotpass.phtml', $vars);
		return $data;
	}
}
?>