<?php
/*
 * вывод каталога компаний и их данных
 */
class CatalogController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "catalog";
		$this->name = "Каталог";
		
		$this->registry = $registry;
		$this->catalog = new Catalog($this->sets);
		$this->product = new Product($this->sets);
		$this->filters = new Filters($this->sets);
	}

	public function indexAction()
	{
		/*$res = $this->db->rows("SELECT * FROM product WHERE price!='0.00'");
		foreach($res as $row)
		{
			$this->db->query("INSERT INTO price SET price='{$row['price']}', product_id='{$row['id']}', code='{$row['code']}-1', price_type_id='1'");
			$this->db->query("INSERT INTO price SET price='{$row['price']}', product_id='{$row['id']}', code='{$row['code']}-2', price_type_id='2'");
			$this->db->query("INSERT INTO price SET price='{$row['price']}', product_id='{$row['id']}', code='{$row['code']}-3', price_type_id='3'");	
		}
		exit();
		$res = $this->db->rows("SELECT product_id, body2 FROM ru_product WHERE body2!=''");
		foreach($res as $row)
		{
			$this->db->query("UPDATE ru_product SET body2='".strip_tags(htmlspecialchars_decode($row['body2']))."' WHERE product_id='{$row['product_id']}'");	
		}*/
		
		$settings = Registry::get('user_settings');
		$vars['message'] = "";
		$data['bread_crumbs'] = "";
		$vars['translate'] = $this->translation;///Переводы интерфейса
		$param=array();
		$param['join']='';
		$data['params_url']='';
		$vars['curr_cat']['name'] = $vars['translate']['catalog'];
		
		if(!isset($_POST['clear_id']))$_POST['clear_id']='';
		if(!isset($_POST['items']))$_POST['items']='';
		
		if(($_POST['items']=='') && (!isset($_POST['clear_id'])))
		{
		    $_SESSION['params'] = array();
		}
		
		#Start onpage
		$vars['onpage'] = array(60, 120, 240);
		if(!isset($_SESSION['onpage']))$_SESSION['onpage']=$vars['onpage'][0];
		if(isset($_POST['onpage']))
		{
			foreach($vars['onpage'] as $row)
			{
				if($_POST['onpage']==$row)$_SESSION['onpage']=$row;	
			}
		}
		//$_SESSION['onpage']=$this->settings['paging_product'];

		if(!isset($_SESSION['search']))$_SESSION['search']='';
		if(isset($_POST['search']))$_SESSION['search']=$_POST['search'];
		
		if(!isset($_SESSION['catalog']))$_SESSION['catalog']='';
		if(!isset($_SESSION['sub'])||(!isset($_POST['sub'])&&isset($_POST['sub_hid']))||$_SESSION['catalog']!=$this->params['catalog'])$_SESSION['sub']=array();
		if(isset($_POST['sub']))$_SESSION['sub'] = $_POST['sub'];
		$_SESSION['catalog']=$this->params['catalog'];
		$_SESSION['catalog_contin']=LINK."/catalog/{$this->params['catalog']}";
		#!End onpage

		#Start sort
		if(isset($_POST['sort'])&&$_POST['sort']!='')
		{
			header('Location: '.String::getUrl('sort', $_POST['sort']));
			exit();		
		}
		elseif(isset($_POST['sort']))
		{
			header('Location: '.String::getUrl2('sort'));
			exit();	
		}
		
		$_SESSION['sort']="tb.sort DESC, tb.id desc";
		if(isset($this->params['sort'])&&($this->params['sort']=="price-asc"))$_SESSION['sort']="price asc, tb.id desc";
		elseif(isset($this->params['sort'])&&($this->params['sort']=="price-desc"))$_SESSION['sort']="price desc, tb.id desc";
		elseif(isset($this->params['sort'])&&($this->params['sort']=="sort-asc"))$_SESSION['sort']="tb.sort asc, tb.id desc";
		elseif(isset($this->params['sort'])&&($this->params['sort']=="sort-desc"))$_SESSION['sort']="tb.sort desc, tb.id desc";
		#!End sort	
		
		$param['where']='';
		$whereParams = "";
		$join = "";
		$_SESSION['params']='';
		$vars['params_url']='';

		if(isset($this->params['catalog'])&&$this->params['catalog']=="all")
		{
			$vars['top_cat'] = $this->catalog->find(array('select'=>'tb.id, tb.sub, tb.photo, tb.url, tb_lang.name',
														  'where'=>'active="1"', 
														  'order'=>'sort ASC, id DESC', 
														  'type'=>'rows'));
			
			$productStatus = Product_status::getObject($this->sets)->find(6);
			$q=Catalog::getObject($this->sets)->queryProducts(array('where'=>"AND tb4.status_id='6'"));
			$product = Product::getObject($this->sets)->find(array_merge($q, array("limit"=>$this->settings['novelty_col'])));
			$vars['novelty'] = $this->view->Render('product_block.phtml', array('product'=>$product, 'title'=>$productStatus['name']));										  
		}
		else{
			if(isset($this->params['catalog'])&&$this->params['catalog']!="search")//Если открыт конкретный каталог
			{	
				$productStatus = Product_status::getObject($this->sets)->find($this->params['catalog']);
				if($productStatus)
				{
					$param['where']="AND tb.id in(select product_id from product_status_set where status_id='{$productStatus['id']}')";
					$vars['curr_cat']['name'] = $productStatus['name'];
					$data['breadcrumbs'] = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', $productStatus['name']);
				}
				else{
					$catrow = $this->catalog->find(array('where'=>'__tb.active:=1__ AND __tb.url:='.$this->params['catalog'].'__'));
					if(!isset($catrow['id']))return Router::act('error', $this->registry);
					$_SESSION['catalog2']=$catrow['id'];	
					if($catrow)
					{
						$catrow2 = $this->catalog->find(array('where'=>'__tb.active:=1__ AND __tb.sub:='.$catrow['id'].'__'));
						if(!$catrow2)$data['curr_parent']=$catrow['sub'];
						else $data['curr_parent']=$catrow['id'];
						
           				$data['meta'] = $catrow;////Meta
						$data['breadcrumbs'] = $this->catalog->getBreadCat($catrow);////bread crumbs
						$vars['sub'] = $this->catalog->find(array('select'=>'tb.id, tb.url, tb_lang.name',
													'where'=>'__sub:='.$catrow['id'].'__', 
													'order'=>'sort asc', 
													'type'=>'rows'));
						$subcat='';
						foreach($vars['sub'] as $row)
						{
							$subcat.="OR tb3.catalog_id='{$row['id']}'";	
						}
						$vars['curr_cat']=$catrow;
						$param['where'].="AND (tb3.catalog_id='{$catrow['id']}' $subcat)";
					}
					else return Router::act('error', $this->registry);			
				}
			}
			//Если производится поиск по каталогу
			elseif(isset($this->params['catalog'])&&isset($_POST['search']))
			{
				$_SESSION['search']=$_POST['search'];
				$vars['cat']['search']='ПОИСК "'.$_SESSION['search'].'"';
				
				if($_SESSION['search']==''||$_SESSION['search']=='Поиск')$vars['message'] = '<div class="err">Введите строку поиска</div>';
				else{
					$vars['curr_cat']['name']='ПОИСК "'.$_SESSION['search'].'"';
					
					$where_s = $this->search_split($_SESSION['search']);
					if($where_s!='')$param['where']="AND ($where_s)";
				}
			}
			
			/* Составление запроса с выбраными фильтрами */
			$price='';
			if(isset($vars['curr_cat']['id']))
			{
				if(isset($this->params['params']))$vars['params_url']=$this->params['params'];
				$condition=$this->filters->get_condition($vars['curr_cat']['id'], $vars['params_url']);
				$param['join'].=$condition['join'];
				$param['where'].=$condition['where'];
				
				if(isset($this->params['price-range'])&&$this->params['price-range']!='')
				{
					$price = explode('-', $this->params['price-range']);
					$param['where'].="AND (`tb_price`.price>='{$price[0]}' AND `tb_price`.price<='{$price[1]}') ";
				}
				if(!isset($condition['group']))$condition['group']='';
			}			
					
			$param['where'].=" AND tb.active='1' ";
			$param['order']=$_SESSION['sort'];
			$param['order']=str_replace('price', 'tb_price.price', $param['order']);
			
			$q = $this->catalog->queryProducts($param);
			
			#Start paging
			$vars['list'] = $this->product->find(array_merge($q, array("paging"=>$_SESSION['onpage'])));
			if(!$vars['list'])return Router::act('error', $this->registry);
			if(!isset($vars['list']['list'][0]))
			{
				if($vars['params_url']!='')$vars['list'] = '<h3 align="center">'.$vars['translate']['if_no_items_params'].'</h3>';
				else $vars['list'] = '<h3 align="center">'.$vars['translate']['if_no_items'].'</h3>';
			}
			
			if(isset($condition['group']))
            {
                $this->db->query("SET SESSION group_concat_max_len = 10000000");
                $product['id']='';
                if($vars['params_url']!='')
                {
                    $q['select']="GROUP_CONCAT(DISTINCT tb.id ORDER BY tb.id ASC SEPARATOR ',') AS id, tb_price.price-tb_price.price*(tb_price.discount/100) AS price_sort";
                    $q['type']="row";
                    unset($q['group']);
                    $product = $this->product->find($q);
                }
                $filter = $this->filters->getParams($condition['group'], $vars['curr_cat']['id'], $vars['params_url'], $product['id'], $price);
                $data['filters'] = $this->view->Render('cat_filters.phtml', array('filter'=>$filter, 'translate'=>$this->translation));
                $data['scripts'] = array('filters.js');
            }
			//echo $vars['list']['count'];
			#!End paging			
		}
	/*	$qq="SELECT pp.product_id
		 FROM params_product pp
		
		 LEFT JOIN product_catalog pc
		 ON pc.product_id=pp.product_id
		 
		 WHERE pc.product_id IS NULL
		 
		 GROUP BY pp.product_id
		";
		$res=$this->db->rows($qq);var_info($res);*/
		$data['styles'] = array('catalog.css');
		$data['content'] = $this->view->Render('catalog.phtml', $vars);
		return $this->Index($data);
	}

	function getfilterAction()
	{
		$where = '';
		$join = '';
		$price='';
		$data = array();
		$data['params_url']='';
		$data['translate'] = $this->translation;
		$_SESSION['params'] = array();
		$_SERVER['REQUEST_URI'] = $_POST['url'];
		if(!isset($_POST['clear_id']))$_POST['clear_id']='';
		if(!isset($_POST['items']))$_POST['items']='';
		
		$_POST['cat_id'] = str_replace('licat_', '', $_POST['cat_id']);

		/*if(isset($_POST['price_from']))
		{
			if($_POST['price_from']==0&&$_POST['price_to']==0)
			{
				unset($_SESSION['price_from'],$_SESSION['price_to']);
				$this->model->recalc_price_range();	
			}
			else{
				if($_SESSION['currency'][1]['base']==1)
				{
					$_SESSION['price_from'][0] = $_POST['price_from'];
					$_SESSION['price_from'][1] = $_POST['price_from'];
					$_SESSION['price_to'][0] = $_POST['price_to'];
					$_SESSION['price_to'][1] = $_POST['price_to'];
				}
				else{
					$_SESSION['price_from'][0] = $_POST['price_from']*$_SESSION['currency'][1]['rate'];
					$_SESSION['price_from'][1] = $_POST['price_from'];
					$_SESSION['price_to'][0] = $_POST['price_to']*$_SESSION['currency'][1]['rate'];
					$_SESSION['price_to'][1] = $_POST['price_to'];	
				}
			}
		}*/
		
		/* Условие where с подкаталогами для запроса продуктов */
		$sub_cat='';
		$res = $this->db->rows("SELECT id FROM catalog WHERE sub='{$_POST['cat_id']}'");
		foreach($res as $row2)
		{
			$sub_cat.=" OR tb3.catalog_id='".$row2['id']."'";	
		}
		if($sub_cat!='')$where.=" AND (tb3.catalog_id='".$_POST['cat_id']."' $sub_cat)";
		else $where.=" AND tb3.catalog_id='".$_POST['cat_id']."'";
		
		$where.=" AND tb.active='1'";
		if(isset($_POST['cat_id'])&&$_POST['cat_id']!='')
		{
			$data['params_url'] = $_POST['items'];
			$condition=$this->filters->get_condition($_POST['cat_id'], $data['params_url']);
			$join.=$condition['join'];
			$where.=$condition['where'];
			
		}
		
		$q = $this->catalog->queryProducts(array('where'=>$where, 'join'=>$join));
		if(!isset($_POST['only_filters']))
		{
			$q['order']=$_SESSION['sort'];
			$q['order']=str_replace('price', 'tb_price.price', $q['order']);
			//$q['where'].=" AND (`tb_price`.price>='{$_SESSION['price_from'][0]}' AND `tb_price`.price<='{$_SESSION['price_to'][0]}')";
			
			
			if($_POST['change_price']!='undefined'&&$_POST['change_price']!='')
			{
				$q['where'].="AND (`tb_price`.price>='{$_POST['price_from']}' AND `tb_price`.price<='{$_POST['price_to']}') ";
				$price=array($_POST['price_from'], $_POST['price_to']);
			}
			
			$data['list'] = $this->product->find(array_merge($q, array("paging" =>$_SESSION['onpage'])));
			if(!isset($data['list']['list'][0]))
			{
				$data['product'] = '<h3 align="center">По выбраным характеристикам нет товаров, попробуйте другую комбинацию!</h3>';
			}
			else{
				$data['product'] = $this->view->Render('catalog_ajax.phtml', $data);
			}
		}
		
		if(isset($condition['group']))
		{
			$this->db->query("SET SESSION group_concat_max_len = 10000000");
			$product['id']='';
			if($data['params_url']!='')
			{
				$q['select']="GROUP_CONCAT(DISTINCT tb.id ORDER BY tb.id ASC SEPARATOR ',') AS id, tb_price.price-tb_price.price*(tb_price.discount/100) AS price_sort";
				$q['type']="row";
				unset($q['group']);
				$product = $this->product->find($q);//var_info($product);
			}
			$data['filters'] = $this->filters->getParams($condition['group'], $_POST['cat_id'], $data['params_url'], $product['id'], $price);
		}
		$data['params_limit'] = $this->settings['params_limit'];
		
        return json_encode($data);
	}

	
	function searchAction()
	{
		$where = $this->search_split($_POST['word']);
		
		$res=$this->db->rows("SELECT tb.id, tb.url, tb.photo, tb_lang.name, tb.url
		FROM product tb
		
		LEFT JOIN ".$this->key_lang."_product tb_lang
		ON tb_lang.product_id=tb.id
				 
		LEFT JOIN product_catalog pc
		ON pc.product_id=tb.id

		WHERE tb.active='1' AND  ($where)
		GROUP BY tb.id
		ORDER BY tb_lang.name ASC, tb.id DESC
		LIMIT 10
		");
		$result='<ul>';
		foreach($res as $row)
		{
			$dir=$row['photo'];
			$src='';
			if(file_exists($row['photo']))
				$src='<img alt="'.$row['name'].'" title="'.$row['name'].'" src="/'.$row['photo'].'" />';
			$result.='<li><a href="'.LINK.'/product/'.$row['url'].'"><div class="search_photo">'.$src.'</div><div class="search_name">'.$row['name'].'</div></a></li>';
		}
		$result.='</ul>';
		echo $result;	
	}
	
	public function search_split($word)
	{
		$where="";
		$arr = explode(' ', $word);
		foreach($arr as $row)
		{
			if($where!='')$where.=' AND ';
			$where.="(tb_lang.name like '%{$row}%' OR 
					  tb_lang.body_m like '%{$row}%' OR
					  tb_lang.body like '%{$row}%' OR
					  tb.code like '%{$row}%')";
		}
		if($where=='')
		{
			$where.="tb_lang.name like '%{$word}%' OR 
					 tb_lang.body_m like '%{$word}%' OR
					 tb_lang.body like '%{$word}%' OR
					 tb.code like '%{$word}%'";	
		}
		return $where;
	}
	
	function changeviewAction()
	{
		if(isset($_POST['id']))
		{
			$_SESSION['sort_view'] = $_POST['id'];
		}
	}
}
?>