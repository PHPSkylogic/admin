<?php
/**
 * Represents captcha as an image and writes the answer to the session.
 */
class CaptchaController {
	function  __construct()
	{
	}
	
	function indexAction()
	{
		$captcha = new Captcha3D();
		$vars['captcha'] = $captcha->show();
	}
}
?>