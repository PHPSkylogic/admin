<?php
/*
 * Admin
 * Catalog Controller
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
		$this->params2 = new Params($this->sets);
		$this->catalog = new Catalog($this->sets);
	}

	public function indexAction()
	{
		if(isset($this->params['subsystem']))return $this->Index($this->catalog->subsystemAction());
        if(isset($_POST['sort_cat']))$_SESSION['sort_cat']=$_POST['sort_cat'];
        if(!isset($_SESSION['sort_cat']))$_SESSION['sort_cat']=0;
		$vars['message'] = '';
		$vars['name'] = $this->name;
		if(isset($this->registry['access']))$vars['message'] = $this->registry['access'];

		if(isset($this->params['delete'])||isset($_POST['delete']))$vars['message'] = $this->catalog->delete();
		elseif(isset($_POST['update']))$vars['message'] = $this->catalog->save();
		elseif(isset($_POST['update_close']))$vars['message'] = $this->catalog->save();
		elseif(isset($_POST['add_open']))$vars['message'] = $this->catalog->add(true);

        //Вывод списка в <select>-e
        $vars['catalog'] = $this->catalog->find(array('type'=>'rows','order'=>'tb.sort ASC'));

        //Фильтрация согласно выбраного родителя в <select>-e
        $where="tb.sub is NULL";
        
        $vars['url']='/admin/'.$this->tb;
        if(isset($this->params['cat']))
        {
            $row = $this->catalog->find($this->params['cat']);
            if($row)$where="tb.sub='{$row['id']}' ";
            $_SESSION['sort_cat']=$row['id'];
            $vars['url'].='/cat/'.$this->params['cat'];
        }
        else $_SESSION['sort_cat']=0;
        
        //Вывод списка каталогов
        $vars['list'] = $this->view->Render('view.phtml', array(
                        'url'=>$vars['url'],
                        'list' => $this->catalog->find(array('type'=>'rows','where'=>$where, 'order'=>'tb.sort ASC')))
        );
        
        
        $vars['catalog'] = $this->catalog->find(array('select'=>'tb.*, tb_lang.name',
													   'group'=>'tb.id',
													   'order'=>'tb.sort',
													   'type'=>'rows'));
        $settings = array('arr'=>$vars['catalog'], 'link'=>'/admin/catalog/cat/', 'id'=>'tree');
        $data['left_menu'] = $this->view->Render('cat_menu.phtml', array('cat_menu'=>Arr::treeview($settings)));
		$data['left_menu'] .=  $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name));
        $data['styles']=array('jquery.treeview.css');
		$data['scripts']=array('jquery.treeview.js');
        
		$data['content'] = $this->view->Render('list.phtml', $vars);
		return $this->Index($data);
	}
	
	public function addAction()
	{
		$vars['message'] = '';
		if(isset($_POST['add']))$vars['message'] = $this->catalog->add();
        $vars['catalog'] = $this->catalog->find(array('type'=>'rows','order'=>'tb.sort ASC'));
		$vars['params'] = $this->params2->find(array( 'type'=>'rows',
													  'group'=>'tb.id',
													  'order'=>'tb.sort ASC, name ASC'));	
															  
		$vars['height']=$this->settings['height_catalog'];	
		$vars['width']=$this->settings['width_catalog'];
		
		$data['content'] = $this->view->Render('add.phtml', $vars);
		return $this->Index($data);
	}
	
	public function editAction()
	{
		$vars['message'] = '';
		if(isset($_POST['update']))$vars['message'] = $this->catalog->save();

		$vars['edit'] = $this->catalog->find((int)$this->params['edit']);
		
		/////Load meta
		$row = $this->meta->load_meta($this->tb, $vars['edit']['url']);
		if($row)
		{
			$vars['edit']['title'] = $row['title'];	
			$vars['edit']['keywords'] = $row['keywords'];	
			$vars['edit']['description'] = $row['description'];	
		}
		
        $vars['catalog'] = $this->catalog->find(array('type'=>'rows','order'=>'tb.sort ASC'));
		
		$vars['params'] = $this->params2->find(array(
													  'join'=>"LEFT JOIN params_catalog pc ON pc.params_id=tb.id AND pc.catalog_id='{$this->params['edit']}'",
													  'type'=>'rows',
													  'where'=>'sub IS NULL',
													  'group'=>'tb.id',
													  'order'=>'tb.sort ASC, name ASC'));	


		$vars['height']=$this->settings['height_catalog'];	
		$vars['width']=$this->settings['width_catalog'];
			
		$data['content'] = $this->view->Render('edit.phtml', $vars);
		return $this->Index($data);
	}
}
?>