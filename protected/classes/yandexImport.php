<?php
class yandexImport{
	private $registry;
	public $db;
	public function __construct($sets)
	{
		$this->registry = $sets['registry'];
		$this->db = $sets['db'];//Connect to database
		$this->view = new View($sets['registry']);
		$this->settings = $sets['settings'];
		$this->params = $sets['params'];
		$this->translation = $sets['translation'];
		$this->registry = $sets['registry'];
        
        $row = $this->db->row("SELECT * FROM price_type WHERE `default`='1'");
        $this->price_type=$row['id'];
        $this->widthImage=$this->settings['width_product'];
        $this->heightImage=$this->settings['height_product'];
	}
    
	public function importXml($filename = false, $trunc = false) 
	{
		$this->data['file'] = $filename;
		$this->data['index'] = array();
		$this->data['is_offer'] = false;
		$this->data['date'] = date("Y-m-d H:i:s", strtotime(date('Y-m-d H:i:s')));
		$prodCount = 0;

		if(file_exists($this->data['file']))
        {
			$xmlString = file_get_contents($this->data['file']);

			$this->data['xml'] = simplexml_load_string($xmlString);
			
			$this->data['products'] = $this->data['categories'] = array();
			$this->data['date'] = date("Y-m-d H:i:s");
			
			if(empty($this->data['xml']->shop->categories->category) && empty($this->data['xml']->shop->offers->offer))
            {
				unlink($filename);
				return messageAdmin('Некорректный файл импорта', 'error');
			}
            else{
				if($trunc)
                {
					$this->flushTables();
				}

				$this->data['manufacturer'] = array();
				if($this->data['tmp'] = $this->getCats())
                {
					foreach($this->data['tmp'] as $key => $value)
                    {
						$this->data['manufacturer'][((String)str_replace(array(' ', ')', '('), '', $value['cat_name']))] = array('id' => $value['id'], 'parent' => $value['sub']);
					}

				}
                else $this->data['manufacturer'] = array();

				$catalogSort = 0;
				foreach($this->data['xml']->shop->categories->category as $key => $value)
                {
					$this->data['index_manu'] = (String)str_replace(array(' ', ')', '('), '',trim($value[0]));
					if (isset($this->data['manufacturer'][$this->data['index_manu']]))
                    {
						foreach ($this->data['xml']->shop->offers->offer as $product)
                        {
							if (($product->checked != "true") && ((int)$product->categoryId == (int)$value['id']))
                            {
								$product->categoryId[0] = $this->data['manufacturer'][$this->data['index_manu']]['id'];
								$product->checked[0] = "true";
							}
						}

						$value['id'] = $this->data['manufacturer'][$this->data['index_manu']]['id'];

					}
                    else{
						if (empty($value['parentId']))
                        {
							$this->data['pars'] = (int)0;
						}
                        else $this->data['pars'] = (int)$value['parentId'];
						
						$this->data['save'] = array(
							'id'=>(int)$value['id'] ,
							'name' => (String)$value[0],
							'parent' => $this->data['pars'],
							'url' => String::translit((String)$value[0]),
							'sort' => $catalogSort,
						);

						unset($this->data['pars']);

						$this->data['new_id'] = $this->saveCat($this->data['save']);
						$catalogSort++;

						foreach ($this->data['xml']->shop->offers->offer as $product)
                        {
							if (($product->checked != "true") && ((int)$product->categoryId === (int)$value['id']))
                            {
								$product->categoryId[0] = (String)$this->data['new_id'];
								$product->checked[0] = "true";
							}
						}

						foreach ($this->data['xml']->shop->categories->category as $cat)
                        {
							if ((int)$cat['parentId'] === (int)$value['id'])
                            {
								$cat['parentId'] = (int)$this->data['new_id'];
							}
						}

						$value['id'] = $this->data['new_id'];
						unset($this->data['new_id']);
					}
				}

				foreach ($this->data['xml']->shop->currencies->currency as $value)
                {
					$attrs = $value->attributes();
					$code = $attrs['id'];
					$rate = $attrs['rate'];
                    
                    if($rate != 1)
                    {
                       $this->db->query("INSERT INTO currency SET `id`=?, `rate`=?", array($code, $rate)); 
                    }
				}
				
				foreach ($this->data['xml']->shop->offers->offer as $key => $value)
                {
                    $id=(String)$value->attributes()->id;
					if (empty($value->url[0]))
                    {
						$value->url = String::translit($value->model);
					}
                    else $value->url = $value->url;

					$this->data['save'] = array(
						'code' => (String)$value->vendorCode[0], 
						'active' => 1, 
						'price' => floatval($value->price) , 
						'old_price' => floatval(0.00), 
						'count' => 1, 
						'material' => (int)1, 
						'date_add' => $this->data['date'], 
						'currency' => (String)$value->currencyId,
						'url' => String::translit($value->name), 
						'name' => (String)$value->name, 
						'picture'=>$value->picture, 
						'title' => '', 
						'keywords' => '', 
						'description' => (String)$value->description, 
						'body_m', 
						'body', 
						'catalog_id' => (int)$value->categoryId[0], 
						'catalog_id2' => (int)$value->categoryId[1]);

					$this->data['save']['url'] = $this->mb_str_replace('&mdash;', '-', $this->data['save']['url']);

					$this->savePrd($this->data['save']);
					$prodCount++;
				}

				unlink($filename);
			//	$this->db->query("UPDATE `users` SET `last_upload_count`=?, `last_upload_date`=? WHERE `id`=?",array($prodCount, date("Y-m-d H:i:s"), (int)$_SESSION['user_id']));
				return messageAdmin('Товары импортированы успешно');

				}

		} else exit('Failed to open: '.$this->data['file']);
	}
	
