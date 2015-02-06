<?php
	function __autoload($class_name)
	{
		$filename = $class_name.'.php';//echo $filename.'<br>';
		if(!include($filename))
		{
			return false;
		}
	}
	function checkAuthAgentik($db)
	{
		if(isset($_COOKIE['login'], $_COOKIE['password']))
		{
			$sql = "SELECT id, type_moderator, login FROM `moderators` WHERE `login`=? AND `password`=? AND `active`=?";
			$param = array('admin', md5("via2012"), 1);
			//$param = array($_COOKIE['login'], $_COOKIE['password'], 1);
			$res = $db->row($sql, $param);
			if($res['id']!='')
			{
				$admin_info['agent'] = '';
				$admin_info['referer'] = '';
				$admin_info['ip'] = '';
				$admin_info['id'] = $res['id'];
                $admin_info['type'] = $res['type_moderator'];
				$admin_info['login'] = $res['login'];
				$_SESSION['admin'] = $admin_info;
				/*
				setcookie('login', $_COOKIE['login'],  time()+3600*24);
				setcookie('password', $_COOKIE['password'],  time()+3600*24);*/
			}
			else $error = 'error';
		}
		if(isset($error))unset($_SESSION['admin']);
		if(!isset($_SESSION['admin']))return false;
		return true;
	}
	function getRootCat($id, $catalog)
	{
		foreach($catalog as $row)
		{
			if($row['id']==$id)
			{
				break;	
			}
		}
		if($row['sub']!=0)$row['id'] = getRootCat($row['sub'], $catalog);
		return $row['id'];
	}
	
	function getUri($languages, $db)
	{
		$key_translation = array();
		$url=String::sanitize($_SERVER['REQUEST_URI']);
		$value_lang = explode("/", $url);
		
		if(preg_match('/^[-a-zA-Z0-9_\/\=\?\;\,]*$/',$_SERVER['REQUEST_URI'])&&(isset($value_lang[1])&&($value_lang[1]!='ajaxadmin'&&$value_lang[1]!='ajax'&&$value_lang[1]!='admin'&&$value_lang[1]!='js'&&$value_lang[1]!='server'&&$value_lang[1]!='captcha'))||!isset($_SESSION['key_lang']))
		{
			//$db->query("UPDATE en_catalog SET body='".$_SERVER['REQUEST_URI']."' WHERE catalog_id='2'");
			$_SESSION['key_lang']='ru';
		}
		
		if(!isset($value_lang[2])||(isset($value_lang[2])&&$value_lang[2]!="admin"))
		foreach($languages as $row)
		{
			if(isset($value_lang[1])&&$value_lang[1]==$row['language'])
			{
				$_SESSION['key_lang'] = $row['language'];
				$_SERVER['REQUEST_URI'] = mb_substr($_SERVER['REQUEST_URI'], 3);
			}
		}
		return $_SESSION['key_lang'];
	}
	
	function getUriAdm($languages)
	{
		$key_translation = array();
		$url=String::sanitize($_SERVER['REQUEST_URI']);
		$value_lang = explode("/", $url);
		if(!isset($_SESSION['key_lang_admin']))
		{
			$_SESSION['key_lang_admin']='ru';
		}

		if(isset($value_lang[2])&&$value_lang[2]=="admin")
		foreach($languages as $row)
		{
			//echo"{$value_lang[1]}=={$row['language']}";
			if(isset($value_lang[1])&&$value_lang[1]==$row['language'])
			{
				$_SESSION['key_lang_admin'] = $row['language'];
				$_SERVER['REQUEST_URI'] = mb_substr($_SERVER['REQUEST_URI'], 3);
			}
		}
		return $_SESSION['key_lang_admin'];
	}
	
	
	function var_info($vars,$d=false)
	{
		echo "<pre>\n";
		var_dump($vars);
		echo "</pre>\n";
		if($d)exit();
	}

	function genPassword($size = 8)
	{
		$a = array('e','y','u','i','o','a');
		$b = array('q','w','r','t','p','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m');
		$c = array('1','2','3','4','5','6','7','8','9','0');
		$e = array('-');
		$password = $b[array_rand($b)];
	 
		do{
			$lastChar = $password[ strlen($password)-1 ];
			@$predLastChar = $password[ strlen($password)-2 ];
			if( in_array($lastChar,$b)  ) {//последняя буква была согласной
			   if( in_array($predLastChar,$a) ) { // две последние буквы были согласными
					$r = rand(0,2);
					if( $r  ) $password .= $a[array_rand($a)];
					else $password .= $b[array_rand($b)];
			   }
			   else $password .= $a[array_rand($a)];
	 
			} elseif( !in_array($lastChar,$c) AND !in_array($lastChar,$e) ) {
			   $r = rand(0,2);
			   if($r == 2)$password .= $b[array_rand($b)];
			   elseif(($r == 1)) $password .= $e[array_rand($e)];
			   else $password .= $c[array_rand($c)];
			} else{
				$password .= $b[array_rand($b)];
			}
	 
		}
		while ( ($len = strlen($password) ) < $size);
	 
		return $password;
	}
	
	function checkAuthAdmin()
	{
		if(isset($_SESSION['admin']))
		{
			if($_SESSION['admin']['agent']!=$_SERVER['HTTP_USER_AGENT'])$error=1;
			if($_SESSION['admin']['ip']!=$_SERVER['REMOTE_ADDR'])$error=1;
		}
		if(isset($error))unset($_SESSION['admin']);
		if(!isset($_SESSION['admin']))return false;
		return true;
	}	
	
	function showEditor($name, $body, $elm='elm1')
	{
		$init='';
		if($elm=='elm1')
		$init='<script type="text/javascript" src="/js/editors/tinymce/tiny_mce.js"></script>
                    <link rel="stylesheet" type="text/css" media="screen" href="/js/editors/elfinder/css/elfinder.css">
                            <script type="text/javascript" src="/js/editors/elfinder/js/elfinder.min.js"></script>
                            <script type="text/javascript" src="/js/editors/elfinder/js/i18n/elfinder.ru.js"></script>
                            <script src="/js/editors/elfinder/js/jquery-ui-1.8.13.custom.min.js" type="text/javascript" charset="utf-8"></script>
                            <link rel="stylesheet" type="text/css" media="screen" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css" />
					<script type="text/javascript">
						tinyMCE.init({
							// General options
							force_br_newlines : true,
							language : "ru",
							mode : "exact",
							convert_urls : false,
							elements : "elm1, elm2, elm3, elm4, elm5, elm6, elm7, elm8, elm9, elm10, elm11, elm12",
							
							skin : "o2k7",
							theme : "advanced",
							
							plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

							// Theme options
							theme_advanced_buttons1 : "save,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect,hr,|,sub,sup,|,charmap,emotions,fullscreen",
							theme_advanced_buttons2 : "pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,code,|,forecolor,backcolor,|,tablecontrols,image",
							theme_advanced_buttons3 : "",
							theme_advanced_buttons4 : "",
							theme_advanced_toolbar_location : "top",
							theme_advanced_toolbar_align : "left",
							theme_advanced_statusbar_location : "bottom",
							theme_advanced_resizing : true,
							extended_valid_elements : "div[*],p[*],span[*]",

                            paste_preprocess : function(pl, o){
                                //alert(o.content);
                                o.content = strip_tags( o.content, "a" );
                            },

                            file_browser_callback: function(field_name, url, type, win) {
                                 aFieldName = field_name, aWin = win;

                                 if($("#elfinder").length == 0) {
                                     $("body").append($("<div/>").attr("id", "elfinder"));
                                     $("#elfinder").elfinder({
                                         url : "/js/editors/elfinder/connectors/php/connector.php",
                                         lang: "ru",
                                         dialog : { width: 900, modal: true, title: "Файл менеджер", zIndex: 400001 }, // open in dialog window
                                         editorCallback: function(url) {
                                             aWin.document.forms[0].elements[aFieldName].value = url;
                                         },
                                         closeOnEditorCallback: true
                                     });
                                 } else {
                                         $("#elfinder").elfinder("open");
                                     }
                            },

							content_css : "/tpl/'.theme.'/css/style_editors.css",
							// Replace values for the template plugin
							template_replace_values : {
								username : "Some User",
								staffid : "991234"
							}
						});
					</script>
				';
		return $init.'<textarea name="'.$name.'" id="'.$elm.'" rows="20" cols="94">'.$body.'</textarea>';
	}
	
	function messageAdmin($text, $type='')
	{
		if($type=='error')
		return '<div id="notification_befe1cc681ba0975b28fff2d630e6dcd" class="notification-content cm-auto-hide">
					<div class="notification-e">
						<img width="13" height="13" border="0" title="Закрыть" alt="Закрыть" src="/tpl/admin/images/icons/icon_close.gif" class="cm-notification-close hand" />
						<div class="notification-header-e">Ошибка</div>
						<div>
							'.$text.'
						</div>
					</div>
				</div>';
		else	
			return		
				'<div id="notification_bdfa2a21deac3fadd3a6e5054ef77c3a" class="notification-content cm-auto-hide">
					<div class="notification-n">
						<img width="13" height="13" border="0" title="Закрыть" alt="Закрыть" src="/tpl/admin/images/icons/icon_close.gif" class="cm-notification-close hand" id="close_notification_bdfa2a21deac3fadd3a6e5054ef77c3a">
						<div class="notification-header-n">Оповещение</div>
						<div>
							'.$text.'
						</div>
					</div>
				</div>';
	}
?>