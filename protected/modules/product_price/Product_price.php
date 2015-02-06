<?
class Product_price extends Model
{
    static $table='price_changelog'; //Главная талица
	static $model = 'Product_price';
    static $name='Ценообразование'; // primary key

    public function __construct($registry)
    {
        parent::getInstance($registry);
    }

    //для доступа к классу через статичекий метод
    public static function getObject($registry)
    {
        return new self::$model($registry);
    }

    public function add($open=false)
    {
        $message='';
        if(isset($_POST['cat_id'], $_POST['action'], $_POST['value']) && $_POST['value']>0)
        {
			if($_POST['cat_id']!=0) {
				$products = $this->db->rows("SELECT tb.id FROM product tb
					 LEFT JOIN product_catalog cat ON tb.id = cat.product_id
					 WHERE cat.catalog_id = ?", array($_POST['cat_id']));
			}
			else $products = $this->db->rows("SELECT tb.id FROM product tb");
			
			$count = count($products);
			
			if ($_POST['action']=='+percent') {
				$action = '*'.(100+$_POST['value'])/100;
				$actionSave = 'увеличить на %';
			} elseif ($_POST['action']=='-percent') {
				$action = '*'.(100-$_POST['value'])/100;
				$actionSave = 'уменьшнить на %';
			} else {
				$action = $_POST['action'].$_POST['value'];
				$actionSave = $_POST['action'];
			}
			
			$where="";
			$price_type="";
			if($_POST['price_type']!=0)
			{
				$where="AND price_type_id='{$_POST['price_type']}'";
				$price_type = $this->db->row("SELECT name FROM price_type WHERE id='{$_POST['price_type']}'");
				$price_type = $price_type['name'];
			}
			
			foreach($products as $row)
			{
				//$this->db->query("UPDATE product SET price=price".$action." WHERE id=?", array($row['id']));
				$this->db->query("UPDATE price SET price=price".$action." WHERE product_id=? $where", array($row['id']));
			}

			$this->db->query("INSERT INTO `".$this->table."`
											   SET
												`catalog_id`=?,
												`price_type`=?,
												`action`=?,
												`value`=?,
												`count`=?,
												`date`=?",
                array(
                    $_POST['cat_id'],
					$price_type,
					$actionSave,
					$_POST['value'],
					$count,
                    date("Y-m-d H:i:s")));

            $message.= messageAdmin('Данные успешно добавлены');
        }
        else $message.= messageAdmin('При добавление произошли ошибки', 'error');
        return $message;
    }
	
	public function listView()
	{
	
		$vars['list'] = Product_price::getObject($this->sets)->find(array('order'=>'id DESC', 'type'=>'rows'));
		$vars['catalog'] = array();
		$catalogs = Catalog::getObject($this->sets)->find(array('type'=>'rows',
																	   'group'=>'tb.id',
																	   'order'=>'tb.sort'));
	   foreach($catalogs as $row) {
			$vars['catalog'][$row['id']] = $row['name'];
	   }
		return $vars;
	}
	
}
?>