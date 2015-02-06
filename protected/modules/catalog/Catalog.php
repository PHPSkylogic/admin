<?
class Catalog extends Model
{
    static $table='catalog'; //Главная талица
    static $name='Каталог'; // primary key

	public function __construct($registry)
    {
        parent::getInstance($registry);
    }

    //для доступа к классу через статичекий метод
	public static function getObject($registry)
	{
		return new self::$table($registry);
	}

    public function add($open=false)
    {
        $message='';
        if(isset($_POST['active'], $_POST['url'], $_POST['name'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['body'], $_POST['sub'])&&$_POST['name']!="")
        {
			
			
            if($_POST['sub']==0)$_POST['sub']=NULL;
			if(!isset($_POST['position']))$_POST['position']=0;
            $param = array($_POST['active'], $_POST['sub'], $_POST['position']);
            $insert_id = $this->db->insert_id("INSERT INTO `".$this->table."` SET `active`=?, sub=?, position=?", $param);
			
			if($_POST['url']=='')$url = String::translit($_POST['name'].'-'.$insert_id);
            else $url = String::translit($_POST['url']);
			$message = $this->checkUrl($this->table, $url, $insert_id);
			
            $languages = $this->db->rows("SELECT * FROM language");
            foreach($languages as $lang)
            {
                $tb=$lang['language']."_".$this->table;
                $param = array($_POST['name'], $_POST['body'], $insert_id);
                $this->db->query("INSERT INTO `$tb` SET `name`=?, `body`=?, `catalog_id`=?", $param);
            }
			
			
			
			//////Save meta data
			$meta = new Meta($this->sets);
			$meta->save_meta($this->table, $url, $_POST['title'], $_POST['keywords'], $_POST['description']);
			
			//////Save catalog params
			if(isset($_POST['cat_id'])&&count($_POST['cat_id'])!=0)
			{
				if(isset($_POST['cat_id']))
				{
					$count=count($_POST['cat_id']) - 1;
					for($i=0; $i<=$count; $i++)
					{
						$this->db->query("INSERT INTO params_catalog SET params_id=?, catalog_id=?", array($_POST['cat_id'][$i], $insert_id));
					}
				}
			}
			
			////Photo
            if(isset($_POST['current_photo'])&&file_exists($_POST['current_photo']))
            {
				$ext = pathinfo($_POST['current_photo'], PATHINFO_EXTENSION);
                $dir="files/catalog/";
                copy(str_replace('_s', '', $_POST['current_photo']), $dir.$insert_id.".".$ext);
                copy($_POST['current_photo'], $dir.$insert_id."_s.".$ext);
				
				$photo_m=str_replace('_s', '_m', $_POST['current_photo']);
				if(file_exists($photo_m))copy($photo_m, $dir.$insert_id."_m.".$ext);
				
				$this->photo_del("files/tmp/", $_POST['tmp_image']);
				$this->db->query("UPDATE `".$this->table."` SET `photo`=? WHERE `id`=?", array($dir.$insert_id."_s.".$ext, $insert_id));
            }
			
			if(isset($_FILES['icon']['tmp_name'])&&$_FILES['icon']['tmp_name']!='')
			{
				copy($_FILES['icon']['tmp_name'], "files/catalog/icon{$insert_id}.png");
			}
					
			if($open)
			{
				header('Location: /admin/'.$this->table.'/edit/'.$insert_id);
				exit();	
			}
			
			$message = $this->checkUrl($this->table, $url, $insert_id);
			
            $message.= messageAdmin('Данные успешно добавлены');
        }
        else $message.= messageAdmin('При добавление произошли ошибки', 'error');
        return $message;
    }

    public function save()
    {
        $message='';
        if(isset($this->registry['access']))$message = $this->registry['access'];
        else
        {
            if(isset($_POST['save_id'])&&is_array($_POST['save_id']))
            {
                if(isset($_POST['save_id'], $_POST['name'], $_POST['url']))
                {
					$count=count($_POST['save_id']) - 1;
                    for($i=0; $i<=$count; $i++)
                    {
                        if($_POST['url'][$i]=='')$url = String::translit($_POST['name'][$i]);
                        else $url = $_POST['url'][$i];

                        $message = $this->checkUrl($this->table, $url, $_POST['save_id'][$i]);
                        $param = array($_POST['name'][$i], $_POST['save_id'][$i]);
                        $this->db->query("UPDATE `".$this->registry['key_lang_admin']."_".$this->table."` SET `name`=? WHERE catalog_id=?", $param);
                    }
                    $message .= messageAdmin('Данные успешно сохранены');
                }
                else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
            }
            else{
                if(isset($_POST['active'], $_POST['url'], $_POST['id'], $_POST['name'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['body']))
                {
                    if($_POST['url']=='')$url = String::translit($_POST['name'].'-'.$_POST['id']);
                    else $url = String::translit($_POST['url']);
					
					//////Save meta data
					$meta = new Meta($this->sets);
					$meta->save_meta($this->table, $url, $_POST['title'], $_POST['keywords'], $_POST['description']);

					if(!isset($_POST['position']))$_POST['position']=0;
                    if($_POST['sub']==0)$sub = NULL;
                    else $sub = $_POST['sub'];

                    $message = $this->checkUrl($this->table, $url, $_POST['id']);
                    $param = array($_POST['active'], $sub, $_POST['position'], $_POST['id']);
                    $this->db->query("UPDATE `".$this->table."` SET `active`=?, sub=?, position=? WHERE id=?", $param);

                    $param = array($_POST['name'], $_POST['body'],  $_POST['id']);
                    $this->db->query("UPDATE `".$this->registry['key_lang_admin']."_".$this->table."` SET `name`=?, `body`=? WHERE `catalog_id`=?", $param);
					
					//////Save catalog params
					$this->db->query("DELETE FROM `params_catalog` WHERE `catalog_id`=?", array($_POST['id']));
					if(isset($_POST['cat_id'])&&count($_POST['cat_id'])!=0)
					{
                        if(isset($_POST['cat_id']))
						{
							$count=count($_POST['cat_id']) - 1;
                            for($i=0; $i<=$count; $i++)
							{
                                $this->db->query("INSERT INTO params_catalog SET params_id=?, catalog_id=?", array($_POST['cat_id'][$i], $_POST['id']));
                            }
                        }
                    }
					
					if(isset($_FILES['icon']['tmp_name'])&&$_FILES['icon']['tmp_name']!='')
					{
						copy($_FILES['icon']['tmp_name'], "files/catalog/icon{$_POST['id']}.png");
					}
					
                    $message .= messageAdmin('Данные успешно сохранены');
                }
                else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
            }
        }
        return $message;
    }

    public function queryProducts($param=array())
    {
		if(!isset($param['select']))$param['select']="";
		if(!isset($param['join']))$param['join']="";	
		if(!isset($param['where']))$param['where']="";	
		$param['group']="tb.id";	
		if(!isset($param['order']))$param['order']="tb.`sort` ASC, tb.id DESC";
		if(!isset($param['limit']))$param['limit']="";
		if(!isset($param['having']))$param['having']="";
		
		if(!isset($param['admin']))
		{
			$price_type=$_SESSION['price_type_id'];

			$param['join'].=" LEFT JOIN product_status_set tb4
							 ON tb4.product_id=tb.id
								
							 LEFT JOIN ".$this->registry['key_lang']."_product_status tb5
							 ON tb4.status_id=tb5.product_status_id
						 
								LEFT JOIN ".$this->registry['key_lang']."_brend brend
								 ON brend.brend_id=tb.brend_id
								 
								 LEFT JOIN params_product
								 ON params_product.product_id=tb.id
								 
								 LEFT JOIN params
								 ON params.id=params_product.params_id AND params.sub='77'
								 
								 LEFT JOIN ".$this->registry['key_lang']."_params params_lang
								 ON params_lang.params_id=params.id
								 
								 LEFT JOIN `price` tb_price
						 		 ON `tb_price`.product_id=tb.id AND tb_price.price_type_id='".$price_type."'
								 
								 LEFT JOIN `price` tb_price2
						 		 ON `tb_price2`.product_id=tb.id AND tb_price2.price_type_id='2'
						 ";
			$param['select'].=",GROUP_CONCAT(DISTINCT params_lang.name ORDER BY params_lang.name ASC SEPARATOR ',') AS sizes, 
								brend.name AS brend, 
								tb_price.price-tb_price.price*(tb_price.discount/100) AS price_sort,
								tb_price2.price AS price2,
							    tb_lang.body_m,tb_lang.body2,tb_lang.body4,tb4.status_id,
								tb5.name AS status";			 	
		}
		else{
			$param['join'].="LEFT JOIN `price` tb_price
						 	 ON `tb_price`.product_id=tb.id AND `tb_price`.price_type_id='1'";
		}
		
        $q=array("select"=>"tb.id,
							tb.url,
							tb.brend_id,
							tb.photo,
							tb.colors,
							tb_lang.name,
							tb3.catalog_id, 
							tb_price.id as price_id, 
							tb_price.price, 
							tb_price.discount, 
							tb_price.stock, 
							tb_price.unit
							".$param['select'],
							
				"join"=>"LEFT JOIN product_catalog tb3
						 ON tb3.product_id=tb.id
						 
						 

						 ".$param['join'],
				"where"=>"tb.id!='0' ".$param['where'],
				"having"=>$param['having'],
				"group"=>$param['group'],
				"order"=>$param['order'],
				"limit"=>$param['limit'],
				"type"=>"rows");//var_info($q);
        return $q;
    }

    public function subcats($catrow)
    {

        $subcats = $this->db->rows("SELECT tb.url, tb2.*, count(distinct prod.id) as count
									FROM `catalog` tb
			
									LEFT JOIN `ru_catalog` tb2
									ON tb.id = tb2.catalog_id
			
									LEFT JOIN product_catalog prodcat
									ON prodcat.catalog_id = tb.id
			
									LEFT JOIN product prod
									ON prod.id = prodcat.product_id
			
									WHERE tb.sub=? GROUP BY tb.id", array($catrow['id']));
		
		if(count($subcats)==0&&isset($catrow['sub']))
		{
			$subcats = $this->db->rows("SELECT tb.url, tb2.*, count(distinct prod.id) as count
										FROM `catalog` tb
				
										LEFT JOIN `ru_catalog` tb2
										ON tb.id = tb2.catalog_id
				
										LEFT JOIN product_catalog prodcat
										ON prodcat.catalog_id = tb.id
				
										LEFT JOIN product prod
										ON prod.id = prodcat.product_id
				
										WHERE tb.sub=? GROUP BY tb.id", array($catrow['sub']));	
		}

        return $subcats;
    }
	
	
	public function getParams($products, $cat_id)
    {
		//var_info($products);
		$product_q='';
		
        foreach($products as $row)
        {
			$product_q2='';
			$row2 = explode(',',$row['params']);
			foreach($row2 as $row3)
			{
				if($product_q2!='')$product_q2.=" OR ";
				$product_q2.="pp.params_id='".$row3."'";
			}
			
			if($product_q2!='')
			{
				if($product_q!='')$product_q.=' or ';
				$product_q.="(($product_q2) AND pp.product_id='{$row['id']}')";
				
				
			}
        }
		if($product_q!='')
		{
			$product_q=" AND (".$product_q.")";
		}

		
		//////////
		$params_q='';
		$i=0;
		if(isset($_SESSION['params'][0]) && ($_SESSION['params'][0]!=''))
        {
			foreach($_SESSION['params'] as $row)
			{
				if($params_q!='')$params_q.=" OR ";
				$params_q="pp.params_id='{$row}'";
				if($i==0)$first=$row;	
				$i++;
			}
		}
		if($params_q!=''&&$product_q!='')
		{
			$row = $this->db->row("SELECT sub FROM params WHERE id='$first'");
			$product_q=substr($product_q, 0, strlen($product_q)-1)." OR tb1.sub='{$row['sub']}')";
			$params_q=" AND (".$params_q.")";
		}
		
		$sub_cat='';
		$res = $this->db->rows("SELECT id FROM catalog WHERE sub='$cat_id'");
		foreach($res as $row2)
		{
			$sub_cat.=" OR pc.catalog_id='".$row2['id']."'";	
		}
		if($sub_cat!='')$sub_cat="(pc.catalog_id='".$cat_id."' $sub_cat)";
		else $sub_cat="pc.catalog_id='".$cat_id."'";
		
		$query = "  SELECT tb1.id, tb1.url, tb1.sub, tb2.name, COUNT(DISTINCT pc.product_id) as count, pp.product_id, pp.params_id

					FROM `params` tb1

					LEFT JOIN ".$this->registry['key_lang']."_params tb2
					ON tb1.id=tb2.params_id

					LEFT JOIN params_product pp
					ON pp.params_id = tb1.id
					
					LEFT JOIN product_catalog pc
					ON pp.product_id = pc.product_id

					WHERE tb1.active='1' AND (($sub_cat $product_q)  OR tb1.sub IS NULL)
					 
					GROUP BY tb1.id
					ORDER BY tb1.`sort` ASC, tb2.`name` ASC, tb1.id DESC";

         $res = $this->db->rows($query);
		//echo $query;
		//echo $product_q;
		 return $res;
	}

    public function sub_id($table, $id, $table_dop="id")
    {
        $sel = '';
        $queri = "SELECT * FROM `$table` WHERE `sub` = ? and `active`='1' ORDER BY `$table`.`sub` asc, `$table`.`sort` asc";
        $resul = $this->db->rows($queri, array($id));

        $sel = '';

        foreach ($resul as $row)
		{
            $sub = $row['id'];
            $sel = $sel."$table_dop ='$sub' OR ".$this->sub_id($table, $sub, $table_dop);
        }
        return $sel;
    }
}
?>