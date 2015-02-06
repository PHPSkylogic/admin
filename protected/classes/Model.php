<?php
class Model extends DataBase
{
	static $table='news'; //Главная талица
    static $name="_news"; //доп таблица
	
	function test()
	{
		echo'asdasd';	
	}
	
	public function __construct($registry)
    {
        parent::getInstance($registry);
    }
	
	
	public function insert($sql=array(), $debug=false)
	{
		$where = $this->checkWhere($sql['query']);
		$insert_id = $db->insert_id("INSERT INTO `".$this->table."` SET ".$where['query'], $where['params']);
		
		if(!$insert_id)return false;
		if(isset($sql['query_lang']))
		{
			$where = $this->checkWhere($sql['query_lang']);
			if($where['query']!='')$where['query']=', '.$where['query'];
			foreach($this->language as $lang)
			{
				$tb=$lang['language']."_".$this->table;
				if(!$db->query("INSERT INTO `$tb` SET `".$this->table."_id`='{$insert_id}'".$where['query'], $where['params']))return false;
			}
		}
		
		return $insert_id;
	}
	
	public function delete($table='')
	{
		$meta = new Meta($this->sets);
		if($table=='')$table=$this->table;
		$message='';
		if(isset($this->registry['access']))$message = $this->registry['access'];
		else
		{
			if(isset($_POST['id'])&&is_array($_POST['id']))
			{
				$count=count($_POST['id']) - 1;
				for($i=0; $i<=$count; $i++)
				{
					////Delete meta data
					$meta->delete_meta($this->table, $_POST['id'][$i]);
					$this->db->query("DELETE FROM `".$table."` WHERE `id`=?", array($_POST['id'][$i]));
					
					if($table=='product')
					{
						$dir=Dir::createDir($_POST['id'][$i]);	
						if($dir[0]!='')Dir::removeDir($dir[0]);
					}
				}
				$message = messageAdmin('Запись успешно удалена');
			}
			elseif(isset($this->params['delete'])&& $this->params['delete']!='')
			{
				$id = $this->params['delete'];
				
				if($table=='product')
				{
					$dir=Dir::createDir($id);	
					if($dir[0]!='')Dir::removeDir($dir[0]);
				}
					
				////Delete meta data
				$meta->delete_meta($this->table, $id);
				if($this->db->query("DELETE FROM `".$table."` WHERE `id`=?", array($id)))$message = messageAdmin('Запись успешно удалена');
			}
		}
		return $message;
	}
	
	public function get_columns($row, $table, $fk='')
	{
		$query="";
		$fields = $this->db->rows("SHOW COLUMNS FROM $table");
		foreach($fields as $row2)
		{
			if($row2['Field']!='id'&&$row2['Field']!=$fk)
			{
				if($row2['Field']=='url')$row[$row2['Field']].="-".time();
				elseif($row2['Field']=='name')$row[$row2['Field']].="-[copy]";
				$query.="{$row2['Field']}='".$row[$row2['Field']]."', ";
			}
		}
		return $query = substr($query, 0, strlen($query)-2);	
	}
	
	public function duplicate($row, $table)
	{
		$message='';
		if(isset($this->registry['access']))$message = $this->registry['access'];
		else
		{
			$message='';
			$fk = $table."_id";
			$query = $this->get_columns($row, $table);
			if($query!='')
			{
				$insert_id = $this->db->insert_id("INSERT INTO `$table` SET ".$query);
				if($this->db->row("SHOW TABLES LIKE '".$this->registry['key_lang_admin']."_".$table."'"))
				{
					$res = $this->db->rows("SELECT * FROM language");
					foreach($res as $lang)
					{
						$tb=$lang['language']."_".$table;
						
						$query = $this->get_columns($row, $tb, $fk);
						if($query!='')$this->db->query("INSERT INTO `$tb` SET $fk = '$insert_id', ".$query);
					}	
				}
				header("Location: /admin/$table/edit/".$insert_id);
			}
		}
		return $message;
	}
	
