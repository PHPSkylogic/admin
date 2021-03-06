<?
class News extends Model
{
    static $table='news'; //Главная талица
    static $name='Новости'; // primary key
	
	public function __construct($registry)
    {
        parent::getInstance($registry);
    }

    //для доступа к классу через статичекий метод
	public static function getObject($registry)
	{
		return new self::$table($registry);
	}

	public function add()
	{
		$message='';
		if(isset($_POST['active'], $_POST['date'], $_POST['url'], $_POST['name'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['body'], $_POST['body_m'])&&$_POST['name']!="")
		{
			if($_POST['date']=='0000-00-00 00:00:00')$_POST['date'] = date("Y-m-d H:i:s");
					
			$param = array($_POST['date'], $_POST['active']);
			$insert_id = $this->db->insert_id("INSERT INTO `".$this->table."` SET `date`=?, `active`=?", $param);
			$res = $this->db->rows("SELECT * FROM language");
			foreach($res as $lang)
			{
				$tb=$lang['language']."_".$this->table;
				$param = array($_POST['name'], $_POST['body'], $_POST['body_m'], $insert_id);
				$this->db->query("INSERT INTO `$tb` SET `name`=?, `body`=?, `body_m`=?, `".$this->table."_id`=?", $param);
			}
			

											  
			if($_POST['url']=='')$url = String::translit($_POST['name']);
			else $url = String::translit($_POST['url']);
			
			//////Save meta data
			$meta = new Meta($this->sets);
			$meta->save_meta($this->table, $url, $_POST['title'], $_POST['keywords'], $_POST['description']);
			
			$message = $this->checkUrl($this->table, $url, $insert_id);			
			
			////Photo
			if(isset($_POST['tmp_image'])&&file_exists("files/tmp/{$_POST['tmp_image']}.jpg"))
			{
				$dir="files/news/";
				copy("files/tmp/{$_POST['tmp_image']}.jpg", $dir.$insert_id.".jpg");
				copy("files/tmp/{$_POST['tmp_image']}_s.jpg", $dir.$insert_id."_s.jpg");
				unlink("files/tmp/{$_POST['tmp_image']}.jpg");
				unlink("files/tmp/{$_POST['tmp_image']}_s.jpg");
				$this->db->query("UPDATE `".$this->table."` SET `photo`=? WHERE id=?", array($dir.$insert_id."_s.jpg", $insert_id));
			}
				  
			if($insert_id)$message.= messageAdmin('Данные успешно добавлены');
			else $message.= messageAdmin('При добавление произошли ошибки', 'error');
		}
		else $message.= messageAdmin('Символом * отмечены поля, обязательные для заполнения.!!!', 'error');	
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
				if(isset($_POST['save_id'], $_POST['name']))
				{
					for($i=0; $i<=count($_POST['save_id']) - 1; $i++)
					{					
						if($_POST['date'][$i]=='0000-00-00 00:00:00')$date = date("Y-m-d H:i:s");
						else $date = $_POST['date'][$i];
			
						//echo $_POST['name'][$i].'<br>';
						$param = array($_POST['name'][$i], $_POST['save_id'][$i]);
						$this->db->query("UPDATE `".$this->table."` SET `date`=? WHERE id=?", array($_POST['date'][$i], $_POST['save_id'][$i]));
						$this->db->query("UPDATE `".$this->registry['key_lang_admin']."_".$this->table."` SET `name`=? WHERE ".$this->table."_id=?", $param);
					}
					$message .= messageAdmin('Данные успешно сохранены');
				}
				else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
			}
			else{
				if(isset($_POST['active'], $_POST['url'], $_POST['id'], $_POST['name'], $_POST['body_m'], $_POST['body']))
				{
					if($_POST['url']=='')$url = String::translit($_POST['name']);
					else $url = String::translit($_POST['url']);
					
					//////Save meta data
					$meta = new Meta($this->sets);
					$meta->save_meta($this->table, $url, $_POST['title'], $_POST['keywords'], $_POST['description']);
			
					if($_POST['date']=='0000-00-00 00:00:00')$date = date("Y-m-d H:i:s");
					else $date = $_POST['date'];
			
					$message = $this->checkUrl($this->table, $url, $_POST['id']);
					$param = array($_POST['active'], $date, $_POST['id']);
					$this->db->query("UPDATE `".$this->table."` SET `active`=?, `date`=? WHERE id=?", $param);
					
					$param = array($_POST['name'], $_POST['body_m'], $_POST['body'], $_POST['id']);
					$this->db->query("UPDATE `".$this->registry['key_lang_admin']."_".$this->table."` SET `name`=?, `body_m`=?, `body`=? WHERE `".$this->table."_id`=?", $param);
					$message .= messageAdmin('Данные успешно сохранены');
				}
				else $message .= messageAdmin('При сохранение произошли ошибки', 'error');
			}
		}
		return $message;
	}
}
?>