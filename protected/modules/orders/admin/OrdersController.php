<?php
/*
 * вывод каталога компаний и их данных
 */
class OrdersController extends BaseController{
	
	protected $params;
	protected $db;
	private $left_menu = array(array('title'=>'Незавершенные заказы', 
									 'url'=>'/admin/orders/act/incomplete', 
									 'name'=>'incomplete'),
                                array('title'=>'Статусы заказов',
                                    'url'=>'/admin/orders/act/orders_status',
                                    'name'=>'orders_status'),
                                array('title'=>'Экспорт данных',
                                    'url'=>'/admin/orders/act/export',
                                    'name'=>'export'),

                            array('title'=>'Экспорт поставщикам',
                                'url'=>'/admin/orders/act/exportProducts',
                                'name'=>'exportProducts'));
	
	function  __construct($registry, $params)
	{
		parent::__construct($registry, $params);
		$this->tb = "orders";
		$this->name = "Заказы";
		$this->registry = $registry;
		$this->orders = new Orders($this->sets);
	}

	public function indexAction()
	{
		$vars['message'] = '';
		$vars['name'] = $this->name;
		if(isset($this->params['act']))
		{
			$act=$this->params['act'].'Action';
			return $this->Index($this->$act());
		}

        if(isset($this->params['xml'])&&is_numeric($this->params['xml']))
        {
            $this->orders->orderInXml($this->params['xml']);
        }

		if(isset($this->params['subsystem']))return $this->Index($this->orders->subsystemAction($this->left_menu));
		if(isset($this->registry['access']))$vars['message'] = $this->orders->registry['access'];
		if(isset($this->params['delete'])||isset($_POST['delete']))$vars['message'] = $this->orders->delete();
		elseif(isset($_POST['update']))$vars['message'] = $this->orders->save();
		elseif(isset($_POST['update_close']))$vars['message'] = $this->orders->save();

		elseif(isset($_POST['add_close']))$vars['message'] = $this->orders->add();

		$vars['currency'] = $this->db->row("SELECT icon FROM currency WHERE `base`='1'");
		$vars['status'] = $this->db->rows("SELECT * FROM orders_status ORDER BY id ASC");
		
		
		if(isset($_POST['word']))
        {
			$_SESSION['search_order']['status']=$_POST['status'];
            $_SESSION['search_order']['sort']=$_POST['sort'];
            $_SESSION['search_order']['word']=$_POST['word'];
        }
		
		$where="tb.id!='0' ";
		if(!isset($_SESSION['search_order'])||isset($_POST['clear']))
		{
			$_SESSION['search_order']['status']='';
			$_SESSION['search_order']['cat_id']='';
			$_SESSION['search_order']['word']='';
			$_SESSION['search_order']['sort']='ORDER BY tb.`start_date` DESC';
		}
		
		if($_SESSION['search_order']['status']!='')
		{
			$where.=" AND tb.status_id='{$_SESSION['search_order']['status']}'";	
		}
		/*
		if($_SESSION['search_user']['rating']!='')
		{
			$where.=" AND tb.rating='{$_SESSION['search_user']['rating']}'";	
		}*/
		
		if($_SESSION['search_order']['word']!="")
		 	$where.="AND (tb.username LIKE '%{$_SESSION['search_order']['word']}%' OR 
						  tb.city LIKE '%{$_SESSION['search_order']['word']}%' OR 
						  tb.email LIKE '%{$_SESSION['search_order']['word']}%' OR 
						  tb.phone LIKE '%{$_SESSION['search_order']['word']}%' OR 
						  tb.address LIKE '%{$_SESSION['search_order']['word']}%'
						  )";
		
		$sort='tb.`date_add` DESC';
		
		if(isset($_SESSION['search_order']['sort'])&&$_SESSION['search_order']['sort']!='')
		{
			if($_SESSION['search_order']['sort']=='name asc')$sort="tb.username ASC, tb.id DESC"; 
			elseif($_SESSION['search_order']['sort']=='name desc')$sort="tb.username DESC, tb.id DESC";
			elseif($_SESSION['search_order']['sort']=='email asc')$sort="tb.email ASC, tb.id DESC";
			elseif($_SESSION['search_order']['sort']=='email desc')$sort="tb.email DESC, tb.id DESC";
			elseif($_SESSION['search_order']['sort']=='date asc')$sort="tb.date_add ASC, tb.id DESC";
			
			elseif($_SESSION['search_order']['sort']=='price asc')$sort="`sum` ASC, tb.id DESC";
			elseif($_SESSION['search_order']['sort']=='price desc')$sort="`sum` DESC, tb.id DESC";
		}

