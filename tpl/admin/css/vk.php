<?php

//namespace BW;

/**
 * The Vkontakte PHP SDK
 *
 * @author Bocharsky Victor, https://github.com/Vastly
 */
class Vkontakte
{

    const VERSION = '5.5';

    /**
     * The application ID
     * @var integer
     */
    private $appId;

    /**
     * The application secret code
     * @var string
     */
    private $secret;

    /**
     * The scope for login URL
     * @var array
     */
    private $scope = array();

    /**
     * The URL to which the user will be redirected
     * @var string
     */
    private $redirect_uri;

    /**
     * The response type of login URL
     * @var string
     */
    private $responceType = 'code';
    private $path_img = '/home/h43269/data/www/bonn-voyage.com/';

    /**
     * The current access token
     * @var \StdClass
     */
    private $accessToken;


    private $db;
    /**
     * The Vkontakte instance constructor for quick configuration
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (isset($config['access_token'])) {

            $this->setAccessToken(json_encode(array('access_token' => $config['access_token'])));
        }
        if (isset($config['app_id'])) {
            $this->setAppId($config['app_id']);
        }
        if (isset($config['secret'])) {
            $this->setSecret($config['secret']);
        }
        if (isset($config['scopes'])) {
            $this->setScope($config['scopes']);
        }
        if (isset($config['redirect_uri'])) {
            $this->setRedirectUri($config['redirect_uri']);
        }
        if (isset($config['response_type'])) {
            $this->setResponceType($config['response_type']);
        }
        if (isset($config['db'])) {
            $this->setDb($config['db']);
        }
    }


    /**
     * Get the user id of current access token
     * @return integer
     */
    public function getUserId()
    {

        return $this->accessToken->user_id;
    }