	public function find($param)
    {
		if(isset($param['paging'])&&is_array($param))
		{
			if(is_numeric($param['paging']))$size_page = $param['paging'];
			elseif(isset($this->settings['paging_admin_'.$this->table], $this->registry['admin']))$size_page = $this->settings['paging_admin_'.$this->table];
			elseif(isset($this->settings['paging_'.$this->table]))$size_page = $this->settings['paging_'.$this->table];
			else $size_page = default_paging;
			
			$start_page = 0;
			$cur_page = 0;
			$paging = '';
			$param2 = $param;
			//$param['select'] = 'tb.id';
			$param['type'] = 'count';
			$count = $this->select($param);//кол страниц
			
			//var_info($this->params).'s';
			if(isset($this->params['page']))
			{
				$cur_page = $this->params['page'];
				if($cur_page<2)
				{
					header('Location: '.String::getUrl2('page'));
					exit();
				}

				$start_page = ($cur_page-1) * $size_page;//номер начального элемента
			}

			if($count > $size_page)
			{
				$class = new Paging($this->registry, $this->params);
				$paging = $class->MakePaging($cur_page, $count, $size_page);//вызов шаблона для постраничной навигации
			}
			
			$param2['limit'] = $start_page.', '.$size_page;
			return array('list'=>$this->select($param2), 'paging'=>$paging, 'count'=>$count);//echo $data['sql'];
		}
		elseif(is_numeric($param))
		{
			return $this->select(array('where'=>'__tb.id:='.$param.'__'));
		}
		elseif(is_string($param))
		{
			return $this->select(array('where'=>'__tb.url:='.$param.'__'));
		}
		else{
			return $this->select($param);
		}
	}
	
	//-----------------функция для вывода каталога SELECT------------------------------------------------------
	function select_tree( $tab='menu', $cur_id = '')
	{
		$text='';
		$query="SELECT * FROM `$tab` JOIN `{$this->registry['key_lang']}_$tab`  on `{$this->registry['key_lang']}_$tab`.`menu_id`=`$tab`.`id` ORDER BY `sub` asc, `sort` asc";
	
		$arrayCategories = array();
		foreach( $this->db->rows($query) as $row){ 	
			$arrayCategories[$row['id']] = array("sub" => $row['sub'], "name" => $row['name'] );	 
		} 
	 
		$text=Arr::createTree_cat($arrayCategories, 0, $cur_id);
		return 	$text;	
	}
	
	public function getPage($id, $index='*')/////Get text page from tables menu or pages
	{
		//echo $id;
		if(is_numeric($id))$WHERE='tb1.id=?';
		else $WHERE='tb1.url=?';
		
		$page = $this->db->row("SELECT ".$index." FROM `menu` tb1
								 LEFT JOIN ".$this->registry['key_lang']."_menu tb2
								 ON tb1.id=tb2.menu_id
								 WHERE ".$WHERE." AND tb1.active=?",
		array($id, 1));
		
		if(!$page)
		{
			$page = $this->db->row("SELECT ".$index." FROM `pages` tb1
										  LEFT JOIN ".$this->registry['key_lang']."_pages tb2
										  ON tb1.id=tb2.pages_id
										  WHERE ".$WHERE." AND tb1.active=?",
        	array($id, 1));
			$page['type']='pages';
		}
		else $page['type']='menu';
		return $page;
	}
	