		$vars['list'] = $this->orders->find(array('paging'=>true, 
												  'order'=>$sort, 
												  'where'=>$where, 
												  'select'=>'tb.*, tb2.name as status, c.icon, c.position',
												  'join'=>'LEFT JOIN orders_status tb2 ON tb.status_id=tb2.id
												  		   LEFT JOIN currency c ON tb.currency=c.id'));
												  
		$vars['list'] = $this->view->Render('view.phtml', $vars);
		$data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'menu2'=>$this->left_menu));
		$data['content'] = $this->view->Render('list.phtml', $vars);
		return $this->Index($data);
	}
	
	public function addAction()
	{
		$vars['message'] = '';
		if(isset($_POST['add']))$vars['message'] = $this->orders->add();
		
		////Delivery
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('delivery'));
		if($row)$vars['delivery'] = Delivery::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		
		////Payment
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('payment'));
		if($row)$vars['payment'] = Payment::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		$data['content'] = $this->view->Render('add.phtml', $vars);
		return $this->Index($data);
	}
	
	public function editAction()
	{
		$vars['message'] = '';		
		if(isset($this->params['del']))
		{
			$vars['message'] = $this->orders->del_product($this->params['del']);
		}
		
		if(isset($_POST['update']))$vars['message'] = $this->orders->save();
        elseif(isset($_POST['update_send']))
        {
            $vars['message'] = $this->orders->save();
            $vars['message'].=$this->orders->sendOrder2($_POST['id']);
        }
		$vars['status'] = $this->db->rows("SELECT * FROM orders_status");
											
		$vars['catalog'] = Catalog::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		$vars['edit'] = $this->orders->find((int)$this->params['edit']);


		
		////Delivery
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('delivery'));
		if($row)$vars['delivery'] = Delivery::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		
		////Payment
		$row = $this->db->row("SELECT id FROM modules WHERE `controller`=?", array('payment'));
		if($row)$vars['payment'] = Payment::getObject($this->sets)->find(array('type'=>'rows', 'where'=>'__tb.active:=1__', 'order'=>'tb.sort ASC'));
		
		$vars['currency'] = $this->db->row("SELECT icon FROM currency WHERE `base`='1'");
		$vars['currency2'] = $this->db->row("SELECT * FROM currency WHERE `id`='{$vars['edit']['currency']}'");	
			
		$product = $this->db->rows("SELECT tb.*, p.photo, tb.photo AS photo_basket, p.url FROM orders_product tb
											LEFT JOIN product p
											ON p.id=tb.product_id
											WHERE orders_id=?", array($this->params['edit']));
											
		$vars['product'] = $this->view->Render('orderproduct.phtml', array('product'=>$product, 'currency'=>$vars['currency'], 'total'=>$vars['edit']['sum'], 'order_id'=>$vars['edit']['id']));
									
		$data['content'] = $this->view->Render('edit.phtml', $vars);
		return $this->Index($data);
	}
	
	public function incompleteAction()
	{
		$vars = $this->orders->incomplete();
											
		$data['styles'] = array('jquery.simple-dtpicker.css');
		$data['scripts'] = array('jquery.simple-dtpicker.js');									
		$data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'incomplete', 'menu2'=>$this->left_menu));
		$data['content'] = $this->view->Render('incomplete.phtml', $vars);
		return $data;
	}
	
	
	/////Load list product in select
	function orderProductAction()
    {
		return Orders::getObject($this->sets)->orderProduct();
    }
	
	////Load table product view
	function orderProductViewAction()
    {
		$this->registry->set('admin', 'orders');
		$data = Orders::getObject($this->sets)->orderProductView();
		$data['content']=$this->view->Render('orderproduct.phtml', array('product'=>$data['res'], 'total'=>$data['total'], 'currency'=>$data['currency'], 'order_id'=>$_POST['order_id']));
		return json_encode($data);
    }
	
	public function exportAction()
    {
        if(!isset($_SESSION['start_order']))
        {
            $_SESSION['start_order'] = getdate(mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
            if(strlen($_SESSION['start_order']['mon'])==1)$_SESSION['start_order']['mon'] = '0'.$_SESSION['start_order']['mon'];
            if(strlen($_SESSION['start_order']['mday'])==1)$_SESSION['start_order']['mday'] = '0'.$_SESSION['start_order']['mday'];
            $_SESSION['start_order'] = $_SESSION['start_order']['year'].'-'.$_SESSION['start_order']['mon'].'-'.$_SESSION['start_order']['mday'].' 00:00:00';
            $_SESSION['end_order'] = date("Y-m-d").' 23:59:59';
        }


        if(isset($_POST['download']))
        {
            $_SESSION['start_order'] = $_POST['start'];
            $_SESSION['end_order'] = $_POST['end'];
            $selectedColumns = implode(', ', $_POST['columns']);
            $dataSet = $this->orders->find(array('select'=> $selectedColumns,
                                                 'where'=>"date_add>='{$_SESSION['start_order']}' AND date_add<='{$_SESSION['end_order']}'",
                                                 'type'=>'rows'));

            $save = 'export'.strtoupper($_POST['file_format']);
            Export::$save($dataSet);
        }

        $vars['name'] = 'Экспорт - ' . $this->name;
        $vars['action'] = $this->tb;
        $vars['path'] = '/act/export';



        $vars['columns'] = $this->db->rows("SHOW COLUMNS FROM " .$this->tb);

        $data['styles'] = array('jquery.simple-dtpicker.css');
        $data['scripts'] = array('jquery.simple-dtpicker.js');
        $data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'export', 'menu2'=>$this->left_menu));
        $data['content'] = $this->view->Render('export_order.phtml', $vars);
        return $data;
    }

    public function orders_statusAction()
    {
        $vars['message'] = '';
        if(isset($_POST['update']))
        {
            $vars['message'] = $this->orders->save_orders_status();
        }
        elseif(isset($this->params['add_provider']))
        {
            $vars['message'] = $this->orders->add_orders_status();
        }
        elseif(isset($this->params['delete']) || isset($_POST['delete']))
        {
            $vars['message'] = $this->orders->delete('orders_status');
        }
        $vars['name'] = 'Статусы заказов';
        $vars['action'] = $this->tb;
        $vars['path'] = '/act/orders_status';
        $vars['list'] = $this->db->rows("SELECT * FROM `orders_status` ORDER BY `id` ASC");
        $data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'orders_status', 'menu2'=>$this->left_menu));
        $data['content'] = $this->view->Render('orders_status.phtml', $vars);
        return $data;
    }


    public function exportProductsAction()
    {
        if(!isset($_SESSION['start_provider']))
        {
            $_SESSION['start_provider'] = getdate(mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
            if(strlen($_SESSION['start_provider']['mon'])==1)$_SESSION['start_provider']['mon'] = '0'.$_SESSION['start_provider']['mon'];
            if(strlen($_SESSION['start_provider']['mday'])==1)$_SESSION['start_provider']['mday'] = '0'.$_SESSION['start_provider']['mday'];
            $_SESSION['start_provider'] = $_SESSION['start_provider']['year'].'-'.$_SESSION['start_provider']['mon'].'-'.$_SESSION['start_provider']['mday'].' 00:00:00';
            $_SESSION['end_provider'] = date("Y-m-d").' 23:59:59';
        }

        $vars['providers']=$this->db->rows("SELECT * FROM `providers` WHERE 1");

        if(isset($_POST['provider']))
        {
            if($_POST['provider']==0) $where='';
            else $where=' AND prod.provider_id="'.$_POST['provider'].'"';
        }
        else $where='';
        if(isset($_POST['download'])||isset($_POST['print_prod']))
        {
            $_SESSION['start_provider'] = $_POST['start'];
            $_SESSION['end_provider'] = $_POST['end'];
            //, op.price, SUM(op.sum) as summa  - добавить в запрос для вывода Цены и Суммы
            $dataSet = $this->db->rows("SELECT op.product_id, prod.code, op.name, prov.name as provider, prod.provider_code, SUM(op.amount) as amount, op.price, SUM(op.sum) as summa, op.photo, op.color, op.size
				FROM `orders_product` op

				LEFT JOIN `orders` o ON op.orders_id=o.id

				LEFT JOIN `product` prod ON prod.id=op.product_id

				LEFT JOIN `providers` prov ON prov.id=prod.provider_id

				WHERE (o.date_add>='{$_SESSION['start_provider']}' AND o.date_add<='{$_SESSION['end_provider']}') {$where}
				GROUP by op.product_id ");



            if(isset($_POST['download']))
            {


                $change = array(
                    'product_id'=>'Товар',
                    'code1с'=>'Код 1С',
                    'name'=>'Наименование',
                    'provider_id'=>'Поставщик',
                    'provider_code'=>'Код поставщика',
                    'color'=>'Цвет',
                    'size'=>'Размер',
                    'amount'=>'Количество',
                    'price'=>'Цена',
                    'summa'=>'Сумма'
                );
                if(!empty($dataSet))
                {
                    $save = 'export'.strtoupper($_POST['file_format']);
                    Export::$save($dataSet, $change);
                }
                else $vars['message']=messageAdmin('Информация отсутствует', 'error');

            }
            else{
                $vars['product'] = $dataSet;
            }
        }


        $vars['name'] = 'Экспорт поставщикам - ' . $this->name;
        $vars['action'] = $this->tb;
        $vars['path'] = '/act/exportProducts';
        $data['styles'] = array('jquery.simple-dtpicker.css');
        $data['scripts'] = array('jquery.simple-dtpicker.js', 'print.js');

        $vars['columns'] = $this->db->rows("SHOW COLUMNS FROM " .$this->tb);
        $data['left_menu'] = $this->model->left_menu_admin(array('action'=>$this->tb, 'name'=>$this->name, 'sub'=>'exportProducts', 'menu2'=>$this->left_menu));
        $data['content'] = $this->view->Render('exportProducts.phtml', $vars);
        return $data;
    }
}
?>