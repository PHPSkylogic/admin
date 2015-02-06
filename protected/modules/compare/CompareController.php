<?php
/*
 * вывод каталога компаний и их данных
 */
class CompareController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "compare";
		$this->name = "Сравнение";
		
		$this->registry = $registry;
		$this->filters = new Params($this->sets);
		$this->catalog = new Catalog($this->sets);
		$this->product = new Product($this->sets);
	}

	public function indexAction()
	{
		$vars['translate'] = $this->translation;///Переводы интерфейса
		if(isset($this->params['del']))
		{
			$this->db->query("DELETE FROM compare WHERE id=?", array($this->params['del']));
			
		}
		$q=$this->catalog->queryProducts(array('where'=>"AND __c.session_id:=".$_COOKIE['session_id']."__",
											   'join'=>'LEFT JOIN compare c ON c.product_id=tb.id 
											   			LEFT JOIN catalog ON c.catalog_id=catalog.id ',
											   'select'=>', c.catalog_id, c.id as compare_id, catalog.url  AS cat_url'));
		$vars['product'] = $this->product->find($q);
	
		$where='';
		foreach($vars['product'] as $row)
		{
			if($where!='')$where.=" OR ";
			$where.="tb2.product_id='{$row['id']}'";
		}
		if($where!='')
		{
			$vars['params'] = $this->filters->find(array("join"=>" LEFT JOIN `params_product` tb2 
															   ON tb2.params_id=tb.id
															   
															   LEFT JOIN params ON params.id=tb.sub
															   
															   LEFT JOIN ".$this->key_lang."_params params_lang 
															   ON params.id=params_lang.params_id
															  ",
																
													  "select"=>"tb.*, tb_lang.*, params_lang.name as sub_name, tb2.product_id, params.id as parent_id, 
													  			 GROUP_CONCAT(DISTINCT tb_lang.name ORDER BY tb_lang.name ASC SEPARATOR ', ') as name",
													  "where"=>"tb.active='1' AND tb.sub IS NOT NULL AND params.compare='1' AND ($where)",
													  "type"=>"rows",
													  "group"=>"params.id,tb2.product_id"));
													  
													  
			$vars['params_parent'] = $this->filters->find(array("where"=>"tb.active='1' AND tb.sub IS NULL",
													  			"type"=>"rows",
																"order"=>"sort ASC, name ASC"));
			//var_info($vars['params_parent']);
			
			$data['breadcrumbs'] = $this->model->getBreadCat($vars['product'][0]['catalog_id'], 'Сравнение товаров');
		}
		$data['styles'] = array('catalog.css');
		$data['content'] = $this->view->Render('compare.phtml', $vars);
		return $this->Index($data);
	}

	function addAction()
    {
		if(isset($_POST['id'],$_POST['cat_id']))
		{
			$_POST['cat_id']=str_replace('licat_','',$_POST['cat_id']);
			$this->db->query("DELETE FROM compare WHERE session_id=? AND catalog_id!=?", array($_COOKIE['session_id'], $_POST['cat_id']));
			
			$param=array($_COOKIE['session_id'], $_POST['cat_id'], $_POST['id']);
			$row=$this->db->row("SELECT * FROM compare WHERE session_id=? AND catalog_id=? AND product_id=?", $param);
			if(!$row)
			{
				$this->db->query("INSERT INTO compare SET session_id=?, catalog_id=?, product_id=?", $param);
				$return['message']='Товар добавлен для сравнения!';
				$return['add']=1;
			}
			else $return['message']='Товар уже есть в таблице сравнений!';
			return json_encode($return);
		}
	}
	
	function delAction()
    {
		if(isset($_POST['id']))
		{
			$_POST['id']=str_replace('compare','',$_POST['id']);
			$this->db->query("DELETE FROM compare WHERE id=?", array($_POST['id']));
		}
	}
	
	function loadAction()
    {
		$res=$this->db->rows("SELECT c.*, p2.name, p.url
							  FROM compare c
							  
							  LEFT JOIN product p
							  ON p.id=c.product_id
							  
							  LEFT JOIN ".$this->key_lang."_product p2
							  ON p.id=p2.product_id
							  
							  WHERE session_id=?
							  ORDER BY c.id DESC
							  ", array($_COOKIE['session_id']));
		if(isset($res[0]))
		{
			return $this->view->Render('compare_block.phtml', array('compare'=>$res, 'translate'=>$this->translation));
		}
	}
}
?>