<?php
/*
 * вывод каталога компаний и их данных
 */
class Product_priceController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "price_changelog";
		$this->name = "Ценообразование";
		$this->registry = $registry;
        $this->product_price = new Product_price($this->sets);
		$this->catalog = new Catalog($this->sets);
	}

	public function indexAction()
	{
		$this->left_menu = array();
		if(isset($this->params['act']))
		{
			$act=$this->params['act'].'Action';
			return $this->Index($this->$act());
		}
		
		$vars['message'] = '';
		$vars['name'] = $this->name;
		$_SESSION['return_link']=$_SERVER['REQUEST_URI'];
		
		if(isset($this->registry['access']))$vars['message'] = $this->registry['access'];
		elseif(isset($_POST['add']))$vars['message'] = $this->product_price->add();
		if(isset($this->params['delete'])||isset($_POST['delete']))$vars['message'] = $this->product_price->delete();
		
		$vars['list'] = $this->view->Render('view.phtml', $this->product_price->listView());

		$vars['catalog'] = Catalog::getObject($this->sets)->find(array('type'=>'rows',
																	   'group'=>'tb.id',
																	   'order'=>'tb.sort'));
		
		$vars['price_type'] = $this->db->rows("SELECT * FROM price_type ORDER BY `default` DESC, id DESC");
		$data['content'] = $this->view->Render('list.phtml', $vars);
		return $this->Index($data);
	}
	
}
?>