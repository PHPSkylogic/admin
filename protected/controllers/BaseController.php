<?php
class BaseController{
	
	protected $registry;
	protected $params;
	protected $key_lang="ru";

	function  __construct($registry, $params)
	{
		$this->params = $params;
		$this->registry = $registry;//
		$this->db = new PDOchild($registry);//Connect to database
		$this->view = new View($this->registry);
		
		/////////////Language
		$this->key_lang = $this->registry['key_lang'];//Current language
		$this->key_lang_admin = $this->registry['key_lang_admin'];//Current admin language
		$this->language = $this->db->rows("SELECT * FROM language ORDER BY `id` ASC");//Languages        
		
		//////////Config
        $const = $this->db->rows_key("SELECT name, value FROM config");
        Registry::set('user_settings', $const);
		
		$this->settings = Registry::get('user_settings');
		
		////////////Translations
		$this->translation=$this->db->rows_key("SELECT tb.key, tb2.value
                                                FROM translate tb
                                                
												LEFT JOIN ".$this->key_lang."_translate tb2
                                                ON tb.id=tb2.translate_id");

		
		$this->sets = array('settings'=>$this->settings, 'registry'=>$registry, 'params'=>$params, 'db'=>$this->db, 'translation'=>$this->translation);
		$this->model = new Model($this->sets);
		$this->meta = new Meta($this->sets);

		///Check redirects list
		if(isset($this->params['topic'])&&$this->params['topic']!='admin')$this->meta->check_redirects();

		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('product'));
		if($row)
		{
			/////////Currency
			if(!isset($_SESSION['currency']))
			{
				$_SESSION['currency']=array();
				$_SESSION['currency'][1]=$this->db->row("SELECT * FROM `currency` WHERE base=?", array(1));
				$_SESSION['currency'][0]=$_SESSION['currency'][1]['id'];
			}
			elseif(isset($_POST['currency']))
			{
				$row = $this->db->row("SELECT * FROM `currency` WHERE id='{$_POST['currency']}'");
				if($row)
				{
					$_SESSION['currency']=array();
					$_SESSION['currency'][1]=$row;
					$_SESSION['currency'][0]=$_SESSION['currency'][1]['id'];
				}
			}
			else{
				$_SESSION['currency'][1]=$this->db->row("SELECT * FROM `currency` WHERE id=?", array($_SESSION['currency'][0]));	
			}
			
			$row = $this->db->row("SELECT * FROM `currency` WHERE id='2'");
			if($row)
			{
				$_SESSION['currency']=array();
				$_SESSION['currency'][1]=$row;
				$_SESSION['currency'][0]=$_SESSION['currency'][1]['id'];
			}
			$_SESSION['price_type_id'] = Product::getObject($this->sets)->default_price_type('user');
			
			$_SESSION['rounding']=$this->settings['rounding'];
			
			//session_regenerate_id();
			if(!isset($_COOKIE['session_id']))
			{
				setcookie("session_id", session_id(), time()+(31566000), '/', ".".$_SERVER['HTTP_HOST']);
				$_COOKIE['session_id']=session_id();
			}
			elseif(isset($_COOKIE['session_id']))
			{
				session_id($_COOKIE['session_id']);
			}
			//echo $_COOKIE['session_id'].'='.session_id();
			$this->model->recalc_price_range();
			
			#sort view
			if(!isset($_SESSION['sort_view']))$_SESSION['sort_view']='sort_table';
			elseif(isset($_POST['sort_view'])&&$_POST['sort_view']!='')$_SESSION['sort_view']=$_POST['sort_view'];
		}
	}


