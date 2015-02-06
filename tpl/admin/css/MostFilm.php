<?php

class MostFilm{

    private $db;
	public function __construct($db)
	{
        $this->db=$db;
	}
	
	public function getRating($word)
	{
        $word=trim($word);
        $pars=array("e"=>"е","c"=>"с","k"=>"к","p"=>"р","x"=>"х","y"=>"у","b"=>"б","a"=>"а","a"=>"а","H"=>"Н","H"=>"Н","m"=>"м"," "=>"%20");
        $word=strtr($word, $pars);
        $arr=explode('(', $word);

        if(!isset($arr[1]))return false;
        $word=trim($arr[0]);
        $year=trim(str_replace(")", "", $arr[1]));

        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                    "Cookie: foo=bar\r\n"
            )
        );
        $context = stream_context_create($opts);echo"http://www.kinopoisk.ru/s/type/film/find/".$word."/m_act%5Byear%5D/".$year."/<br>";
        $content = @file_get_contents("http://www.kinopoisk.ru/s/type/film/find/".$word."/m_act%5Byear%5D/".$year."/", false, $context);
        $content = iconv('Windows-1251', 'UTF-8', $content);

        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        $results = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' rating ')]");

        //var_info($results);
        foreach ($results as $node)
        {
            if($node->hasAttribute( 'class' ))
            {
                //echo $node->nodeValue.'===';

                $rating = $node->nodeValue;
                break;
            }

        }

        if(isset($rating))
        {
            $text='';
            $spaner = $xpath->query("//*[contains(@class, 'info')]");
            foreach ($spaner as $node)
            {
                //echo $node->nodeValue;
                $text=$node->nodeValue;
                break;
            }
            $arr = array('сша','джеки чан','франция');
            $str = mb_strtolower($text, 'UTF-8');

            $upload = false;
            foreach($arr as $row3)
            {
                $pos = strripos($str, $row3);
                if ($pos === false)
                {
                    $upload = 0;
                }
                else{
                    $upload = 1;
                    break;
                }
            }

            if(!$upload)return false;


            $name='';
            $spaner = $xpath->query("//*[contains(@class, 'name')]");
            foreach ($spaner->item(0)->childNodes as $node)
            {
                $name = $node->nodeValue;
                break;
            }

            if($name!='')
            {
                $name=strtr($name, $pars).'%20';
                echo $name.'=='.$word.'=='.$rating.'<br>';
                if($name==$word)
                {

                    return $rating;
                }
            }
        }
        return false;
    }
}
?>