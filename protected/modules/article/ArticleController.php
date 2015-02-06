<?php
/*
 * вывод каталога компаний и их данных
 */
class ArticleController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "article";
		$this->registry = $registry;
		$this->article = new Article($this->sets);
	}
	
	public function indexAction()
	{
		$vars['translate'] = $this->translation;
		if(!isset($this->params[$this->tb])||$this->params[$this->tb]=='all')
		{
			$vars['list'] = $this->article->find(array('where'=>'__tb.active:=1__', 
													'paging'=>$this->settings['paging_article'], 
													'order'=>'tb.date DESC'));
		}
		else{
			$vars['article'] = $this->article->find(array('where'=>'__tb.url:='.$this->params[$this->tb].'__ AND __tb.active:=1__'));
			if(!isset($vars['article']['id']))return Router::act('error', $this->registry);
			
			$data['meta'] = $vars['article'];////Meta
			$vars['other'] = $this->article->find(array('where'=>'__tb.id!:='.$vars['article']['id'].'__ AND __tb.active:=1__', 
													 'type'=>'rows', 
													 'order'=>'tb.date DESC', 
													 'limit'=>5));

			if(isset($this->settings['comment_article'])&&$this->settings['comment_article']==1)	 
				$vars['comments'] = Comments::getObject($this->sets)->list_comments($vars['article']['id'], $this->tb);
			//var_info($vars['other']);
		}
		
		$data['content'] = $this->view->Render($this->tb.'.phtml', $vars);
		return $this->Index($data);
	}
}
?>