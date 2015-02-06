<?php
/*
 * вывод каталога компаний и их данных
 */
class ProductController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "product";
		$this->name = "Товары";
		$this->registry = $registry;
		$this->product = new Product($this->sets);
		$this->catalog = new Catalog($this->sets);
		$this->orders = new Orders($this->sets);
		$this->filters = new Params($this->sets);
	}

	public function indexAction()
	{
		if(!isset($this->params['product']))return Router::act('error', $this->registry);	
		$vars['message'] = '';
		$vars['translate'] = $this->translation;
		$vars['currency'] = $this->model->currency();
		if(isset($this->params['video']))$vars['show_video']=true;
		///Product										  
		$vars['product'] = $this->product->find(array("join"=>" LEFT JOIN `product_catalog` tb3 ON tb3.product_id=tb.id
																LEFT JOIN catalog ON catalog.id=tb3.catalog_id
																LEFT JOIN ".$this->key_lang."_catalog c2 ON catalog.id=c2.catalog_id
																LEFT JOIN (SELECT * FROM `price` WHERE price_type_id='".$_SESSION['price_type_id']."' ORDER BY `sort` ASC, id DESC) as `tb_price`
						 										ON `tb_price`.product_id=tb.id
																
																LEFT JOIN product_status_set tb4
																ON tb4.product_id=tb.id
																 
																LEFT JOIN ".$this->registry['key_lang']."_product_status tb5
																ON tb4.status_id=tb5.product_status_id
																
																LEFT JOIN ".$this->key_lang."_brend brend
																ON brend.brend_id=tb.brend_id
																",
																
													  "select"=>"tb.*, 
													  			 tb_lang.*, 
																 tb3.*,
																 catalog.sub as catalog_sub, 
																 c2.name AS catalog,
																 brend.name AS brend,
																 tb4.status_id,
																 tb5.name AS status,
																 tb_price.price, 
																 tb_price.discount, 
																 tb_price.id as price_id",
													  "where"=>"__tb.url:={$this->params['product']}__ AND tb.active='1'"));
		if(!$vars['product'])return Router::act('error', $this->registry);
		
		if($vars['product']['catalog_sub']==NULL)
		{
			$row = $this->db->row("SELECT * FROM product_catalog WHERE product_id='{$vars['product']['id']}' AND catalog_id!='{$vars['product']['catalog_id']}'");
			if($row)
			{
				$vars['product']['catalog_id']=$row['catalog_id'];	
			}
		}
		
		///Other products
		/*$q = Catalog::getObject($this->sets)->queryProducts(array('where'=>"AND
																		    __tb3.catalog_id:={$vars['product']['catalog_id']}__ AND 
																		    __tb3.product_id!:={$vars['product']['id']}__",
																  'order'=>'rand()',
																  'limit'=>3));
		$vars['other'] = $this->product->find($q);*/
		
		$productStatus = Product_status::getObject($this->sets)->find(8);
		$q=$this->catalog->queryProducts(array('where'=>"AND tb4.status_id='8'"));
		$product = $this->product->find(array_merge($q, array("limit"=>$this->settings['novelty_col'])));
		$vars['recomand'] = $this->view->Render('product_block.phtml', array('product'=>$product, 'title'=>$productStatus['name']));				
		
		///Other products
		$q = $this->catalog->queryProducts(array( 'join'=>'LEFT JOIN product_other po ON po.product_id2=tb.id ',
												  'where'=>"AND __po.product_id:={$vars['product']['id']}__",
												  'order'=>'tb.sort ASC, tb.id DESC'));
		$vars['other'] = $this->product->find($q);
		
		///////Extra photo
		$vars['photo'] = $this->product->find(array('table'=>'product_photo',
													'where'=>"__tb.product_id:={$vars['product']['id']}__ AND tb.active='1' AND texture='0'",
													'order'=>'tb.`sort` ASC',
													'type'=>'rows'));
		
		$vars['params'] = $this->filters->find(array("join"=>" LEFT JOIN `params_product` tb2 ON tb2.params_id=tb.id
															   
															   LEFT JOIN params ON params.id=tb.sub
															   LEFT JOIN ".$this->key_lang."_params params_lang ON params.id=params_lang.params_id
																",
																
													  "select"=>"tb.*, tb_lang.*, params_lang.name as sub_name, GROUP_CONCAT(DISTINCT tb_lang.name SEPARATOR ', ') as name ",
													  "where"=>"__tb2.product_id:={$vars['product']['id']}__ AND tb.active='1' AND tb.sub IS NOT NULL",
													  "group"=>"params.id",
													  "order"=>"params.sort ASC",
													  "type"=>"rows"));
		
		$vars['sizes'] = $this->product->getSizes($vars['product']['id']);
        $vars['colors'] = $this->product->getColors($vars['product']['id']);
		
		////Fast order
		if(isset($_POST['f3_name']))
		{
			$error="";
			if(!Captcha3D::check($_POST['f3_captcha']))$error.="<div class='err'>".$this->translation['wrong_code']."</div>";
			$error.=Validate::check(array($_POST['f3_name'], $_POST['f3_phone'], $_POST['f3_captcha']), $this->translation);
			if($error=="")
			{
				$user_id = 0;
				if(isset($_SESSION['user_id']))
				{
					$row = $this->db->row("SELECT * FROM `users` WHERE id=?", array($_SESSION['user_id']));
					$user_id = $row['id'];
				}
				
				$query = "SELECT '1' AS `amount`,
							p.id,
							p.code,
							p.url,
							tb_price.`price`,
							p.`photo`,
							tb_price.discount,
							p2.name
				
				  FROM product p
				  
				  LEFT JOIN ".$this->registry['key_lang']."_product p2
				  ON p.id=p2.product_id
				  
				  LEFT JOIN (SELECT * FROM `price` WHERE price_type_id='".$_SESSION['price_type_id']."' ORDER BY `sort` ASC, id DESC) as `tb_price`
				  ON `tb_price`.product_id=p.id
		
				  WHERE p.id='{$vars['product']['id']}'";
				$res=$this->db->rows($query);
				$info['name']=$_POST['f3_name'];
				$info['phone']=$_POST['f3_phone'];
				$this->orders->sendOrder($res, $info, $user_id);
				$vars['message']='<div class="done" style="margin:15px 0;">'.htmlspecialchars_decode($this->translation['message_sent_order']).'</div>';
			}
			else $vars['message']=$error;
		}
		
		////Follow price
		if(isset($_POST['send_follow']))
		{
			$error="";
			//if(!Captcha3D::check($_POST['f3_captcha']))$error.="<div class='err'>".$this->translation['wrong_code']."</div>";
			$error.=Validate::check($_POST['follow_name'], $this->translation);
			$error.=Validate::check($_POST['follow_email'], $this->translation, 'email');
			if($error=="")
			{
				$text = "Имя: {$_POST['follow_name']}<br />
						 E-mail: {$_POST['follow_email']}";
				$this->model->insert_post_form($text);
				//$this->sendMail($email, $name, $city, $tel, $text);
				Mail::send($_POST['follow_name'], // имя отправителя
							"info@".$_SERVER['HTTP_HOST'], // email отправителя
							$this->settings['sitename'], // имя получателя
							$this->settings['email'], // email получателя
							"utf-8", // кодировка переданных данных
							"utf-8", // кодировка письма
							"Следить за ценой сайта ".$this->settings['sitename'], // тема письма
							$text // текст письма
							);
				$data['message']='<div class="done">'.$this->translation['message_sent'].'</div>';		
			}
			else $data['message']=$error;
		}
		
		if(isset($this->settings['comment_product'])&&$this->settings['comment_product']==1)
			$vars['comments'] = Comments::getObject($this->sets)->list_comments($vars['product']['id'], $this->tb);
		
		$vars['comments2'] = $this->db->rows("SELECT c.id, c.author, c.text, c.text2, author2, moderator_id, c.date, c.photo
									 FROM `comments` c
									 
									 WHERE c.active='1' AND content_id='{$vars['product']['id']}' AND type='product'
									 GROUP BY c.id
									 ORDER BY c.date DESC 
									 LIMIT 4");
		$data['meta'] = $vars['product'];////Meta
		$data['breadcrumbs'] = $this->model->getBreadCat($vars['product']['catalog_id'], $vars['product']['name']);
		$data['cur_product'] = $vars['product']['id'];
		
		$vars['rating']=$this->model->rating_bar($this->tb,$vars['product']['id'], 5);
		$this->model->add_fav($vars['product']['id'], 1);
		$vars['delivery'] = $this->model->getBlock(15);
		$data['styles'] = array('easyzoom.css', 'pygments.css', 'catalog.css', 'rating.css');
		$data['scripts'] = array('jquery.elevatezoom.js', 'behavior.js', 'rating.js');
		$data['content'] = $this->view->Render('product_in.phtml', $vars);
		return $this->Index($data);
	}

	function loadphotoAction()
	{
		if(isset($_POST['id']))
		{
			$photo='';
			$_POST['id']=explode('_',$_POST['id']);
			$res = $this->product->getPhotoProduct($_POST['id'][1]);
			if(isset($res[0]))
			{
				$photo='<ul>';
				$row2=$this->product->find(array('table'=>'product',
												 'where'=>"__tb.id:={$_POST['id'][1]}__ AND tb.active='1'"));
												 
				if(file_exists($row2['photo']))	
				{					 
					$photo.='<li><a href="/product/'.$row2['url'].'"><img src="/'.$row2['photo'].'" /></a></li>';	
				}
				foreach($res as $row)
				{
					if(file_exists($row['photo']))
					{
						$photo.='<li><a href="/product/'.$row2['url'].'"><img src="/'.$row['photo'].'" /></a></li>';	
					}
				}
				
			}
			$data['content']='<div class="extra_photo">'.$photo.'</div>';
			return json_encode($data);
		}
	}
	
	function favAction()
	{
		if(isset($_POST['id']))
		{
			$res = $this->model->add_fav($_POST['id']);
			return json_encode(array('message'=>'Товар добавлен!'));
		}
	}
	
	///Load size
	function loadparamAction()
    {
		if(isset($_POST['id'], $_POST['product_id']))
		{
			$_POST['id']=explode('-',$_POST['id']);
			$_POST['id']=$_POST['id'][1];

            if($_POST['id2']!='')
            {
                $_POST['id2']=explode('-',$_POST['id2']);
                $_POST['id2']=$_POST['id2'][1];
            }

			$return=array();
			$res = $this->product->get_param($_POST['id'], $_POST['product_id'], $_POST['id2']);
			$return['option']='';
			$return['select_size']='';
			$cnt=count($res);


			if($cnt>1)
			{
                $id2=$res[0]['id'];
				foreach($res as $row)
				{
					if($return['option']!='')$return['option'].=',';
					$return['option'].=$row['id'];

                    if($_POST['id2']!=''&&$_POST['id2']==$row['id'])$id2=$row['id'];
				}
                $row2 = $this->product->get_remains(array($_POST['id'], $id2),  $_POST['product_id']);
                $return['price'] = $row2['price'];
                $return['cur_price'] = $row2['cur_price'];
                $return['remains'] = $row2['stock'];
                $return['max'] = $row2['max'];
				$return['photo'] = $row2['photo'];
			}
			elseif($cnt==1)
			{
				$row = $this->product->get_remains(array($_POST['id'],$res[0]['id']),  $_POST['product_id']);

				$return['price'] = $row['price'];
				$return['cur_price'] = $row['cur_price'];
				$return['remains'] = $row['stock'];
				$return['option']=$res[0]['id'];
                $return['max'] = $row['max'];
				$return['price_id'] = $row['price_id'];
				$return['photo'] = $row['photo'];
			}
			elseif($cnt==0)
			{
				$row = $this->product->get_remains(array($_POST['id']),  $_POST['product_id']);
				$return['price'] = $row['price'];
				$return['cur_price'] = $row['cur_price'];
				$return['remains'] = $row['stock'];
				$return['option']='';
                $return['max'] = $row['max'];
			}
			/*$return['image']='';
			$dir=createDir($_POST['product_id']);
			$path=$dir[0].$_POST['product_id'].".jpg";
			if(file_exists($path))
			{
				$return['image']='<a href="/'.$path.'" rel="lightbox">
									<img src="/'.$path.'" />
								  </a>';	
			}
			$row = $this->db->row("SELECT code as id_1c FROM product WHERE id='{$_POST['product_id']}'");
			if($row['id_1c']!='')$return['code']='<div class="parag" style="margin:0;"><span>Артикул: </span>'.$row['id_1c'].'</div>';*/

			$return['option']=explode(',',$return['option']);
			return json_encode($return);
		}
    }
	
	function remainsAction()
    {	
		$param=array();
		if($_POST['color_id']!='')array_push($param, $_POST['color_id']);
		if($_POST['size_id']!='')array_push($param, $_POST['size_id']);
		$row = $this->product->get_remains($param,  $_POST['product_id']);
		$return['price'] = $row['price'];
		$return['cur_price'] = $row['cur_price'];
		$return['remains'] = $row['stock'];
		return json_encode($return);
	}
}
?>