	/////Base action
	public function Index($param = array())
	{			
		
		if(!isset($this->params['topic'])) $param['topic'] = '';
		else $param['topic'] = $this->params['topic'];

		$data = $param;
		$settings = Registry::get('user_settings');
		$data['lang'] = $this->key_lang;
		$data['languages'] = $this->language;
		$data['currency'] = $this->db->rows("SELECT * FROM currency ORDER BY id ASC");
		
        if(!isset($param['styles']))$param['styles']=array();
        if(!isset($param['scripts']))$param['scripts']=array();
		
		
				
		if($param['topic'] == 'admin')//////Back side
		{
			$data['menu_inc']='';
			$data['admin'] = "admin";

			if(isset($_SESSION['admin']))
			{
				$styles=array_merge(array('style.css', 'jquery.notification.css', 'bootstrap.css'), $param['styles']);
				$scripts=array_merge(array('jquery-1.7.1.min.js', 'jquery-ui-1.10.3.custom.js', 'base.js', 'jquery.tablednd_0_5.js', 'ajax.js', 'jquery.cookie.js', 'autoresize.jquery.js'), $param['scripts']);
				if(!isset($data['breadcrumb']))$data['breadcrumb']=$this->model->breadcrumbAdmin();
				
				if($_SESSION['admin']['type']==1)
				{
					$data['menu'] = $this->db->rows("SELECT tb.*  FROM `modules` tb WHERE tb.hidden='0' ORDER BY tb.`sort` ASC");//var_info($data['menu']);
					
					$data['menu_subsystem'] = $this->db->rows("SELECT tb.* FROM `subsystem` tb ORDER BY tb.sort ASC, tb.`id` DESC");
				}
				else{
					$data['menu'] = $this->db->rows("SELECT tb.*, subsystem_id
													 FROM `modules` tb
													 
													 RIGHT JOIN moderators_permission tb2
													 ON tb.id=tb2.module_id AND tb2.moderators_type_id=? AND tb2.permission!=?
													 
													 WHERE tb.hidden='0'
													 GROUP BY tb.id
													 ORDER BY tb.`sort` ASC",
					array($_SESSION['admin']['type'], '000'));//var_info($data['menu']);
					
					$data['menu_subsystem'] = $this->db->rows("SELECT tb.* 
															   FROM `subsystem` tb 
															   
															   LEFT JOIN moderators_permission tb2
															   ON tb.id=tb2.subsystem_id 
															   
															   WHERE tb2.moderators_type_id=? AND tb2.permission!=?
															   GROUP BY tb.id
															   "
															  , array($_SESSION['admin']['type'], '000'));	
				}
				$data['key'] = $this->key_lang_admin;
				$data['menu_inc'] = $this->view->Render('menu.inc.phtml',	$data);
			}
            else{
				$data['login']=1;
				$styles = array_merge(array('style.css', 'jquery.notification.css'), $param['styles']);
				$scripts = array_merge(array('jquery-1.7.1.min.js', 'base.js'), $param['scripts']);				
			}
			
			$data['styles'] = $this->view->Load($styles, 'styles', 'admin');
			$data['scripts'] = $this->view->Load($scripts, 'scripts', 'admin');
			return ($this->view->Render('index.phtml', $data));//начальный шаблон	
		}
		//////Front side
		else
        {
			//////Webmaster meta confirm and google analytics
			$data['webmaster']='';
			if($settings['google']!="")$data['google']=$settings['google'];
			if(isset($settings['uni_analytics'])&&$settings['uni_analytics']==1)$data['uni_analytics']=$settings['uni_analytics'];
			if($settings['webmaster_g']!="")$data['webmaster'].='<meta name="google-site-verification" content="'.$settings['webmaster_g'].'" />';
			if($settings['webmaster_y']!="")$data['webmaster'].='<meta name="yandex-verification" content="'.$settings['webmaster_y'].'" />';
			
			///Include css and js
            $styles = array_merge(array('style.css', 'fancy.css', 'sticker.css', 'menu.css', 'skin.css', 'cabinet.css', 'user.css', 'bootstrap.css',  'forms.css', 'jquery.sb.css'), $param['styles']);
            $scripts = array_merge(array('jquery-1.7.1.min.js', 
									   'base.js', 
									   'ajax.js', 
									   'cabinet.js',
									   'jquery.stickr.min.js',
									   'jquery.fancybox-1.3.4.pack.js',
									   'jquery.jcarousel.min.js',
									   'bootstrap-modal.js',
									   'html2canvas.js',
									   'jquery.sb.min.js',
									   'jquery.blackandwhite.js',
									   'jquery.scrollTo-1.4.2.js'
									   ), 
									   $param['scripts']);									   
            
			if((int)$this->settings['testing_on'] == 1)
			{
                if($this->settings['testing_date_end']<date("Y-m-d H:i:s"))
                {
    				$dateEnd = new DateTime($this->settings['testing_date_end']);
    				$dateNow = new DateTime(date("Y-m-d H:i:s"));
    				$interval = $dateEnd->diff($dateNow);
    	
    				$testing['days'] = $interval->d;
    				$testing['hours'] = $interval->h;
    				$testing['minutes'] = $interval->i;
                }
				$testing['translate'] = $this->translation;
	
				$styles = array_merge($styles, array('testing.css'));
				$scripts = array_merge($scripts, array('html2canvas.js', 'testing.js'));
				$data['testing_mode'] = $this->view->Render('testing.phtml', $testing);
			}
            $data['translate'] = $this->translation;///Interface translations
            if(!isset($data['meta']))$data['meta']=array();
            $data['meta']=$this->meta->set_meta_data($data['meta'], $param['topic']);//Calling the metadata
			$this->catalog = new Catalog($this->sets);
			$data['catalog'] = $this->catalog->find(array('type'=>'rows',
														  'select'=>'tb.id, tb.url, tb.photo, tb.position, tb.sub, tb_lang.name',
														  'where'=>"tb.active='1'",
														  'group'=>'tb.id',
														  'order'=>'tb.sort ASC, tb.id DESC'));
			$data['cat_menu'] = $this->view->Render('cat_menu.phtml', array_merge(array('menu'=>$data['catalog'], 'translate'=>$data['translate'])));
															  
			#Start Main menu
			$menu = Menu::getObject($this->sets)->find(array('type'=>'rows',
                                                             'where'=>"tb.active='1'",
															 'order'=>'tb.sort ASC, tb.id DESC'));
			
            $data['menu'] = $this->view->Render('menu.phtml', array_merge(array('menu'=>$menu, 'translate'=>$data['translate'])));
			#!End Main menu 			
			
			$data['info_blocks'] = $this->model->getBlock(array(1,2,5,6,7,8,16));//Info blocks
			
			$news = News::getObject($this->sets)->find(array('where'=>'__tb.active:=1__', 
																 'type'=>'rows',
																 'order'=>'tb.date DESC',
																 'limit'=>$settings['limit_news_block']));
																 
			$data['news'] = $this->view->Render('news_block.phtml', array('news'=>$news, 'translate'=>$this->translation));
			#!End News blocks													 
			
			$data['topic']=$param['topic'];											 
			if(($param['topic'] == 'catalog'&&$this->params['catalog']!='all'))//////Back side
			{			
				#Start Left menu
				$data['left_menu'] = $this->view->Render('left_menu.phtml', array_merge($param, array('menu'=>$data['catalog'], 'translate'=>$data['translate'])));	
				$data['content'] = $this->view->Render('body_catalog.phtml', $data);
			}

			if(isset($_POST['send_callbacks']))
			{
				$text = "Телефон: {$_POST['phone_callbacks']}";
				$this->model->insert_post_form($text);
				//$this->sendMail($email, $name, $city, $tel, $text);
				Mail::send($this->settings['sitename'], // имя отправителя
							"info@".$_SERVER['HTTP_HOST'], // email отправителя
							$this->settings['sitename'], // имя получателя
							$this->settings['email'], // email получателя
							"utf-8", // кодировка переданных данных
							"utf-8", // кодировка письма
							"Обратный звонок от посетителя сайта ".$this->settings['sitename'], // тема письма
							$text // текст письма
							);
				$data['message']='<div class="done">'.$this->translation['message_sent'].'</div>';		
			}
			
			if(isset($_POST['send_inform']))
			{
				$this->product = new Product($this->sets);
				$product = $this->product->find((int)$_POST['inform_id']);
				$text = "Имя: {$_POST['inform_name']}<br />
					 	 E-mail: {$_POST['inform_email']}<br />
						 Телефон: {$_POST['inform_phone']}<br /><br />
						 
						 Товар: <a href='http://{$_SERVER['HTTP_HOST']}/product/{$product['url']}' target='_blank'>{$product['name']}</a>
						 ";
				$this->model->insert_post_form($text);
				//$this->sendMail($email, $name, $city, $tel, $text);
				Mail::send($this->settings['sitename'], // имя отправителя
							"info@".$_SERVER['HTTP_HOST'], // email отправителя
							$this->settings['sitename'], // имя получателя
							$this->settings['email'], // email получателя
							"utf-8", // кодировка переданных данных
							"utf-8", // кодировка письма
							"Сообщить о наличии. ".$this->settings['sitename'], // тема письма
							$text // текст письма
							);
				$data['message']='<div class="done">'.$this->translation['message_sent'].'</div>';		
			}
			
			////Auth form
			if(!isset($_SESSION['user_id']))$data['sign_in'] = $this->view->Render('sign_in.phtml', array('translate'=>$data['translate']));
			$data['feedback'] = $this->view->Render('feedback_modal.phtml', array('translate'=>$data['translate']));
			
			$where="";
			if(isset($_SESSION['user_id']))$where=", user_id='{$_SESSION['user_id']}'";
			
			$data['cnt_compare'] = $this->db->row("SELECT COUNT(*) AS cnt FROM compare WHERE session_id='{$_COOKIE['session_id']}'");
			$data['cnt_view'] = $this->db->row("SELECT COUNT(*) AS cnt FROM favorites WHERE type='1' AND session_id='{$_COOKIE['session_id']}' ".str_replace(', ',' OR ',$where)."");
			$data['cnt_fav'] = $this->db->row("SELECT COUNT(*) AS cnt FROM favorites WHERE type='0' AND session_id='{$_COOKIE['session_id']}' ".str_replace(', ',' OR ',$where)."");
			
			/*$res = $this->db->rows("SELECT id FROM product");
			foreach($res as $row)
			{
				$this->db->query("INSERT INTO product_catalog SET product_id='{$row['id']}', catalog_id='11'");
			}*/
			
			$data['styles'] = $this->view->Load($styles, 'styles');
            $data['scripts'] = $this->view->Load($scripts, 'scripts');
			if(isset($data['breadcrumbs']))$data['breadcrumbs'] = $this->model->breadcrumbs($data['breadcrumbs']);//Bread crumbs
            return ($this->view->Render("index.phtml", $data));//General template
		}
	}
}
?>