	private function flushTables()
	{
		$this->db->query("
						DELETE FROM currency WHERE `base`!='1';
						DELETE FROM ru_product;
						DELETE FROM ru_catalog;
						DELETE FROM product;
						DELETE FROM catalog;
						DELETE FROM product_catalog;
		");
		
		/* delete photo */
			/*if(is_dir("files/product/".$dbPrefix."/")) {
				removeDir("files/product/".$dbPrefix."/");
			}*/
	}
	
	private function getCats($id = false)
	{
		if($id){
			 $this->data['sql'] = $this->db->row("SELECT rc.catalog_id, c.sub as parent_id  FROM ru_catalog` rc 
													LEFT JOIN catalog c ON c.id = rc.catalog_id 
													WHERE rc.name = '$id'");
		} else {
			$this->data['sql'] = $this->db->rows("SELECT  c.*, rc.name as cat_name
												  FROM catalog c
												  
                                                  LEFT JOIN ru_catalog rc 
                                                  ON c.id=rc.catalog_id ");
		}
		return $this->data['sql'];
	}
	
	private function saveCat($data = array())
	{
		if($data['parent'] === 0) $data['parent'] = NULL;

		if(! $this->db->row("SELECT `id` FROM catalog WHERE `id`=?", array($data['id']))) {
			$url = $data['url'];
			$i =1;
			while($this->db->row("SELECT `id` FROM catalog WHERE `url`=?", array($url))) {
				$url = $data['url'].'-'.$i++;
			}
			$this->db->query("INSERT INTO catalog (id, sub, sort, active, url) VALUES (?,?,?,?,?)", array($data['id'], $data['parent'], $data['sort'], 0, $url));
			$this->db->query("INSERT INTO ru_catalog SET `catalog_id`=?, `name`=?", array($data['id'], $data['name']));
		}
		return $data['id'];
	}
	
	private function savePrd($data = array())
	{
		if((int)$this->db->cell("SELECT `id` FROM product WHERE `url`=?", array($data['url']))==0)
		{
			$url = $data['url'];
			$i = 1;
			while($this->db->row("SELECT `id` FROM product WHERE `url`=?", array($url)))
            {
				$url = $data['url'].'-'.$i++;
			}
			
			$this->data['insert_id'] = $this->db->insert_id("INSERT INTO product SET code=?, active=?, date_add=?, url=?", array($data['code'], $data['active'], $data['date_add'], $url));
            $this->db->query("INSERT INTO price SET price=?, price_type_id=?, product_id=?", array($data['price'], $this->price_type, $this->data['insert_id']));
			if($this->data['insert_id'])
            {
				$dir = Dir::createDir($this->data['insert_id']);
				if($data['picture']!=''&&$this->SavePicture($data['picture'], $dir[0].$this->data['insert_id']))
                {
                    $this->db->query("UPDATE product SET `photo`=? WHERE id=?", array($dir[0].$this->data['insert_id'].'_s.jpg', $this->data['insert_id']));
                }
                
				

				$parentCat = $this->db->row("SELECT sub FROM catalog WHERE id=?",array($data['catalog_id']));
				if ($parentCat)
                {
					$this->db->query("UPDATE catalog SET `active`=? WHERE id=?", array(1, $parentCat['sub']));
					$rootCat = $this->db->row("SELECT sub FROM catalog WHERE id=?",array($parentCat['sub']));
					if($rootCat)
                    {
						$this->db->query("UPDATE catalog SET `active`=? WHERE id=?", array(1, $rootCat['sub']));
					}
                    
                    $this->db->query("INSERT INTO product_catalog SET product_id=?, catalog_id=?;
     								  UPDATE catalog SET `active`=? WHERE id=?", array($this->data['insert_id'], $data['catalog_id'], 1, $data['catalog_id']));

				}
                
                if($data['catalog_id2']) 
                {
                    $parentCat2 = $this->db->row("SELECT sub FROM catalog WHERE id=?",array($data['catalog_id2']));
    				if ($parentCat2)
                    {
                        $this->db->query("INSERT INTO product_catalog SET product_id=?, catalog_id=?;
                                          UPDATE catalog SET `active`=? WHERE id=?", array($this->data['insert_id'], $data['catalog_id2'], 1, $data['catalog_id2']));
                    }
                }
				return	$this->db->query("INSERT INTO ru_product SET `name`=?, `body`=?, `product_id`=?", array($data['name'], $data['description'], $this->data['insert_id']));

			}
            else return false;
		}
        else return false;
	}
	
	private function SavePicture($url, $saveto)
	{
	    $flag=false;
	    $bigPhoto=$saveto.'.jpg';
        $smallPhoto=$saveto.'_s.jpg';
		$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$raw=curl_exec($ch);
		curl_close ($ch);
		if(file_exists($bigPhoto))
        {
			unlink($bigPhoto);
		}
		$fp = fopen($bigPhoto,'x');
		fwrite($fp, $raw);
		fclose($fp);
        Images::resizeImage($bigPhoto, '', $smallPhoto, $this->widthImage, $this->heightImage);
        
        if(file_exists($smallPhoto))
        {
            $flag=true;
		}
        return $flag;
	}
	
	private function mb_str_replace($search, $replace, $subject, &$count = 0) {
		if (!is_array($subject)) {
			// Normalize $search and $replace so they are both arrays of the same length
			$searches = is_array($search) ? array_values($search) : array($search);
			$replacements = is_array($replace) ? array_values($replace) : array($replace);
			$replacements = array_pad($replacements, count($searches), '');
 
			foreach ($searches as $key => $search) {
				$parts = mb_split(preg_quote($search), $subject);
				$count += count($parts) - 1;
				$subject = implode($replacements[$key], $parts);
			}
		} else {
			// Call mb_str_replace for each subject in array, recursively
			foreach ($subject as $key => $value) {
				$subject[$key] = mb_str_replace($search, $replace, $value, $count);
			}
		}
 
		return $subject;
	}
}
?>