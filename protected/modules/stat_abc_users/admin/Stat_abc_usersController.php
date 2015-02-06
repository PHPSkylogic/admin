<?php
/*
 * вывод каталога компаний и их данных
 */
class Stat_abc_usersController extends BaseController{
	
	protected $params;
	protected $db;
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "stat_abc_users";
        $this->name = "ABC-анализ покупателей";
		$this->registry = $registry;
        $this->stat = new Stat_abc_users($this->sets);
        $this->cntDate=5;
	}

	public function indexAction()
	{
        if(isset($this->params['subsystem']))return $this->Index($this->stat->subsystemAction());

        if(!isset($_SESSION['orderStatAbc']))$_SESSION['orderStatAbc'] = 'DESC';
        if(isset($_POST['start']))
        {
            $_SESSION['date_start'.$this->cntDate] = $_POST['start'];
            $_SESSION['date_end'.$this->cntDate] = $_POST['end'];

            $_SESSION['orderStatAbc'] = $_POST['order'];
        }
        elseif(!isset($_SESSION['date_start'.$this->cntDate]))
        {
            $date = $this->model->setDateStat();
            $_SESSION['date_start'.$this->cntDate] = $date['start'];
            $_SESSION['date_end'.$this->cntDate] = $date['end'];
        }
        $vars['date_start'] = $_SESSION['date_start'.$this->cntDate];
        $vars['date_end'] = $_SESSION['date_end'.$this->cntDate];
        $vars['compare']='';
        $vars['all'] = $this->db->row("SELECT SUM(tb1.amount) AS amount, SUM(tb1.sum) AS total
                                       FROM orders_product tb1

                                       LEFT JOIN orders
                                       ON orders.id=tb1.orders_id

                                       RIGHT JOIN users
                                       ON users.id=orders.user_id OR users.email=orders.email

                                       WHERE orders.date_add>='{$vars['date_start']}' AND orders.date_add<='{$vars['date_end']}'");

        $discount = Numeric::discount(80, $vars['all']['total']);
        $vars['pareto'] = $vars['all']['total'] - $discount;

        if(!isset($_SESSION['paretoA']))
        {
            $_SESSION['paretoA'] = 50;
            $_SESSION['paretoB'] = 30;
            $_SESSION['paretoC'] = 20;
        }

        if(isset($_POST['paretoA']))
        {
            if($_POST['paretoA']+$_POST['paretoB']+$_POST['paretoC']!=100)
            {
                $vars['message']= messageAdmin('Сумма процентов должна быть равна 100%', 'error');
            }
            else{
                $_SESSION['paretoA'] = $_POST['paretoA'];
                $_SESSION['paretoB'] = $_POST['paretoB'];
                $_SESSION['paretoC'] = $_POST['paretoC'];
            }
        }

        $vars['paretoA'] = $vars['pareto'] - Numeric::discount($_SESSION['paretoA'], $vars['pareto']);
        $vars['paretoB'] = $vars['pareto'] - Numeric::discount($_SESSION['paretoB'], $vars['pareto']);
        $vars['paretoC'] = $vars['pareto'] - Numeric::discount($_SESSION['paretoC'], $vars['pareto']);



        $order="total";
        if(isset($this->params['order']))
        {
            if($this->params['order']=='amount')
            {
                $order="amount {$_SESSION['orderStatAbc']}, total";
            }
            else $order=$this->params['order'];
        }
        $vars['product'] = $this->db->rows("SELECT tb1.*,SUM(tb1.amount) AS amount, SUM(tb1.sum) AS total, users.id AS user_id, users.name, users.email
                                            FROM orders_product tb1

                                            LEFT JOIN orders
                                            ON orders.id=tb1.orders_id

                                            RIGHT JOIN users
                                            ON users.id=orders.user_id OR users.email=orders.email

                                            WHERE orders.date_add>='{$vars['date_start']}' AND orders.date_add<='{$vars['date_end']}'
                                            GROUP BY users.id
                                            ORDER BY $order ".$_SESSION['orderStatAbc'].', tb1.name ASC');
        $vars['view'] = $this->view->Render('view.phtml', $vars);
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

        if(!isset($_SESSION['date_start'.$this->cntDate]))
        {
            $date = $this->model->setDateStat();
            $_SESSION['date_start'.$this->cntDate] = $date['start'];
            $_SESSION['date_end'.$this->cntDate] = $date['end'];
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