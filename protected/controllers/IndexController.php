<?php
/**
* class to auntificate admin
* @author
*/
 
class IndexController extends BaseController{

    protected $params;
    protected $registry;

    function __construct ($registry, $params)
    {
        parent::__construct($registry, $params);
        $this->registry = $registry;
		$this->catalog = new Catalog($this->sets);
		$this->product = new Product($this->sets);
		$this->product_status = new Product_status($this->sets);
    }

    function indexAction()
    {
		$vars['body'] = $this->model->getPage('/');
		
		$vars['catalog'] = $this->catalog->find(array('type'=>'rows',
											  'select'=>'tb.id, tb.url, tb.sub, tb_lang.name, tb.photo',
											  'where'=>"tb.active='1' AND tb.position='1'",
											  'group'=>'tb.id',
											  'order'=>'tb.sort ASC, tb.id DESC'));
															  

		////Novelty products
		$status['novelty'] = $this->product_status->find(6);
		$status['action'] = $this->product_status->find(7);
		$status['hit'] = $this->product_status->find(9);
		
		$q=$this->catalog->queryProducts(array('where'=>"AND tb4.status_id='6'"));
		$novelty = $this->product->find($q);
		
		$q=$this->catalog->queryProducts(array('where'=>"AND tb4.status_id='7'"));
		$action = $this->product->find($q);
		
		$q=$this->catalog->queryProducts(array('where'=>"AND tb4.status_id='9'"));
		$hit = $this->product->find($q);
		
		$vars['slider2'] = $this->view->Render('slider2.phtml', array('novelty'=>$novelty, 
																	  'hit'=>$hit, 
																	  'action'=>$action,
																	  'status'=>$status, 
																	  'translate'=>$this->translation));				
		
		$slider = Slider::getObject($this->sets)->find(array('where'=>"tb.active='1'", 'type'=>'rows', 'order'=>'tb.sort ASC'));
		$vars['slider'] = $this->view->Render('slider.phtml', array('slider'=>$slider, 'translate'=>$this->translation, 'settings'=>$this->settings));


        $news = News::getObject($this->sets)->find(array('where'=>'__tb.active:=1__',
            'type'=>'rows',
            'order'=>'tb.date DESC',
            'limit'=>$this->settings['limit_news_block']));

        $vars['news'] = $this->view->Render('news_block.phtml', array('news'=>$news, 'translate'=>$this->translation));
        #!End News blocks

        $data['styles'] = array( 'catalog.css', 'local.css');
        $data['scripts'] = array( 'jquery.jcarousel.min.js', 'jquery.waterwheelCarousel.js');
        $data['content'] = $this->view->Render('main.phtml', $vars);
        return $this->Index($data);
	}
}
?>