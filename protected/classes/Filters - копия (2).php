<?php
class Filters{
	private $registry;
	protected $db;
	public function __construct($sets)
	{
		$this->registry = $sets['registry'];
		$this->db = $sets['db'];//Connect to database
		$this->view = new View($sets['registry']);
		$this->settings = $sets['settings'];
		$this->params = $sets['params'];
		$this->translation = $sets['translation'];
		$this->registry = $sets['registry'];
	}
	
	
	////Разбор url фильтров и формирование запроса в БД
	public function get_condition($cat_id, $params_url)
	{
		$join='';
		$where='';
		$group=array();
		$_SESSION['params']=array();
		if(!isset($_POST['clear_id']))$_POST['clear_id']='';
		
		if($params_url!='')
		{
			$uri=explode(';', $params_url);
			$i=0;
			foreach($uri as $row)
			{
				$uri2 = explode('=', $row);
				if($uri2[0]!=''&&$uri2[1]!='')
				{
					$group[$uri2[0]]=array();
					$join .= " LEFT JOIN params_product tj".$i." ON tb.id = tj".$i.".product_id ";
					$uri3 = explode(',', $uri2[1]);
					$where2='';
					foreach($uri3 as $row2)
					{
						if($_POST['clear_id']!=$row2)
						{
							$where2.= " OR tj".$i.".params_id = '".$row2."' ";
							$_SESSION['params'][]=$row2;
							$group[$uri2[0]][]=$row2;
						}
					}
					$where.= " AND (".substr($where2, 4, strlen($where2)).") ";
				}
				$i++;
			}
		}
		//$params = $this->getParams($group, $cat_id, $params_url);
		//var_info($group);
		return array('join'=>$join, 'where'=>$where, 'group'=>$group);
	}
	
	
	public function getParams($group, $cat_id, $params_url, $current_product)
    {	
		//$this->db->query("SET SESSION group_concat_max_len = 10000000");
		$sub_cat='';
		$res = $this->db->rows("SELECT id FROM catalog WHERE sub='$cat_id'");
		foreach($res as $row2)
		{
			$sub_cat.=" OR pc.catalog_id='".$row2['id']."'";	
		}
		
		if($sub_cat!='')$sub_cat="(pc.catalog_id='".$cat_id."' $sub_cat)";
		else $sub_cat="pc.catalog_id='".$cat_id."'";
		
		$filters = $this->db->rows("SELECT params_id FROM params_catalog pc WHERE $sub_cat GROUP BY params_id ORDER BY params_id ASC");
		//var_info($group);
		$where=array();
		$product2='';
		$cnt=count($group);
		$params=array();
		$product_q='';
		foreach($filters as $row)
		{
			$param='';
			$join='';
			
			$product_q='';
			foreach($group as $key2=>$row2)
			{
				if($row['params_id']!=$key2)
				{
					$where[$row['params_id']]='';
					foreach($row2 as $row3)
					{
						$where[$row['params_id']].=" OR tj".$key2.".params_id='$row3'";
					}
					if($where[$row['params_id']]!='')
					{
						$param.=' AND ('.substr($where[$row['params_id']], 4).')';
					}
					$join .= " LEFT JOIN params_product tj".$key2." ON p.id = tj".$key2.".product_id 
					
					";
				}
			}
			if($param!='')
			{
				if($cnt==1&&isset($product['id'],$product_q2))
				{
					$product_q=$product_q2;
				}
				else{
					$product = $this->db->row("SELECT GROUP_CONCAT(DISTINCT p.id ORDER BY p.id ASC SEPARATOR ',') as id
												   
											   FROM product p 
											   
											   $join
											   
											   LEFT JOIN product_catalog pc
											   ON pc.product_id=p.id
											   
											   LEFT JOIN price tb_price
											   ON tb_price.product_id=p.id
							
											   WHERE p.active='1' AND (tb_price.price>='{$_SESSION['price_from'][0]}' AND tb_price.price<='{$_SESSION['price_to'][0]}') AND $sub_cat ".$param."
											   
											   GROUP BY p.id");
	

					if(isset($product['id']))
					{
						$product_q='';
						$products = array_unique(explode(',', $product['id']));
						foreach($products as $row5)
						{
							if($product_q!='')$product_q.=' OR ';
							$product_q.="pp.product_id='{$row5}'";
						}
						if($product_q!='')
						{
							$product_q=" AND (".$product_q.")";
						}
						$product_q2=$product_q;
					}
				}
			}
			
			if($product_q=='')$select_count='COUNT(DISTINCT pc.product_id) as count';
			else $select_count='COUNT(DISTINCT pp.product_id) as count';
			
			$query = "  SELECT tb1.id, tb1.url, tb1.sub, tb2.name, $select_count, pp.product_id, pp.params_id, GROUP_CONCAT(DISTINCT pc.product_id ORDER BY pc.product_id ASC SEPARATOR ',') as product_id
			
						FROM `params` tb1
	
						LEFT JOIN ".$this->registry['key_lang']."_params tb2
						ON tb1.id=tb2.params_id
	
						LEFT JOIN params_product pp
						ON pp.params_id = tb1.id $product_q
						
						LEFT JOIN product_catalog pc
						ON pp.product_id = pc.product_id AND $sub_cat
	
						WHERE tb1.active='1' AND tb1.sub='{$row['params_id']}' AND pp.params_id IS NOT NULL
						 
						GROUP BY tb1.id
						ORDER BY tb1.`sort` ASC, tb2.`name` ASC, tb1.id DESC";
						//if($row['params_id']==26)echo $query;
	
			$params2 = $this->db->rows($query);
			$params=array_merge($params2, $params);
		}
		
		if($params_url!='')
		{
			$current_product=explode(',', $current_product);
			$i=0;
			foreach($params as $row)
			{
				
				
				$count=0;
				if($row['product_id']!='')
				{
					$product_id=explode(',', $row['product_id']);
					foreach($product_id as $row2)
					{
						if(!in_array($row2, $current_product))
						{
							/*if($row['id']==126)
							{
								echo $row['product_id'].'asd';
							}*/
							$count++;	
						}
					}
					if($count!=0)$params[$i]['count']='+'.$count;
				}
				$i++;
			}
		}
		
		$query = "  SELECT tb1.id, tb1.url, tb1.sub, tb2.name
			
					FROM `params` tb1

					LEFT JOIN ".$this->registry['key_lang']."_params tb2
					ON tb1.id=tb2.params_id

					WHERE tb1.active='1' AND tb1.sub IS NULL
					 
					GROUP BY tb1.id
					ORDER BY tb1.`sort` ASC, tb2.`name` ASC, tb1.id DESC";
	
		$params2 = $this->db->rows($query);
		$params=array_merge($params2, $params);
		//var_info($params);

		if(count($params)!=0)
		{
			//var_info($params);
			return $this->view->Render('cat_filters_ajax.phtml', array('params_url'=>$params_url,
																		'params'=>$params, 
																		'translate'=>$this->translation, 
																		'params_limit'=>$this->settings['params_limit']));
		}
		 
	}
}
?>