	public function getBlock($id)
	{
		if(is_array($id))
		{
			$where='';
			foreach($id as $row)
			{
				$where.="OR id='$row'";
			}
			if($where!='')$where="AND (".substr($where, 3, strlen($where)).")";
			return $this->db->rows_key("SELECT id, body 
										   FROM infoblocks tb 
										   
										   LEFT JOIN ".$this->registry['key_lang']."_infoblocks tb2 
										   ON tb.id=tb2.infoblocks_id
										   
										   WHERE tb.active='1' $where");
		}
		else{
			if(is_numeric($id))
			{
				$where="AND tb.id='$id'";
			}
			else{
				$where="AND tb.url='$id'";
				
			}	
			return $this->db->row("SELECT * 
								   FROM infoblocks tb 
								   
								   LEFT JOIN ".$this->registry['key_lang']."_infoblocks tb2 
								   ON tb.id=tb2.infoblocks_id
								   
								   WHERE tb.active='1' $where");
		}
	}
	
	public function breadcrumbAdmin()/////Хлебные крошки админки
	{
		if(isset($this->params['action'])&&($this->params['action']=='edit'||$this->params['action']=='add'))
			 return'<a href="/admin/'.strtolower($this->params['controller']).'" class="back-link">« Назад в:&nbsp;'.$this->params['module'].'</a>';
		else return'';
	}

	public function breadcrumbs($links)/////Хлебные крошки на сайте
	{
		$separator='<span>></span>';
		$return='';
		$cnt=count($links);
		if($cnt>0)
		{
			$i=1;
			foreach($links as $row)
			{
				$return.=$row;
				if($cnt!=$i)$return.=' '.$separator.' ';	
				$i++;
			}
			if($return!='')$return='<div id="breadcrumbs"><noindex><a href="'.LINK.'/" rel="nofollow">'.$this->translation['main'].'</a></noindex> '.$separator.' '.$return.'</div>';
			return $return;
		}
	}
	
	public function getBreadCat($catrow, $product_name='')
	{
		$return=array();
		if(is_numeric($catrow))
		{
			$catrow = $this->db->row("SELECT tb.id, tb.sub, tb.url, tb2.name 
									  FROM catalog tb 
									  
									  LEFT JOIN ".$this->registry['key_lang']."_catalog tb2
									  ON tb.id=tb2.catalog_id
											
									  WHERE tb.id=?", array($catrow));
				  
		}
		
		if($product_name!='')$last='<a href="'.LINK.'/catalog/'.$catrow['url'].'" title="'.$catrow['name'].'">'.$catrow['name'].'</a>'.' ';
		if($catrow['sub']==0)
		{
			if($product_name!='')$return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', 
												 '<a href="'.LINK.'/catalog/'.$catrow['url'].'" title="'.$catrow['name'].'">'.$catrow['name'].'</a>', 
												  $product_name);	
			else $return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', $catrow['name']);	
		}
		else{
			$catrow2 = $this->db->row("SELECT * FROM catalog tb 
											LEFT JOIN ".$this->registry['key_lang']."_catalog tb2
											ON tb.id=tb2.catalog_id
											
										  WHERE tb.id=?", array($catrow['sub']));
			
			if($catrow2['sub']==0)
			{
				if($product_name!='')$return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', 
													 '<a href="'.LINK.'/catalog/'.$catrow2['url'].'" title="'.$catrow2['name'].'">'.$catrow2['name'].'</a>', 
													 '<a href="'.LINK.'/catalog/'.$catrow['url'].'" title="'.$catrow['name'].'">'.$catrow['name'].'</a>', 
													 $product_name);	
				else $return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', '<a href="'.LINK.'/catalog/'.$catrow2['url'].'" title="'.$catrow2['name'].'">'.$catrow2['name'].'</a>', 
									 $catrow['name']);	
			}
			else{
				$catrow3 = $this->db->row("SELECT * FROM catalog tb 
												LEFT JOIN ".$this->registry['key_lang']."_catalog tb2
												ON tb.id=tb2.catalog_id
												
											  WHERE tb.id=?",array($catrow2['sub']));
											  
				if($product_name!='')$return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', 
													 '<a href="'.LINK.'/catalog/'.$catrow3['url'].'" title="'.$catrow3['name'].'">'.$catrow3['name'].'</a>',  
													 '<a href="'.LINK.'/catalog/'.$catrow2['url'].'" title="'.$catrow2['name'].'">'.$catrow2['name'].'</a>', 
													 '<a href="'.LINK.'/catalog/'.$catrow['url'].'" title="'.$catrow['name'].'">'.$catrow['name'].'</a>', 
													 $product_name);	
				else $return = array('<a href="'.LINK.'/catalog/all">'.$this->translation['catalog'].'</a>', 
									 '<a href="'.LINK.'/catalog/'.$catrow3['url'].'" title="'.$catrow3['name'].'">'.$catrow3['name'].'</a>',  
									 '<a href="'.LINK.'/catalog/'.$catrow2['url'].'" title="'.$catrow2['name'].'">'.$catrow2['name'].'</a>', 
									 $catrow['name']);										 
			}
		}
					
		return $return;
	}

	public function checkAccess($action, $module)//////Проверка доступа модулей в админке
	{
		/*
		'000'-off;
		'100'-read;
		'200'-read/edit;
		'300'-read/del;
		'400'-read/add;
		'500'-read/edit/del;
		'600'-read/edit/add;
		'700'-read/del/add;
		'800'-read/edit/del/add;
		*/
		$row = $this->db->row("SELECT m.`permission` 
							   FROM `moderators_permission` m
							   
							   LEFT JOIN moderators mm
							   ON m.moderators_type_id=mm.type_moderator
							   
							   LEFT JOIN modules mmm
							   ON mmm.id=m.module_id
							   
							   WHERE mmm.controller=? AND mm.id=?", array($module, $_SESSION['admin']['id']));
							   
		if($row['permission']==000)return false;
		elseif($action=='delete'&&($row['permission']!=500&&$row['permission']!=300&&$row['permission']!=700&&$row['permission']!=800))
		{
			return false;
		}
		elseif(($action=='edit'||$action=='update')&&($row['permission']!=200&&$row['permission']!=500&&$row['permission']!=600&&$row['permission']!=800))
		{
			return false;
		}
		elseif(($action=='add'||$action=='duplicate')&&($row['permission']!=400&&$row['permission']!=600&&$row['permission']!=700&&$row['permission']!=800))
		{
			return false;
		}
		return true;
	}
	
	public function checkUrl($tb, $url, $id)///Проверка уникальности URL
	{
		if($this->db->row("SELECT id from `".$tb."` WHERE url!='' AND url=? AND id!=?", array($url, $id)))return messageAdmin('Данный адрес уже занят', 'error');
		else{
			$this->db->query("UPDATE `".$tb."` set url=? WHERE id=?", array($url, $id));
		}

	}
	
	public function currency()///Валюта
	{
		return $this->db->row("SELECT * FROM `currency` WHERE id='{$_SESSION['currency']}'");	
	}
	
	
	function left_menu_admin($vars)
	{
		$this->view = new View($this->registry);
		
		if($_SESSION['admin']['type']==1)
		{
			$vars['menu'] = $this->db->rows("SELECT * FROM subsystem tb ORDER BY tb.sort ASC, tb.`id` DESC");
		}
		else{
			$vars['menu'] = $this->db->rows("SELECT tb.* FROM subsystem tb
											 
											 LEFT JOIN moderators_permission tb2
											 ON tb.id=tb2.subsystem_id 
											 
											 LEFT JOIN modules m
											 ON m.id=tb2.module_id
											 
											 WHERE tb2.moderators_type_id=? AND tb2.permission!=? AND m.controller=?
											 GROUP BY tb.id
											 ORDER BY tb.sort ASC, tb.`id` DESC", array($_SESSION['admin']['type'], '000', $vars['action']));
		}

		$i=0;
		foreach($vars['menu'] as $row)
		{
			$vars['menu'][$i]['url']='/admin/'.$vars['action'].'/subsystem/'.$row['name'];
			$i++;
		}
		
		if(isset($vars['menu2']))
		{
			$vars['menu']=array_merge($vars['menu'], $vars['menu2']);//var_info($vars['menu']);	
		}
		return $this->view->Render('left_menu.phtml', $vars);	
	}
	
	public function subsystemAction($left_menu=array())
	{
		$class_name = ucfirst($this->params['subsystem']).'Controller';
		$class = new $class_name($this->registry, $this->params);
		
		$vars['message'] = '';
		$vars['subsystem'] = $this->params['subsystem'];
		$vars['action'] = $this->table;
		
		$row = $this->db->row("SELECT id, name FROM modules WHERE controller='".$this->table."'");
		$modules_id = $row['id'];
		
		if(isset($this->params['delsubsystem'])||isset($_POST['delete']))$vars['message'] = $class->delete();
		elseif(isset($_POST['update']))$vars['message'] = $class->save();
		elseif(isset($_POST['update_close']))$vars['message'] = $class->save();
		elseif(isset($this->params['addsubsystem']))$vars['message'] = $class->add($modules_id);

		$vars['where'] = "WHERE `modules_id`='".$modules_id."'";
		$vars['modules_id'] = $modules_id;
		$vars['modules_name'] = $row['name'];
		$vars['path'] = "/subsystem/".$this->params['subsystem'];

		if(count($left_menu)==0)$left_menu = array('action'=>$this->table, 'name'=>$this->name, 'sub'=>$this->params['subsystem']);
		else $left_menu = array('action'=>$this->table, 'name'=>$this->name, 'sub'=>$this->params['subsystem'], 'menu2'=>$left_menu);
		$data['left_menu'] = $this->left_menu_admin($left_menu);
		$data['content'] = $class->subcontent($vars);
		return $data;
	}

	public function photo_del($dir, $id)
	{
		if(file_exists("{$dir}{$id}.jpg"))unlink("{$dir}{$id}.jpg");
		if(file_exists("{$dir}{$id}_s.jpg"))unlink("{$dir}{$id}_s.jpg");	
	}
	
	public function insert_post_form($text)
	{
		$this->db->query("INSERT INTO `feedback` SET `text`=?", array($text));
	}
	
	public function active($id, $tb, $tb2)
	{
		$data=array();
		$data['message'] ='';
		if(!$this->checkAccess('edit', $tb))$data['message'] = messageAdmin('Отказано в доступе', 'error');
		
		if($tb=='info')$tb='infoblocks';
		if($tb2!='undefined')$tb=$tb2;
		if($data['message']=='')
		{
			$id=str_replace("active", "", $id);
			//$tb=$_POST['tb'];
			$row=$this->db->row("SELECT `active` FROM `$tb` WHERE `id`=?", array($id));
			if($row['active']==1)
			{
				$this->db->query("UPDATE `$tb` SET `active`=? WHERE `id`=?", array(0, $id));
				$data['active']='<div class="selected-status status-d"><a> Выкл. </a></div>';
			}
			else{
				$this->db->query("UPDATE `$tb` SET `active`=? WHERE `id`=?", array(1, $id));
				$data['active']='<div class="selected-status status-a"><a> Вкл. </a></div>';
			}
			$data['message']=messageAdmin('Данные успешно сохранены');
		}
		return $data;
	}
	
	public function sortTable($arr, $tb, $tb2)
	{
		if($tb=='module')$tb='modules';
		elseif($tb=='infoblocks')$tb='info';
		$data=array();
		$data['message'] ='';			
		if(!$this->checkAccess('edit', $tb))$data['message'] = messageAdmin('Отказано в доступе', 'error');
		if($tb2!='undefined')$tb=$tb2;
		if($data['message']=='')
		{
			$arr=str_replace("sort", "", $arr);
			preg_match_all("/=(\d+)/",$arr,$a);//echo var_dump($a);
			foreach($a[1] as $pos=>$id)
			{
				$pos2=$pos+1;
				//echo"update {$_POST['tb']} set sort='$pos2' WHERE id='".$id."'";
				$this->db->query("update `$tb` set `sort`=? WHERE `id`=?", array($pos2, $id));
			}
			$data['message']=messageAdmin('Данные успешно сохранены');
		}
		return $data;
	}
	
	function check_for_delete($subsystem_id, $tb, $group_id)
	{
		if($this->db->query("DELETE tb.* FROM `".$tb."` tb
									 
							 LEFT JOIN `moderators_permission` mp
							 ON mp.module_id=tb.modules_id
							 
							 WHERE mp.moderators_type_id=? AND `id`=? AND (permission='300' OR permission='500' OR permission='700' OR permission='800') AND mp.subsystem_id='0'", 
							 array($group_id, $subsystem_id)))return messageAdmin('Запись успешно удалена');
		else return messageAdmin('Ошибка в правах доступа!', 'error');	
	}
	
	function check_for_update($subsystem_id, $tb, $group_id)
	{
		$row = $this->db->row("SELECT tb.* FROM `".$tb."` tb
									 
								 LEFT JOIN `moderators_permission` mp
								 ON mp.module_id=tb.modules_id
								 
								 WHERE (mp.moderators_type_id=? AND `id`=? AND (permission='200' OR permission='500' OR permission='600' OR permission='800') AND mp.subsystem_id='0')
								 		OR (tb.modules_id='' AND `id`=?)", 
								 array($group_id, $subsystem_id, $subsystem_id));
		//var_info($row);	
		return $row;					 
	}
	
	function loadExtraPhoto($tempFile, $name, $tb, $fk, $id, $path, $width, $height)
	{
		$fk2=$fk.'_id';
		if(!is_dir($path))mkdir($path, 0755, true);
		$insert_id = $this->db->insert_id("INSERT INTO $tb SET {$fk2}=?, active=?", array($id, 1));
		foreach($this->language as $lang)
		{
			$tb_l=$lang['language'].'_'.$tb;
			$param = array($name, $insert_id);
			$this->db->query("INSERT INTO `$tb_l` SET `name`=?, `{$tb}_id`=?", $param);
		}
		
		if(!is_dir($path))mkdir($path, 0755, true);
		Images::resizeImage($tempFile, $path.$insert_id.".jpg", $path.$insert_id."_s.jpg", $width, $height);
		Images::set_watermark($this->settings['watermark'], $path.$insert_id."_s.jpg", $fk);
		
		$this->db->query("UPDATE {$tb} SET photo=? WHERE id=?", array($path.$insert_id."_s.jpg", $insert_id));
	}
	
	
	function add_fav($id, $type=0)
	{
		$where="";
		if(isset($_SESSION['user_id']))$where=", user_id='{$_SESSION['user_id']}'";
		
		if(!$this->db->row("SELECT * FROM favorites WHERE type='$type' AND product_id='$id' AND (session_id='{$_COOKIE['session_id']}' ".str_replace(', ',' OR ',$where).")"))
		{
			if($where=='')$where=", user_id=NULL";
			$this->db->query("INSERT INTO favorites SET type='$type', product_id='$id', session_id='{$_COOKIE['session_id']}' $where");
		}
	}
	
	public function rating_bar($tb, $id, $units='', $static='')
	{ 
		$rating_unitwidth     = 20; // ширина (в пикселях) каждого оценивающего модуля (звезда, и т.п.)
		//set some variables
		$ip = $_SERVER['REMOTE_ADDR'];
		if (!$units) {$units = 10;}
		if (!$static) {$static = FALSE;}
		
		// получаем значения votes, values, ips для текущего рейтинг бара
		$numbers=$this->db->row("SELECT total_votes, total_value, used_ips FROM rating WHERE item_id='$id' AND `table`='$tb'");			
		if (!$numbers)
		{
			$this->db->query("INSERT INTO rating (`table`,`item_id`,`total_votes`, `total_value`, `used_ips`) VALUES ('$tb', '$id', '0', '0', '')");
		}
		
		if ($numbers['total_votes'] < 1)
		{
			$count = 0;
		}
		else{
			$count=$numbers['total_votes']; //how many votes total
		}
		$current_rating=$numbers['total_value']; //total number of rating added together and stored
		
		// определяем, голосовал ли пользователь
		$voted=$this->db->row("SELECT used_ips FROM rating WHERE used_ips LIKE '%".$ip."%' AND item_id='".$id."' AND `table`='$tb'"); 
		
		// now draw the rating bar
		$rating_width = @number_format($current_rating/$count,2)*$rating_unitwidth;
		$rating1 = @number_format($current_rating/$count,1);
		$rating2 = @number_format($current_rating/$count,2);
		
		if ($static == 'static')
		{
			$static_rater = array();
			$static_rater[] .= "\n".'<div class="ratingblock"><div id="unit_long'.$id.'">';
			$static_rater[] .= '<ul id="unit_ul'.$id.'" class="unit-rating" style="width:'.$rating_unitwidth*$units.'px;">';
			$static_rater[] .= '<li class="current-rating" style="width:'.$rating_width.'px;">Currently '.$rating2.'/'.$units.'</li>';
			$static_rater[] .= '</ul>';
			$static_rater[] .= '<p class="static">Оценка: <strong> <font color=green size=3>'.$rating1.'</font></strong> из <font size=3>'.$units.'</font> (голосов: '.$count.') <em>This is \'static\'.</em></p>';
			$static_rater[] .= '</div></div>'."\n\n";
			return join("\n", $static_rater);
		}
		else{
			$rater ='';
			$rater.='<div class="ratingblock"><div id="unit_long'.$id.'">';
			$rater.='<ul id="unit_ul'.$id.'" class="unit-rating" style="width:'.$rating_unitwidth*$units.'px;">';
			$rater.='<li class="current-rating" style="width:'.$rating_width.'px;">Currently '.$rating2.'/'.$units.'</li>';
			
			for ($ncount = 1; $ncount <= $units; $ncount++)
			{ 
			   if(!$voted)
			   { 
				  $rater.='<li><noindex><a href="/ajax/rating?j='.$ncount.'&amp;q='.$id.'&amp;t='.$ip.'&amp;c='.$units.'&amp;tb='.$tb.'" title="'.$ncount.' из '.$units.'" class="r'.$ncount.'-unit rater" rel="nofollow">'.$ncount.'</a></noindex></li>';
			   }
			}
			$ncount=0; // resets the count
			
			$rater.='  </ul><p';
			if($voted)
			{
				$rater.=' class="voted"';
			}
			$rater.='  </p></div></div>';
			return $rater;
		}
	}
	
	function rating()
	{
		$rating_unitwidth = 20;
		//getting the values
		$vote_sent = preg_replace("/[^0-9]/","",$_REQUEST['j']);
		$id_sent = preg_replace("/[^0-9a-zA-Z]/","",$_REQUEST['q']);
		$ip_num = preg_replace("/[^0-9\.]/","",$_REQUEST['t']);
		$units = preg_replace("/[^0-9]/","",$_REQUEST['c']);
		$tb = $_REQUEST['tb'];
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if ($vote_sent > $units) die("Sorry, vote appears to be invalid."); // kill the script because normal users will never see this.
		
		//connecting to the database to get some information
		$numbers = $this->db->row("SELECT total_votes, total_value, used_ips FROM rating WHERE item_id='$id_sent' AND `table`='$tb'");
		$checkIP = unserialize($numbers['used_ips']);
		$count = $numbers['total_votes']; //how many votes total
		$current_rating = $numbers['total_value']; //total number of rating added together and stored
		$sum = $vote_sent+$current_rating; // add together the current vote value and the total vote value
		
		// checking to see if the first vote has been tallied
		// or increment the current number of votes
		($sum==0 ? $added=0 : $added=$count+1);
		
		// if it is an array i.e. already has entries the push in another value
		((is_array($checkIP)) ? array_push($checkIP, $ip_num) : $checkIP=array($ip_num));
		$insertip=serialize($checkIP);
		
		//IP check when voting
		$voted=$this->db->row("SELECT used_ips FROM rating WHERE used_ips LIKE '%".$ip."%' AND item_id='".$id_sent."' AND `table`='$tb'");
		if(!$voted)//if the user hasn't yet voted, then vote normally...
		{     
			if(($vote_sent >= 1 && $vote_sent <= $units) && ($ip == $ip_num))// keep votes within range, make sure IP matches - no monkey business!
			{ 
				$this->db->query("UPDATE rating SET total_votes='".$added."', total_value='".$sum."', used_ips='".$insertip."' WHERE item_id='$id_sent' AND `table`='$tb'");		
			} 
		}
		//end for the "if(!$voted)"
		// these are new queries to get the new values!
		$numbers = $this->db->row("SELECT total_votes, total_value, used_ips FROM rating WHERE item_id='$id_sent' AND `table`='$tb'");
		$count = $numbers['total_votes'];//how many votes total
		$current_rating = $numbers['total_value'];//total number of rating added together and stored
		
		// $new_back is what gets 'drawn' on your page after a successful 'AJAX/Javascript' vote
		
		$new_back = array();
		
		$new_back[] .= '<ul class="unit-rating" style="width:'.$units*$rating_unitwidth.'px;">';
		$new_back[] .= '<li class="current-rating" style="width:'.@number_format($current_rating/$count,2)*$rating_unitwidth.'px;">Current rating.</li>';
		$new_back[] .= '<li class="r1-unit">1</li>';
		$new_back[] .= '<li class="r2-unit">2</li>';
		$new_back[] .= '<li class="r3-unit">3</li>';
		$new_back[] .= '<li class="r4-unit">4</li>';
		$new_back[] .= '<li class="r5-unit">5</li>';
		$new_back[] .= '<li class="r6-unit">6</li>';
		$new_back[] .= '<li class="r7-unit">7</li>';
		$new_back[] .= '<li class="r8-unit">8</li>';
		$new_back[] .= '<li class="r9-unit">9</li>';
		$new_back[] .= '<li class="r10-unit">10</li>';
		$new_back[] .= '</ul>';
		$new_back[] .= '<span class="thanks">Ваш голос принят!</span></p>';
		
		$allnewback = join("\n", $new_back);
		//name of the div id to be updated | the html that needs to be changed
		$output = "unit_long$id_sent|$allnewback";
		return $output;	
	}
	
	function recalc_price_range()
	{
		if(!isset($_SESSION['price_from']))
		{
			$_SESSION['price_from'][0]=$this->db->cell("SELECT MIN(price) AS price FROM `price`", array(1));
			$_SESSION['price_to'][0]=$this->db->cell("SELECT MAX(price) AS price FROM `price`", array(1));
		}
		
		$price_from = Numeric::viewPrice($_SESSION['price_from'][0]);
		$_SESSION['price_from']=array();
		$_SESSION['price_from'][0] = $price_from['base_price'];
		$_SESSION['price_from'][1] = floor($price_from['cur_price']);

		$price_to = Numeric::viewPrice($_SESSION['price_to'][0]);
		$_SESSION['price_to']=array();
		$_SESSION['price_to'][0] = $price_to['base_price'];
		$_SESSION['price_to'][1] = round($price_to['cur_price']);
		//echo $_SESSION['price_to'];	
	}

    function setDateStat()
    {
        $cur_start_date = getdate(mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
        $cur_end_date = date("Y-m-d H:i:s");
        if(strlen($cur_start_date['mon'])==1)$cur_start_date['mon'] = '0'.$cur_start_date['mon'];
        if(strlen($cur_start_date['mday'])==1)$cur_start_date['mday'] = '0'.$cur_start_date['mday'];
        $cur_start_date = $cur_start_date['year'].'-'.$cur_start_date['mon'].'-'.$cur_start_date['mday'];

        return array('start'=>$cur_start_date, 'end'=>$cur_end_date);
    }
}
?>