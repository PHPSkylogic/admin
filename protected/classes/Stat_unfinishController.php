<?php
/*
 * вывод каталога компаний и их данных
 */
class Stat_unfinishController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "Stat_unfinish";
        $this->name = "Незавершенные заказы";
		$this->registry = $registry;
        $this->stat = new Stat_unfinish($this->sets);

        $this->cntDate=4;
	}

	public function indexAction()
	{
        if(isset($this->params['subsystem']))return $this->Index($this->stat->subsystemAction());


        if(isset($_POST['start']))
        {
            $_SESSION['date_start'.$this->cntDate] = $_POST['start'];
            $_SESSION['date_end'.$this->cntDate] = $_POST['end'];echo $_SESSION['date_end'.$this->cntDate];
        }
        elseif(!isset($_SESSION['date_start']))
        {
            $date = $this->model->setDateStat();
            $_SESSION['date_start'.$this->cntDate] = $date['start'];
            $_SESSION['date_end'.$this->cntDate] = $date['end'];
        }


        $vars['graph'] = $this->stat->viewGraph($_SESSION['date_start'.$this->cntDate], $_SESSION['date_end'.$this->cntDate]);
		$vars['message'] = '';
        $vars['name'] = $this->name;

        ## Start Statistics

        $data['styles'] = array('jquery.simple-dtpicker.css');
        $data['scripts'] = array('jquery.simple-dtpicker.js');
        $data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name));
		$data['content'] = $this->view->Render('list.phtml', $vars);
		return $this->Index($data);
	}


    public function compareAction()
    {
        if(isset($_POST['startcompare']))
        {
            $_SESSION['date_start_c'.$this->cntDate] = $_POST['startcompare'];
            $_SESSION['date_end_c'.$this->cntDate] = $_POST['endcompare'];
        }
        elseif(isset($_POST['start']))
        {
            $_SESSION['date_start'.$this->cntDate] = $_POST['start'];
            $_SESSION['date_end'.$this->cntDate] = $_POST['end'];
        }
        if(!isset($_SESSION['date_start_c'.$this->cntDate]))
        {
            $cur_start_date = getdate(mktime(0, 0, 0, substr($_SESSION['date_start'.$this->cntDate],5,2)+1, substr($_SESSION['date_start'.$this->cntDate],8,2), substr($_SESSION['date_start'.$this->cntDate],0,4)));
            if(strlen($cur_start_date['mon'])==1)$cur_start_date['mon'] = '0'.$cur_start_date['mon'];
            if(strlen($cur_start_date['mday'])==1)$cur_start_date['mday'] = '0'.$cur_start_date['mday'];
            $_SESSION['date_start_c'.$this->cntDate] = $cur_start_date['year'].'-'.$cur_start_date['mon'].'-'.$cur_start_date['mday'].' 00:00:00';

            $cur_end_date = getdate(mktime(0, 0, 0, substr($_SESSION['date_start'.$this->cntDate],5,2)+2, substr($_SESSION['date_start'.$this->cntDate],8,2), substr($_SESSION['date_start'.$this->cntDate],0,4)));
            if(strlen($cur_end_date['mon'])==1)$cur_end_date['mon'] = '0'.$cur_end_date['mon'];
            if(strlen($cur_end_date['mday'])==1)$cur_end_date['mday'] = '0'.$cur_end_date['mday'];
            $_SESSION['date_end_c'.$this->cntDate] = $cur_end_date['year'].'-'.$cur_end_date['mon'].'-'.$cur_end_date['mday'].' 23:59:59';
        }
        //echo $_SESSION['date_start_c'].' = '.$_SESSION['date_end_c'];

        if(isset($this->params['type'])&&$this->params['type']==0)
        {
            $start = $_SESSION['date_start'.$this->cntDate];
            $end = $_SESSION['date_end'.$this->cntDate];
            $compare='';
        }
        else{
            $start = $_SESSION['date_start_c'.$this->cntDate];
            $end = $_SESSION['date_end_c'.$this->cntDate];
            $compare='compare';
        }
        $vars['graph'] = $this->stat->viewGraph($start, $end, $compare);
        echo $this->view->Render('graph_body.phtml', $vars);
    }


}
?>