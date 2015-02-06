<?php
/*
 * вывод каталога компаний и их данных
 */
class ProductController extends BaseController{
	
	protected $params;
	protected $db;
	private $left_menu = array(array('title'=>'Типы цен', 
									 'url'=>'/admin/product/act/pricetype', 
									 'name'=>'pricetype'),
                                array('title'=>'Поставщики',
                                    'url'=>'/admin/product/act/provider',
                                    'name'=>'provider')
							   );
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "product";
		$this->name = "Товары";
		$this->registry = $registry;
        $this->product = new Product($this->sets);
		$this->catalog = new Catalog($this->sets);
	}

	public function indexAction() 
	{
		
		if(isset($this->params['act']))
		{
			$act=$this->params['act'].'Action';
			return $this->Index($this->$act());
		}
		if(isset($this->params['subsystem']))return $this->Index($this->product->subsystemAction($this->left_menu));
        if(!isset($_SESSION['search_admin'])||isset($_POST['clear']))
        {
			$_SESSION['search_admin']=array();
            $_SESSION['search_admin']['cat_id']=0;
            $_SESSION['search_admin']['price_from']="";
            $_SESSION['search_admin']['price_to']="";
            $_SESSION['search_admin']['word']="";
			$_SESSION['search_admin']['paging']='';
			$_SESSION['search_admin']['params']='';
        }
        elseif(isset($_POST['word']))
        {
            $_SESSION['search_admin']['cat_id']=$_POST['cat_id'];
            $_SESSION['search_admin']['price_from']=$_POST['price_from'];
            $_SESSION['search_admin']['price_to']=$_POST['price_to'];
            $_SESSION['search_admin']['word']=$_POST['word'];
			$_SESSION['search_admin']['params']=$_POST['params'];
        }
		$vars['message'] = '';
		$vars['name'] = $this->name;
		$_SESSION['return_link']=$_SERVER['REQUEST_URI'];
		
		if(isset($this->registry['access']))$vars['message'] = $this->registry['access'];
		if(isset($this->params['delete'])||isset($_POST['delete']))$vars['message'] = $this->product->delete();
		elseif(isset($_POST['update']))$vars['message'] = $this->product->save();
		elseif(isset($_POST['update_close']))$vars['message'] = $this->product->save();
		elseif(isset($_POST['add_open']))$vars['message'] = $this->product->add(true);
		
		if(isset($this->params['cat'])&&$this->params['cat']!='')
		{
			$vars['curr_cat'] = $this->catalog->find($this->params['cat']);
		}
		
		$vars['list'] = $this->view->Render('view.phtml', $this->product->listView());
		$vars['status'] = Product_status::getObject($this->sets)->find(array('type'=>'rows', 'order'=>'id ASC'));
		$vars['params'] = Params::getObject($this->sets)->find(array('type'=>'rows', 'order'=>'sort ASC, id DESC'));
		$vars['catalog'] = $this->catalog->find(array('select'=>'tb.*, tb_lang.name, tb2.product_id',
													   'join'=>' LEFT JOIN `product_catalog` tb2 ON tb.id=tb2.catalog_id',
													   'group'=>'tb.id',
													   'order'=>'tb.sort',
													   'type'=>'rows'));
		$vars['URL']='';
		$data['styles']=array('jquery.treeview.css');
		$data['scripts']=array('jquery.treeview.js');
		
		$vars['link'] = '/admin/product/cat/';
		$settings = array('arr'=>$vars['catalog'], 'link'=>'/admin/product/cat/', 'id'=>'tree');
        $data['left_menu'] = $this->view->Render('cat_menu.phtml', array('cat_menu'=>Arr::treeview($settings)));
		$data['left_menu'] .= $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'menu2'=>$this->left_menu));
		$data['content'] = $this->view->Render('list.phtml', $vars);
		return $this->Index($data);
	}
	
	public function addAction()
	{
		if(!isset($_SESSION['return_link']))$vars['message'] = $_SESSION['return_link']='/admin/'.$this->table;
		if(isset($this->params['cat'])&&$this->params['cat']!='')
		{
			$vars['curr_cat'] = $this->params['cat'];
		}
		
		$vars['message'] = '';
		if(isset($_POST['add']))$vars['message'] = $this->product->add();

        $vars['catalog'] = Catalog::getObject($this->sets)->find(array('type'=>'rows',
																	   'group'=>'tb.id',
																	   'order'=>'tb.sort'));
											  
		//////////Brends set
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('brend'));
		if($row)
		{
			$vars['brend'] = Brend::getObject($this->sets)->find(array('select'=>'tb.id, tb_lang.name', 'type'=>'rows', 'order'=>'tb.sort'));									  					  
		}	
		
		//////////Params set
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('params'));
		if($row)
		{
            $vars['params'] = Params::getObject($this->sets)->find(array('type'=>'rows',
																		 'order'=>'tb.sort ASC'));
			
			$vars['params'] = $this->view->Render('params.phtml', $vars);								  
		}
        $vars['providers'] = $this->db->rows("SELECT * FROM providers ORDER BY id ASC");
        $vars['providers_def'] = $this->db->row("SELECT * FROM providers WHERE `default`='1'");
		/////////Price
		$price_type = $this->db->rows("SELECT * FROM price_type ORDER BY id ASC");						
		$price_rows = $this->view->Render('price_row.phtml', array('price'=>array(1), 'price_type'=>$price_type, 'type'=>'add', 'def'=>$vars['providers_def']['price']));
		$vars['price'] = $this->view->Render('price.phtml', array('price'=>$price_rows, 'type'=>'add'));
		
		
		$vars['status'] = Product_status::getObject($this->sets)->find(array('type'=>'rows', 'order'=>'id ASC'));
		$vars['currency'] = $this->db->row("SELECT icon FROM currency WHERE `base`='1'");


		$vars['height']=$this->settings['height_product'];	
		$vars['width']=$this->settings['width_product'];
		$vars['height_extra']=$this->settings['height_product_extra'];	
		$vars['width_extra']=$this->settings['width_product_extra'];
						  
		$data['content'] = $this->view->Render('add.phtml', $vars);
		return $this->Index($data);
	}

	public function editAction()
	{
		if(!isset($_SESSION['return_link']))$vars['message'] = $_SESSION['return_link']='/admin/'.$this->tb;
		$data['breadcrumb']='<a href="'.$_SESSION['return_link'].'" class="back-link">« Назад в:&nbsp;'.$this->name.'</a>';
		
		$vars['message'] = '';	
		$dir=Dir::createDir($this->params['edit']);	
		if(isset($_POST['update']))$vars['message'] = $this->product->save();
        if(isset($_POST['dell'],$_POST['photo_id']))
        {
			$count=count($_POST['photo_id']) - 1;
            for($i=0; $i<=$count; $i++)
            {
                $this->db->query("DELETE FROM `product_photo` WHERE `id`=?", array($_POST['photo_id'][$i]));
				$this->model->photo_del($dir['1'], $_POST['photo_id'][$i]);
            }
            $message = messageAdmin('Запись успешно удалена');
        }
        elseif(isset($this->params['dellone'])&& $this->params['dellone']!='')
        {
            $id = $this->params['dellone'];
            if($this->db->query("DELETE FROM `product_photo` WHERE `id`=?", array($id)))$message = messageAdmin('Запись успешно удалена');
			$this->model->photo_del($dir['1'], $id);
        }

		$vars['edit'] = $this->product->find((int)$this->params['edit']);
		
		/////Load meta
		$row = $this->meta->load_meta($this->tb, $vars['edit']['url']);
		if($row)
		{
			$vars['edit']['title'] = $row['title'];	
			$vars['edit']['keywords'] = $row['keywords'];	
			$vars['edit']['description'] = $row['description'];	
		}
		
		
		//////////Params set
		$params='';
		$params_set='';
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('params'));
		if($row)
		{
            $params = Params::getObject($this->sets)->find(array('where'=>"(pc2.product_id='{$vars['edit']['id']}' AND sub IS NULL) OR sub IS NOT NULL",
                                                    					 'join'=>'LEFT JOIN params_catalog pc ON pc.params_id=tb.id
																		 		  LEFT JOIN product_catalog pc2 ON pc.catalog_id=pc2.catalog_id',
																		 'type'=>'rows',
																		 'group'=>'tb.id',
																		 'order'=>'tb.sub ASC, tb.sort ASC'));
			
			$params_set = $this->db->rows("SELECT * FROM `params_product` WHERE product_id=?", array($vars['edit']['id']));	
			$vars['params'] = $this->view->Render('params.phtml', array('params'=>$params, 'params_set'=>$params_set));								  
		}	
		
		//////////Brends set
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('brend'));
		if($row)
		{
			$vars['brend'] = Brend::getObject($this->sets)->find(array('select'=>'tb.id, tb_lang.name', 'type'=>'rows', 'order'=>'tb.sort'));				  
		}	
		
		////Show tab comment
		$this->comments = new Comments($this->sets);
		$vars['comments']=$this->comments->list_comments_admin($vars['edit']['id'], $this->tb);
		
		/////Catalog
		$vars['catalog'] = Catalog::getObject($this->sets)->find(array('select'=>'tb.*, tb_lang.*, tb2.product_id', 
																	   'join'=>" LEFT JOIN `product_catalog` tb2 ON tb.id=tb2.catalog_id AND tb2.product_id='{$this->params['edit']}'", 
																	   'group'=>'tb.id',
																	   'order'=>'tb.sort',
																	   'type'=>'rows'));
		
		/////Catalog
        $vars['catalog2'] = Catalog::getObject($this->sets)->find(array('order'=>'tb.sort',
																	   'type'=>'rows'));
																	   
		/////Catalog set							  
		$vars['status'] = $this->db->rows("SELECT * FROM `product_status` tb
                                          LEFT JOIN `product_status_set` tb2
                                          ON tb.id=tb2.status_id AND product_id=?",
            array($vars['edit']['id']));
		
		/////////Price
		$res = $this->db->rows("SELECT * FROM price WHERE `product_id`=? ORDER BY sort ASC, price_type_id ASC, id DESC", array($vars['edit']['id']));	
		$price_type = $this->db->rows("SELECT * FROM price_type ORDER BY id ASC");						
		$price_rows = $this->view->Render('price_row.phtml', array('price'=>$res, 'price_type'=>$price_type));
		$vars['price'] = $this->view->Render('price.phtml', array('edit'=>$vars['edit'], 'price'=>$price_rows, 'params'=>$params, 'params_set'=>$params_set));
		
		$vars['currency'] = $this->db->row("SELECT icon FROM currency WHERE `base`='1'");
        $vars['providers'] = $this->db->rows("SELECT * FROM providers ORDER BY id ASC");
		
		////Загрузка фоток для текушего альбома
		
		
		
		$vars['height']=$this->settings['height_product'];	
		$vars['width']=$this->settings['width_product'];
		$vars['height_extra']=$this->settings['height_product_extra'];	
		$vars['width_extra']=$this->settings['width_product_extra'];
		
		$vars['action']=$this->tb;
		$vars['action2']='product_photo';
		$vars['path']=$dir[1];
        $photo = $this->product->getPhotoProduct($vars['edit']['id']);
		$vars['photo'] = $this->view->Render('extra_photo_one2.phtml', array('photo'=>$photo, 'action'=>$this->tb, 'path'=>$vars['path'], 'sub_id'=>$vars['edit']['id']));
        $vars['photo'] = $this->view->Render('extra_photo.phtml', $vars);

		$data['styles']=array('default.css', 'uploadify.css', 'bootstrap.css');
		$data['scripts']=array('swfobject.js', 'jquery.uploadify.v2.1.4.min.js', 'bootstrap-modal.js', 'jquery.searchabledropdown.js');
		//////////////
		
		$data['content'] = $this->view->Render('edit.phtml', $vars);
		return $this->Index($data);
	}
	
	public function pricetypeAction()
	{
		$vars['message'] = '';
		if(isset($_POST['update']))
		{
			$vars['message'] = $this->product->save_pricetype();
		}
		elseif(isset($this->params['addpricetype']))
		{
			$vars['message'] = $this->product->addpricetype();
		}
		elseif(isset($this->params['delete'])||isset($_POST['delete']))
		{
			$vars['message'] = $this->product->delete('price_type');
		}
		
		$vars['name'] = 'Типы цен';
		$vars['action'] = $this->tb;
		$vars['path'] = '/act/pricetype';
		$vars['list'] = $this->db->rows("SELECT * FROM `price_type` ORDER BY id ASC");
		$data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'pricetype', 'menu2'=>$this->left_menu));
		$data['content'] = $this->view->Render('price_type.phtml', $vars);
		return $data;
	}

	
	/////Product extra photo view
    function photoproductAction()
    {
		if(isset($_REQUEST['id']))
		{
			$res = $this->db->rows("SELECT * FROM `product_photo` tb
									LEFT JOIN `".$this->key_lang."_product_photo` tb2
									ON tb.id=tb2.product_photo_id
									WHERE product_id=?
									ORDER BY sort ASC",
			array($_REQUEST['id']));
			
			$this->registry->set('admin', 'product');
			echo $this->view->Render('photoproduct.phtml', array('photo'=>$res, 'id'=>$_REQUEST['id']));
		}
    }
	
	function addpriceAction()
    {
		if(isset($_POST['id']))
		{
			$this->registry->set('admin', 'product');


			if($_POST['rel']=='add')
			{
				$vars = $this->product->addPrice($_POST['id'], $_POST['rel']);
				$vars['rel']='add';
			}
			else $vars = $this->product->addPrice($_POST['id']);

            $vars['def'] = $this->db->row("SELECT * FROM providers WHERE `default`='1'");
            $vars['def']=$vars['def']['price'];
			$data['content']=$this->view->Render('price_row.phtml', $vars);
			return json_encode($data);
		}
    }
	
	function delpriceAction()
    {
		if(isset($_POST['id']))
		{
			$this->registry->set('admin', 'product');
			$vars = $this->product->delPrice($_POST['id']);
			$data['content']=$this->view->Render('price_row.phtml', $vars);
			return json_encode($data);
		}
    }
	
	function configpriceAction()  
    {
		if(isset($_POST['id'], $_POST['product_id']))
		{
			$this->registry->set('admin', 'product');
			$vars = $this->product->configPrice($_POST['id'], $_POST['product_id']);
			$vars['id']=$_POST['id'];
			$vars['photo_id']=$_POST['photo_id'];
			$vars['texture_id']=$_POST['texture_id'];
			$data['content']=$this->view->Render('price_config.phtml', $vars);
			return json_encode($data);
		}
    }
	
	function otherproductAction()
    {
		if(isset($_POST['id']))
		{
			
			if(isset($_POST['product_id']))
			{
				$row = $this->db->row("SELECT tb.*, tb2.name 
									   FROM product tb
									   
									   LEFT JOIN ".$this->key_lang."_product tb2
									   ON tb.id=tb2.product_id 
									   
									   WHERE tb.`id`='{$_POST['id']}'");
				
				$row2 = $this->db->row("SELECT product_id2 FROM product_other WHERE product_id2=? AND product_id=?", array($_POST['product_id'], $_POST['id']));	
				$param = array($_POST['product_id'], $_POST['id']);		   
				if(!$row2)$this->db->query("INSERT INTO product_other SET product_id2=?, `product_id`=?", $param);
			}
			elseif(isset($_POST['del']))
			{
				$param = array($_POST['del'], $_POST['id']);
				$this->db->query("DELETE FROM product_other WHERE product_id2=? AND `product_id`=?", $param);
			}
			$res = $this->db->rows("SELECT tb.*, tb2.name, tb3.code
									FROM product_other tb
									LEFT JOIN ru_product tb2
									ON tb.product_id2=tb2.product_id
									LEFT JOIN product tb3
									ON tb3.id=tb2.product_id
									WHERE tb.product_id=?
									
									GROUP BY tb2.product_id", array($_POST['id']));
			
			$this->registry->set('admin', 'product');
			$data=array();
			$data['content']=$this->view->Render('otherproduct.phtml', array('product'=>$res));
			return json_encode($data);
		}
    }
	
	function orderProduct2Action()
    {
		if($_POST['id'])
		{
			$data=array();
			$data['content']='<select id="product_add2" name="'.$_POST['id'].'" style="width:300px;"><option value="0">Выберите товар...</option>';
			$q="SELECT
                tb.*,
                tb2.name
				
				 FROM product tb
	
					LEFT JOIN ".$this->key_lang."_product tb2
					ON tb2.product_id=tb.id
	
					LEFT JOIN product_catalog tb3
					ON tb3.product_id=tb.id
					
					LEFT JOIN catalog tb4
					ON tb3.catalog_id=tb4.id
	
				 WHERE tb.active='1' AND (tb4.id=? OR tb4.sub=?) AND tb2.product_id!=?
				 GROUP BY tb.id
				 ORDER BY tb.`sort` ASC, tb.id DESC";//echo $q;
			$res = $this->db->rows($q, array($_POST['id'], $_POST['id'], $_POST['id2']));
			if(count($res)!=0)
			{
				foreach($res as $row)
				{
					$data['content'].='<option value="'.$row['id'].'">'.$row['name'].' ('.$row['code'].')</option>';
				}
			}
			else $data['content'].='<option value="0">Товаров нет...</option>';
			$data['content'].='</select>';
			
			return json_encode($data);
		}
    }


    public function providerAction()
    {
        $vars['message'] = '';
        if(isset($_POST['update']))
        {
            $vars['message'] = $this->product->save_provider();
        }
        elseif(isset($this->params['add_provider']))
        {
            $vars['message'] = $this->product->add_provider();
        }
        elseif(isset($this->params['delete']) || isset($_POST['delete']))
        {
            $vars['message'] = $this->product->delete('provider');
        }
        $vars['name'] = 'Поставщики';
        $vars['action'] = $this->tb;
        $vars['path'] = '/act/provider';
        $vars['list'] = $this->db->rows("SELECT * FROM `providers` ORDER BY `id` ASC");
        $data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'provider', 'menu2'=>$this->left_menu));
        $data['content'] = $this->view->Render('provider.phtml', $vars);
        return $data;
    }

    public function getproviderAction()
    {
        $row = $this->db->row("SELECT * FROM providers WHERE `id`='{$_POST['id']}'");
        return json_encode($row);
    }
}
?>