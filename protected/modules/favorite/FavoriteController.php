<?php
/*
 * вывод каталога компаний и их данных
 */
class FavoriteController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "favorites";
		$this->name = "Избранное";
		
		$this->registry = $registry;
		$this->filters = new Params($this->sets);
		$this->catalog = new Catalog($this->sets);
		$this->product = new Product($this->sets);
	}

	public function indexAction()
	{
		
		$vars['type']=0;
		if(isset($this->params['favorite']))$vars['type']=$this->params['favorite'];
		
		if(isset($this->params['del']))
		{
			$this->db->query("DELETE FROM favorites WHERE product_id=? AND session_id=? AND type=?", array($this->params['del'], session_id(), $vars['type']));
			
		}
		
		$q=$this->catalog->queryProducts(array('where'=>"AND __c.session_id:=".session_id()."__ AND __c.type:=".$vars['type']."__",
											   'join'=>'LEFT JOIN favorites c ON c.product_id=tb.id ',
											  // 'select'=>',  c.id as compare_id',
											   ));
		$vars['product'] = $this->product->find($q);
		
		
		$where='';		
		foreach($vars['product'] as $row)
		{
			if($where!='')$where.=" OR ";
			$where.="tb.id='{$row['catalog_id']}'";
		}
		
		if($where!='')
		{
			$vars['catalog'] = $this->catalog->find(array('type'=>'rows',
														  'select'=>'tb.id, tb.url, tb.sub, tb_lang.name',
														  'where'=>"tb.active='1' AND ($where)",
														  'group'=>'tb.id',
														  'order'=>'tb.sort ASC, tb.id DESC'));
		}
		$vars['translate'] = $this->translation;
		$data['styles'] = array('catalog.css');
		$data['content'] = $this->view->Render('favorites.phtml', $vars);
		return $this->Index($data);
	}

	function addAction()
    {
		if(isset($_POST['id'],$_POST['cat_id']))
		{
			$_POST['cat_id']=str_replace('licat_','',$_POST['cat_id']);
			$this->db->query("DELETE FROM compare WHERE session_id=? AND catalog_id!=?", array(session_id(), $_POST['cat_id']));
			
			$param=array(session_id(), $_POST['cat_id'], $_POST['id']);
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
							  ", array(session_id()));
		if(isset($res[0]))
		{
			return $this->view->Render('compare_block.phtml', array('compare'=>$res, 'translate'=>$this->translation));
		}
	}
}
?>