    /**
     * Set the db connect
     * @param array $db
     * @return db connect
     */
    public function setDb($db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Get the application id
     * @return integer
     */
    public function getDb()
    {

        return $this->db;
    }

    /**
     * Set the application id
     * @param integer $appId
     * @return \Vkontakte
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * Get the application id
     * @return integer
     */
    public function getAppId()
    {

        return $this->appId;
    }

    /**
     * Set the application secret key
     * @param string $secret
     * @return \Vkontakte
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the application secret key
     * @return string
     */
    public function getSecret()
    {

        return $this->secret;
    }

    /**
     * Set the scope for login URL
     * @param array $scope
     * @return \Vkontakte
     */
    public function setScope(array $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get the scope for login URL
     * @return array
     */
    public function getScope()
    {

        return $this->scope;
    }

    /**
     * Set the URL to which the user will be redirected
     * @param string $redirect_uri
     * @return \Vkontakte
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;

        return $this;
    }

    /**
     * Get the URL to which the user will be redirected
     * @return string
     */
    public function getRedirectUri()
    {

        return $this->redirect_uri;
    }

    /**
     * Set the response type of login URL
     * @param string $responceType
     * @return \Vkontakte
     */
    public function setResponceType($responceType)
    {
        $this->responceType = $responceType;

        return $this;
    }

    /**
     * Get the response type of login URL
     * @return string
     */
    public function getResponceType()
    {

        return $this->responceType;
    }

    /**
     * Get the login URL via Vkontakte
     * @return string
     */
    public function getLoginUrl()
    {

        return 'https://oauth.vk.com/authorize'
        . '?client_id=' . urlencode($this->getAppId())
        . '&scope=' . urlencode(implode(',', $this->getScope()))
        . '&redirect_uri=' . urlencode($this->getRedirectUri())
        . '&response_type=' . urlencode($this->getResponceType())
        . '&v=' . urlencode(self::VERSION);
    }

    /**
     * Check is access token expired
     * @return boolean
     */
    public function isAccessTokenExpired()
    {

        return time() > $this->accessToken->created + $this->accessToken->expires_in;
    }

    /**
     * Authenticate user and get access token from server
     * @param string $code
     * @return \Vkontakte
     */
    public function authenticate($code = '0bf2586fdb4ee99a5e')
    {
        $code = $code ? $code : $_GET['code'];

        $url = 'https://oauth.vk.com/access_token'
            . '?client_id=' . urlencode($this->getAppId())
            . '&client_secret=' . urlencode($this->getSecret())
            . '&code=' . urlencode($code)
            . '&redirect_uri=' . urlencode($this->getRedirectUri());

        $token = $this->curl($url);
        $data = json_decode($token);
        $data->created = time(); // add access token created unix timestamp
        $token = json_encode($data);

        $this->setAccessToken($token);

        return $this;
    }

    /**
     * Set the access token
     * @param string $token The access token in json format
     * @return \Vkontakte
     */
    public function setAccessToken($token)
    {
        $this->accessToken = json_decode($token);

        return $this;
    }

    /**
     * Get the access token
     * @param string $code
     * @return string The access token in json format
     */
    public function getAccessToken()
    {

        return json_encode($this->accessToken);
    }

    /**
     * Make an API call to https://api.vk.com/method/
     * @return string The response, decoded from json format
     */
    public function api($method, array $query = array())
    {
        /* Generate query string from array */
        $parameters = array();
        foreach ($query as $param => $value) {
            $q = $param . '=';
            if (is_array($value)) {
                $q .= urlencode(implode(',', $value));
            } else {
                $q .= urlencode($value);
            }

            $parameters[] = $q;
        }

        $q = implode('&', $parameters);
        if (count($query) > 0) {
            $q .= '&'; // Add "&" sign for access_token if query exists
        }
        $url = 'https://api.vk.com/method/' . $method . '?' . $q . 'access_token=' . $this->accessToken->access_token;
        $result = json_decode($this->curl($url));


        if (isset($result->response)) {

            return $result->response;
        }

        return $result;
    }

    /**
     * Make the curl request to specified url
     * @param string $url The url for curl() function
     * @return mixed The result of curl_exec() function
     * @throws \Exception
     */
    protected function curl($url)
    {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        // return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // disable SSL verifying
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // $output contains the output string
        $result = curl_exec($ch);

        if (!$result) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
        }

        // close curl resource to free up system resources
        curl_close($ch);

        if (isset($errno) && isset($error)) {
            throw new \Exception($error, $errno);
        }

        return $result;
    }


    /**
     * @param $publicID int vk group official identifier
     * @param $fullServerPathToImage string full path to the image file, ex. /var/www/site/img/pic.jpg
     * @param $text string message text
     * @param $tags array message tags
     * @return bool true if operation finished successfully and false otherwise
     */
    public function postToPublic($publicID, $text, $fullServerPathToImage, $id, $db, $tags = array())
    {
        $this->db=$db;


        $text=preg_replace("/^\[[a-z0-9|]*/i", "", $text);
        $text = str_replace('<br>', "----", $text);
        $text = str_replace('<br />', "----", $text);
        $text = str_replace('<br >', "----", $text);
        $text = str_replace('<br/>', "----", $text);
        $text = preg_replace ("/[^a-zа-я0-9.,:+?!—()-\s\xD0\x81]/ui","",$text);
        $text = str_replace('----', "\n", $text);

        $text = html_entity_decode($text);
        //echo $text;
        $attach='';

        $res = $db->rows("SELECT * FROM photo WHERE post_id='{$id}'");
        if(isset($res[0]))
        {
            $response = $this->api('photos.getWallUploadServer', array(

                'group_id' => $publicID,
                'version' => '5.27',
            ));
            foreach($res as $row)
            {
                $fullServerPathToImage=$this->path_img.'soc/'.$row['img'];
                $uploadURL = $response->upload_url;
                $output = array();
                exec("curl -X POST -F 'photo=@$fullServerPathToImage' '$uploadURL'", $output);
                $response2 = json_decode($output[0]);


                $response3 = $this->api('photos.saveWallPhoto', array(
                    'group_id' => $publicID,
                    'photo' => $response2->photo,
                    'server' => $response2->server,
                    'hash' => $response2->hash,
                ));
                //var_info($response3);
                if($attach!='')$attach.=',';
                $attach.=$response3[0]->id;
                sleep(2);
            }
        }
        elseif($fullServerPathToImage!='')
        {
            $response = $this->api('photos.getWallUploadServer', array(

                'group_id' => $publicID,
                'version' => '5.27',
            ));
            $uploadURL = $response->upload_url;
            $output = array();
            exec("curl -X POST -F 'photo=@$fullServerPathToImage' '$uploadURL'", $output);
            $response = json_decode($output[0]);


            $response = $this->api('photos.saveWallPhoto', array(
                'group_id' => $publicID,
                'photo' => $response->photo,
                'server' => $response->server,
                'hash' => $response->hash,
            ));
            $attach=$response[0]->id;
        }


        $res = $db->rows("SELECT * FROM videos WHERE post_id='{$id}'");
        if(isset($res[0]))
        {
            foreach($res as $row)
            {
                /*$res2 = $this->api('video.add',array(
                    'video_id'=>$row['video_id'],
                    'owner_id'=>$row['owner_id'],
                ));
                var_info($res2);— Пепельница должен выбрать свой дальнейший путь в жизни, он может быть просто хорошим чернокожим или стать опасным чернокожим, примкнув к банде своего кузена.
                */
                if($attach!='')$attach.=',';
                $attach.='video'.$row['owner_id'].'_'.$row['video_id'];
            }
        }

        $res = $db->rows("SELECT * FROM audio WHERE post_id='{$id}'");
        if(isset($res[0]))
        {

            foreach($res as $row)
            {
                $audio=true;
                if($attach!='')$attach.=',';
                $attach.='audio'.$row['owner_id'].'_'.$row['audio_id'];
            }
        }


        ///poll
        $poll = $this->setPoll($id, $publicID);
        if($poll)$attach.=','.$poll;

        $upload=true;

        if($attach==''&&$text=='')$upload=false;
        else
        {
            $row3 = $db->row("SELECT public_id FROM posts WHERE id='{$id}'");

            if($row3['public_id']==4&&!isset($audio))$upload=false;
        }

        if($upload)
        {
            if(isset($tags[0])&&$tags[0]!='')
            {
                $text .= "\n";
                foreach ($tags as $tag) {

                    $text .= ' #' . str_replace(' ', '_', $tag);
                }
            }

            $arr = array(
                'owner_id' => "-".$publicID,
                'from_group' => 1,
                'message' => $text,
                'attachments'=>$attach,
            );
            //var_info($arr);


            $response = $this->api('wall.post', array(
                'owner_id' => "-".$publicID,
                'from_group' => 1,
                'message' => $text,
                'attachments'=>$attach,
            ));

            if($response==NULL)
            {
                $db->query("DELETE FROM posts WHERE id='{$id}'");
            }
            else{
                $db->query("UPDATE posts SET date_upload='".date('Y-m-d H:i:s')."' WHERE id='{$id}'");
                var_info($response);
            }
        }
        else{
            $db->query("DELETE FROM posts WHERE id='{$id}'");
        }

        return isset($response->post_id);


    }



    public function addFriends($db, $info)
    {
        $response = $this->api('users.search',array(
            'country'=>'2',
            'offset'=>$info['offset_friends'],
            'sort'=>'1',
            'has_photo'=>1,
            'age_from'=>18,
            'group_id'=>$info['groups'],
        ));

        /*$response = $this->api('users.search',array(
            'q'=>'Ирина Савченко',
            'fields'=>'photo_50',
            'country'=>'2',
            'city'=>'292',
            'group_id'=>'82843902',
            'offset'=>'0',
            'sort'=>'1',
        ));*/

        //var_info($response);

        $i=0;
        foreach($response as $row)
        {
            if(isset($row->uid)&&$row->uid!='')
            {
                $response2 = $this->api('friends.add',array(
                    'user_id'=>$row->uid,
                    'text'=>'',
                ));
                //var_info($response2);echo'asd';
                $id = $db->insert_id("INSERT INTO friends SET date='".date("Y-m-d H:i:s")."', `uid`='".$row->uid."',`first_name`='".$row->first_name."',`last_name`='".$row->last_name."', account_id='{$info['id']}' ");
                $i++;
                break;
            }
        }

        $where=", `offset_friends`=`offset_friends`+".$i."";
        if(!isset($id))
        {
            $db->query("UPDATE groups SET `offset`='{$info['offset_friends']}', active='0' WHERE groupId='{$info['groups']}'");

            $where=", groups=''";

            $row = $db->row("SELECT * FROM groups WHERE account_id='{$info['id']}' AND active='1'");
            if($row)
            {
                $where=", groups='{$row['groupId']}'";
            }

            $where.=", offset_friends='0'";


        }
        $db->query("UPDATE accounts SET ".substr($where, 1, strlen($where))." WHERE id='{$info['id']}'");
    }


    public function repost($db, $publicId, $id, $response2='')
    {
        if($response2=='')
        {
            $response = $this->api('wall.get',array(
                'owner_id'=>'-'.$publicId,
                'count'=>1,
                'offset'=>0,
            ));
        }
        else{
            $response=$response2;
        }
        //var_info($response);//[club47131705|Жeлeзный чeлoвек 3  ]
        $src='';
        $i=0;
        foreach($response as $row)
        {
            if(!is_numeric($row))
            {
                $name='';
                if(isset($row->text))$name=preg_replace('~<a\b[^>]*+>|</a\b[^>]*+>~', '', $row->text);
                $row2 = $db->row("SELECT * FROM posts WHERE `name`='$name' AND public_id='$id' AND name!=''");

                if(!$row2)
                {

                    if(isset($row->copy_text))
                    {
                        //12,к,опрос-кого вы бы взяли на ковчег: семью; друзей;никого
                        $comment = explode(',', $row->copy_text);
                        $where='';
                        if($id==3)
                        {
                            $cat_id=5;
                            if(isset($comment[1]))
                            {
                                if(mb_strtolower($comment[1], 'UTF8')=='п')$cat_id=1;
                                if(mb_strtolower($comment[1], 'UTF8')=='ц')$cat_id=2;
                                if(mb_strtolower($comment[1], 'UTF8')=='о')$cat_id=3;
                                if(mb_strtolower($comment[1], 'UTF8')=='к')$cat_id=4;
                            }
                            $where=", cat_id='$cat_id'";


                        }



                        if($comment[0]<date('d'))$mon=date('Y-m-', strtotime('+1 month'));
                        else $mon=date('Y-m-');
                        $time_to_post = $mon.$comment[0];
                    }
                    else{
                        $where='';
                        $time_to_post=0;
                    }

                    $id = $db->insert_id("INSERT INTO posts SET date='".date("Y-m-d H:i:s")."', `time_to_post`='".$time_to_post."', `name`='".$name."',`img`='".$src."', public_id='$id' $where");
                    echo $row->text.'='.$name.'='.$id;


                    ///
                    if(isset($comment[2]))
                    {
                        $poll = explode(':', $comment[2]);

                        if($poll[0]!=''&&$poll[1]!='')
                        {
                            $poll_id = $db->insert_id("INSERT INTO poll SET `post_id`='$id', name='{$poll[0]}'");

                            $poll_var = explode(';', $poll[1]);
                            foreach($poll_var as $row_poll)
                            {
                                $db->query("INSERT INTO poll_var SET `poll_id`='$poll_id', name='{$row_poll}'");
                            }
                        }
                    }

                    if(!is_dir($this->path_img.'soc/files/posts/'.$id))mkdir($this->path_img.'soc/files/posts/'.$id, 0755, true);
                    foreach($row->attachments as $row3)
                    {
                        if($row3->type=='photo')
                        {
                            $src2='';
                            if(isset($row3->photo->src_xxbig)&&$row3->photo->src_xxbig!='')$src2 = $row3->photo->src_xxbig;
                            elseif(isset($row3->photo->src_xbig)&&$row3->photo->src_xbig!='')$src2 = $row3->photo->src_xbig;
                            elseif(isset($row3->photo->src_big)&&$row3->photo->src_big!='')$src2 = $row3->photo->src_big;

                            if($src2!='')
                            {
                                $id2 = $db->insert_id("INSERT INTO photo SET post_id='".$id."', `img`='".$src2."'");
                                $ext=end(explode('.', $src2));
                                $new_src='files/posts/'.$id.'/'.$id2.'.'.$ext;
                                if(file_put_contents($this->path_img.'soc/'.$new_src, file_get_contents($src2)))
                                {
                                    $db->query("UPDATE photo SET img='$new_src' WHERE id='$id2'");
                                }
                                else{
                                    $db->query("DELETE FROM photo WHERE id='$id2'");
                                }
                            }
                        }
                        elseif($row3->type=='video')
                        {

                            $res_video = $this->api('video.get',array(
                                'owner_id'=>$row3->video->owner_id,
                                'videos'=>$row3->video->owner_id.'_'.$row3->video->vid,
                                'count'=>1,
                                'extended'=>1,
                            ));
                            var_info($res_video);
                            //$id2 = $db->insert_id("INSERT INTO videos SET post_id='".$id."', `video_id`='".$row3->video->vid."', `owner_id`='".$row3->video->owner_id."'");

                            if(isset($res_video[1]->vid))
                            {
                                $id2 = $db->insert_id("INSERT INTO videos SET post_id='".$id."', `video_id`='".$row3->video->vid."', `owner_id`='".$row3->video->owner_id."'");
                            }
                            else{
                                $db->query("DELETE FROM posts WHERE id='$id'");
                                break;
                            }
                        }
                        elseif($row3->type=='audio')
                        {

                            $res_audio = $this->api('audio.getById',array(
                                'audios'=>$row3->audio->owner_id.'_'.$row3->audio->aid,
                            ));
                            var_info($res_audio);
                            if(!isset($res_audio[0]->aid))
                            {
                                sleep(2);
                                $res_audio = $this->api('audio.search',array(
                                    'q'=>$row3->audio->artist.' – '.$row3->audio->title,
                                    'count'=>1,
                                ));
                                $res_audio[0]=$res_audio[1];
                                var_info($res_audio);echo'11';
                            }

                            //if(isset($res_audio[1]))$res_audio[0]=$res_audio[1];
                            if(is_array($res_audio)&&isset($res_audio[0], $res_audio[0]->aid))
                            {
                                $db->query("INSERT INTO audio SET post_id='".$id."', `audio_id`='".$res_audio[0]->aid."', `owner_id`='".$res_audio[0]->owner_id."'");
                            }
                        }
                        sleep(2);
                    }

                    $i++;

                    if($response2=='')
                    {
                        $res = $this->api('wall.delete',array(
                            'owner_id'=>'-'.$publicId,
                            'post_id'=>$row->id,
                        ));
                    }
                    //var_info($res);echo'asd';
                    break;
                }
                elseif($response2=='')
                {
                    $res = $this->api('wall.delete',array(
                        'owner_id'=>'-'.$publicId,
                        'post_id'=>$row->id,
                    ));
                }
            }
        }

        if(isset($id))return $id;
    }



    public function setOnline()
    {
        $response = $this->api('account.setOnline');
        var_info($response);
    }

    public function getOnline($db, $id)
    {
        $response = $this->api('friends.getOnline');
        $db->query("INSERT INTO online SET date='".date("Y-m-d H")."', `account_id`='".$id."',`day`='".date("l")."', online='".count($response)."'");


        var_info($response);
    }

    public function delFriend($id)
    {
        $cnt = $this->db->row("SELECT COUNT(*) AS cnt FROM friends WHERE account_id='{$id}'");
        $response = $this->api('friends.getRequests', array('out'=>1, 'count'=>10, 'offset'=>$cnt['cnt']));
        //var_info($response);

        foreach($response as $row)
        {
            $row2 = $this->db->row("SELECT uid FROM friends WHERE uid='{$row}'");

            if(!$row2)
            {
                $this->api('friends.delete',array(
                    'user_id'=>$row,
                ));
            }
        }
    }


    function testVideo()
    {
        $response = $this->api('wall.get',array(
            'owner_id'=>'-48868052',
            'count'=>1,
            'offset'=>2,
        ));


        foreach($response as $row)
        {
            var_info($row->attachments);
            foreach($row->attachments as $row3)
            {
                if($row3->type=='video')
                {


                    echo "INSERT INTO videos SET post_id='', `video_id`='".$row3->video->vid."', `owner_id`='".$row3->video->owner_id."'";
                }
            }

        }

    }


    function testP()
    {echo'asd';
        $res_video = $this->api('video.get',array(
            'owner_id'=>'-285050892',
            'videos'=>'-285050892_171010760',
            'count'=>1,
            'extended'=>1,
        ));
        var_info($res_video[1]->vid);
    }


    function setRepost($publicId)
    {
        $response = $this->api('wall.get',array(
            'owner_id'=>'-'.$publicId,
            'count'=>1,
            'offset'=>0,
        ));

        if(is_array($response))
        {
            foreach($response as $row)
            {
                if(isset($row->id))
                {
                    $response2 = $this->api('wall.get',array(
                        'count'=>5,
                    ));
                    foreach($response2 as $row2)
                    {
                        if(isset($row2->id))
                        {

                            if($row2->copy_post_id==$row->id&&$row2->copy_owner_id=='-'.$publicId)
                            {
                                $found=1;
                                break;
                            }
                            else{
                                echo'Not Finded';

                            }
                        }
                    }

                    if(!isset($found))
                    {
                        $response3 = $this->api('wall.repost',array(
                            'object'=>'wall-'.$publicId.'_'.$row->id,
                        ));
                        var_info($response3);
                        return true;

                    }
                    unset($found);
                }

            }
        }
        return false;
    }

    function setLike($publicId)
    {
        $response = $this->api('wall.get',array(
            'owner_id'=>'-'.$publicId,
            'count'=>1,
        ));

        if(is_array($response)&&isset($response[1],$response[1]->is_pinned)&&$response[1]->is_pinned==1)
        {
            $response = $this->api('wall.get',array(
                'owner_id'=>'-'.$publicId,
                'count'=>1,
                'offset'=>1,
            ));
            
        }

        foreach($response as $row)
        {
            if(isset($row->likes->can_like)&&$row->likes->can_like==1)
            {
                $response3 = $this->api('likes.add',array(
                    'type'=>'post',
                    'owner_id'=>'-'.$publicId,
                    'item_id'=>$row->id,
                ));
                var_info($response3);
            }

        }
    }

    function setPoll($id, $publicID)
    {
        $row = $this->db->row("SELECT * FROM poll WHERE post_id='{$id}'");

        if($row)
        {
            $var=array();
            $res = $this->db->rows("SELECT * FROM poll_var WHERE poll_id='{$row['id']}'");
            foreach($res as $row2)
            {
                $var[]=$row2['name'];
            }

            $response = $this->api('polls.create',array(
                'question'=>$row['name'],
                'is_anonymous'=>1,
                'owner_id'=>'-'.$publicID,
                'add_answers'=>json_encode($var),
            ));

            if($response)
            {
                return 'poll'.substr($response->owner_id,1).'_'.$response->poll_id;
            }
            //var_info($response);
        }
        return false;
    }


    function searchPost($name, $date, $publicID)
    {
        $name = str_replace("&nbsp;", " ", $name);
        $name = str_replace("&mdash;", "-", $name);
        $response = $this->api('wall.search',array(
            'owner_id'=>'-'.$publicID,
            'query'=>'"'.$name.'" Жанр:',
            'count'=>10,
            'offset'=>0,
        ));echo'"'.$name.'" Жанр:';
        //var_info($response);exit();
        if($response)
        {
            $find=false;
            $i=0;
            foreach($response as $row)
            {
                if(isset($row->text))
                {
                    if(count(explode('<br>',$row->text))<8)
                    {

                        $find=false;

                        if(isset($row->attachments))
                        {
                            foreach($row->attachments as $row3)
                            {
                                if($row3->type=='video')
                                {
                                    //echo $row->text.'===='.strlen($row->text).'===='.count(explode('<br>',$row->text)).'<br><br>';

                                    $res_video = $this->api('video.get',array(
                                        'owner_id'=>$row3->video->owner_id,
                                        'videos'=>$row3->video->owner_id.'_'.$row3->video->vid,
                                        'count'=>1,
                                        'extended'=>1,
                                    ));
                                    if(is_array($res_video)&&isset($res_video[1]->vid))
                                    {
                                        $find=true;
                                    }
                                    break;
                                }
                            }
                        }
                        if($find)break;
                    }
                    //echo  $i.'='.$row->text.'<br><br><br>';
                    $i++;
                }
            }

            if($find)
            {
                //if($i>0)$i=$i-1;echo $i;

                $response = $this->api('wall.search',array(
                    'owner_id'=>'-'.$publicID,
                    'query'=>'"'.$name.'" Жанр:',
                    'offset'=>$i,
                    'count'=>1,
                ));
                //var_info($response);

                if(isset($response[1]->text))
                {
                    $date = explode('-', $date);
                    $response[1]->copy_text=$date[2].',к';echo'ads';
                    if($this->repost($this->db, '', 3, $response))return true;
                }
            }
        }
        return false;
    }

    function searchPost2($publicID, $numb=0)
    {
        $MostFilm = new MostFilm($this->db);
        $offset = rand(10, 9818);
        $response = $this->api('wall.search',array(
            'owner_id'=>'-'.$publicID,
            'query'=>') Жанр:',
            'count'=>10,
            'offset'=>$offset,
        ));
        //var_info($response);
        if($response)
        {
            $find=false;
            $i=0;
            foreach($response as $row)
            {
                if(isset($row->text))
                {
                    $name = explode('<br>',$row->text);

                    $arr = array('[', ']',
                        'документальный','трейлер',
                        'ужасы','короткометражка');
                    $str = mb_strtolower($row->text, 'UTF-8');

                    $upload = false;
                    foreach($arr as $row3)
                    {
                        $pos = strripos($str, $row3);
                        if ($pos === false)
                        {
                            $upload = true;
                        }
                        else{
                            $upload = false;
                            break;
                        }
                    }

                    if(count($name)<8&&$upload)
                    {

                        $find=false;

                        //echo'222'.$str.'='.$row.'<br>';
                        if(isset($row->attachments))
                        {
                            foreach($row->attachments as $row3)
                            {
                                if($row3->type=='video')
                                {
                                    //echo $row->text.'===='.strlen($row->text).'===='.count(explode('<br>',$row->text)).'<br><br>';

                                    sleep(2);
                                    $res_video = $this->api('video.get',array(
                                        'owner_id'=>$row3->video->owner_id,
                                        'videos'=>$row3->video->owner_id.'_'.$row3->video->vid,
                                        'count'=>1,
                                        'extended'=>1,
                                    ));
                                    if(is_array($res_video)&&isset($res_video[1]->vid))
                                    {
                                        $find=true;
                                    }

                                    break;
                                }
                            }
                        }
                        if($find)break;
                    }
                    //echo  $i.'='.$row->text.'<br><br><br>';
                    $i++;

                }
            }

            if(isset($name[0])&&$name[0]!='')
            {
                $row2 = $this->db->row("SELECT id FROM posts WHERE public_id='3' AND name LIKE '%{$name[0]}%'");
                if($row2)$find = false;
            }

            $rating=10;
            if($find&&$name[0]!='')
            {
                //include $this->path_img."soc/protected/classes/MostFilm.php";
                $rating = $MostFilm->getRating($name[0]);
            }

            if($find&&$rating>=6)
            {
                echo'<br>'.$name[0].'=='.$rating.'<br>';
                //if($i>0)$i=$i-1;echo $i;
                //var_info($response);
                sleep(2);
                $response = $this->api('wall.search',array(
                    'owner_id'=>'-'.$publicID,
                    'query'=>') Жанр:',
                    'offset'=>$offset+$i,
                    'count'=>1,
                ));
                //var_info($response);
                var_info($response);
                if(isset($response[1]->text))
                {
                    $response[1]->copy_text=date("d").',к';
                    sleep(2);

                    if($this->repost($this->db, '', 3, $response))return true;
                }

            }
            else{echo'noe';

                if($numb>10)return false;
                $numb++;
                $this->searchPost2($publicID, $numb);
            }
        }
        return false;
    }


    function inviteGroup($publicID, $offset)
    {

        $response = $this->api('friends.get',array(
            'count'=>2,
            'offset'=>$offset,
        ));
        //var_info($response);
        foreach($response as $row)
        {
            $response = $this->api('groups.invite',array(
                'group_id'=>$publicID,
                'user_id'=>$row,
            ));var_info($response);
        }


        //
        return false;
    }


    public function repost_mm($db, $publicId, $id)
    {
        $offset = rand(10, 13506);
        $response = $this->api('wall.get',array(
            'owner_id'=>'-'.$publicId,
            'count'=>1,
            'offset'=>$offset,
        ));

        $upload = false;
        if(is_array($response)&&isset($response[1],$response[1]->text)&&$response[1]->text!=''&&strlen($response[1]->text)<60)
        {
            //var_info($response);
            $i=0;
            foreach($response[1]->attachments as $row3)
            {
                if($row3->type=='audio')
                {
                   $i++;
                }
            }

            $response[1]->text = trim($response[1]->text);
            $row = $db->row("SELECT id FROM posts WHERE name=?", array($response[1]->text));
            if(!$row&&$i>4)
            {

                $arr = array('[', ']',
                    'нов',
                    'свеж',
                    'премьер', 'http',
                    'сегодня', 'завтра',
                    'вчера', 'понедель',
                    'вторни', 'сред',
                    'четвер', 'пятни',
                    'суббот', 'воскрес',
                    'утр', 'вечер',
                    'скоро', 'зим',
                    'весн', 'лет', 'осен',
                    'начал', 'коне',);
                $str = mb_strtolower($response[1]->text, 'UTF-8');
                foreach($arr as $row)
                {
                    $pos = strripos($str, $row);
                    if ($pos === false)
                    {
                        $upload = true;
                    }
                    else{
                        $upload = false;
                        break;
                    }
                }
            }
            else{
                $upload = false;
            }
        }
        else{
            $upload = false;
        }

        if ($upload)
        {
            /*echo $response[1]->text;
            echo $i.'<br />';
            exit();*/
            $this->repost($db, $publicId, $id, $response);
        }
        else{
            $this->repost_mm($db, $publicId, $id);
        }

    }

    public function repost_bon($db, $publicId, $id)
    {
        $offset = rand(10, 13506);
        $response = $this->api('wall.get',array(
            'owner_id'=>'-'.$publicId,
            'count'=>1,
            'offset'=>$offset,
        ));

        $upload = false;
        if(isset($response[1],$response[1]->text)&&$response[1]->text!=''&&strlen($response[1]->text)<60)
        {
            //var_info($response);

            $row = $db->row("SELECT id FROM posts WHERE public_id='1' AND name LIKE '%".$response[1]->text."%'");
            if(!$row)
            {

                $arr = array('[', ']','http');
                $str = mb_strtolower($response[1]->text, 'UTF-8');
                foreach($arr as $row)
                {
                    $pos = strripos($str, $row);
                    if ($pos === false)
                    {
                        $upload = true;
                    }
                    else{
                        $upload = false;
                        break;
                    }
                }
            }
            else{
                $upload = false;
            }
        }
        else{
            $upload = false;
        }

        if ($upload)
        {
            /*echo $response[1]->text;
            echo $i.'<br />';
            exit();*/
            if(isset($response[1]->text)&&$response[1]->text!=''&&strlen($response[1]->text)<60)
            {
                $this->repost($db, $publicId, $id, $response);
            }
        }
        else{
            $this->repost_bon($db, $publicId, $id);
        }

    }

    function getMessage()
    {
        include $this->path_img."soc/protected/classes/Messages.php";
        $message = new Messages($this->db);
        $response = $this->api('messages.get',array(
            'preview_length'=>0,
            'count'=>20,

        ));

        foreach($response as $row)
        {
            if($row->body)//isset($row->read_state)&&$row->read_state==0
            {
                if($str = $message->getMessage($row->body))
                {
                    /*$response2 = $this->api('messages.send',array(
                        'user_id'=>$row->uid,
                        'message'=>$str,
                    ));*/
                    var_info($response2);
                    //sleep(2);
                }
            }
        }
    }

    function getMessage2()
    {
        include $this->path_img."soc/protected/classes/Messages.php";
        $message = new Messages($this->db);
        $response = $this->api('messages.getDialogs',array(
            'count'=>20,
        ));


        if(is_array($response))
        foreach($response as $row)
        {
            if(isset($row->uid)&&!isset($row->chat_id))
            {
                //var_info($row);

                $dialog_id = $message->addDialog($row);

                $response2 = $this->api('messages.getHistory',array(
                    'user_id'=>$row->uid,
                ));

                $message->addMessages($dialog_id, $response);
                sleep(2);
                $response2 = $this->api('messages.markAsRead',array(
                    'peer_id'=>$row->uid,
                ));
                sleep(2);
            }
        }
    }
}
