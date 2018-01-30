<?php
if (! defined('CORE'))
    exit("error!"); // 检查某常量是否存在。
$time=time();                    // 首页

$base64 = $_POST['path_s'];

$IMG = base64_decode($base64);
$save_url = "http://static.duyiwang.cn/image/";
$dir_name = "E:/apptupian/image/";
/* $save_url = "http://192.168.1.138/apptupian/image/";
$dir_name = "F:/wamp/www/apptupian/image/"; */
$ymd = date("Ymd");
$dir_name .= $ymd . "/";
$save_url .= $ymd . "/";
if (! file_exists($dir_name)) {
    mkdir($dir_name);
}
//缩略图文件名
$new_file_names = date("YmdHis") . '_' . mt_rand(100, 999) . '.jpg';
// 移动缩略图文件
$file_path_s = $dir_name . $new_file_names;
$file_url_s = $save_url . $new_file_names;
file_put_contents('e:/error67.txt', $file_path_s);
$fp=fopen($file_path_s, "w");//打开缩略图文件流
fwrite($fp, $IMG);
fclose($fp);


if(!empty($_POST['path'])){
    $base=$_POST['path'];
    $Y_IMG=base64_decode($base);
    $save_url = "http://static.duyiwang.cn/image/";
    $dir_name = "E:/apptupian/image/";
    $ymd = date("Ymd");
    $dir_name .= $ymd . "/";
    $save_url .= $ymd . "/";
    if (! file_exists($dir_name)) {
        mkdir($dir_name);
    }
    // 原图新文件名
    $new_file_name = date("YmdHis") . '_' . mt_rand(10000, 99999) . '.jpg';
      
    //移动原图
    $file_path=$dir_name . $new_file_name;
    $file_url=$save_url . $new_file_name;
    //file_put_contents($file_path, $Y_IMG);
    $forigin=fopen($file_path, "w");//打开原图文件流
    fwrite($forigin, $Y_IMG);
    fclose($forigin);
}


if ($do == 'upload') {
    echo $file_url;
    exit();
}
if ($do == 'fasixin_img') {
    $uid = $_POST['uid'];
    $toid = $_POST['toid'];
    $img = $file_url_s;
    $y_img=$file_url;
    $nowtime = date('m月d日 H:i');
    //获取第二次传的值判断
    $_REQUEST['pic_path'];
    if(!empty($_REQUEST['pic_path'])){
        $img=$_REQUEST['pic_path'];
        $file_url_s=$_REQUEST['pic_path'];
    }
    $sql="select content_s_img from rv_xiaoxi where 1=1 and uid=? order by addtime desc limit 1";
    $db->p_e($sql,array($uid));
    $s_img=$db->fetchRow();
    $s_img=$s_img['content_s_img'];
  
    $cont = array(
        'lx' => 1,
        'nr' => $file_url_s,
        'thumb_pic'=> $file_url,
        'time' => date('m月d日 H:i')
    );
    $cont = json_encode($cont);
     if($img==$s_img){
        if($db->update(0, 1, "rv_xiaoxi", array(
            "content='$y_img'"
        ),array(
            "content_s_img='$img'"
        ))){
             to_msg(array(
                'type' => 'sixin_to_groups',
                'cont' => $cont,
                'to' => $groups_room
            )); // 推送消息
        }
    }else{
        $sql = "insert into rv_xiaoxi(uid,toid,content_s_img,content_type,type,status,is_du) values(?,?,?,1,1,1,1)";
        if ($db->p_e($sql, array(
            $uid,
            $toid,
            $img
        ))) {
            $sql = "insert into rv_xiaoxi(uid,toid,content_s_img,content_type,type,status,is_du) values(?,?,?,1,2,1,0)";
            if ($db->p_e($sql, array(
                $toid,
                $uid,
                $img
            ))) {
                to_msg(array(
                    'type' => 'sixin_to',
                    'cont' => $cont,
                    'to' => $toid
                ));
                echo '{"code":"200","url":"' . $file_url_s . '","time":"' . $nowtime . '"}';
            } else {
                echo '{"code":"404"}';
            }
        } else {
            echo '{"code":"404"}';
        } 
    }
  
    exit();
} elseif ($do == "fasixin_img_ql") {
    $uid = $_REQUEST['uid'];
    $gid = $_REQUEST['gid'];
    $_REQUEST['pic_path'];
    $groups_room = $_REQUEST['groups_room'];
    $img = $file_url_s;//缩略图
    $y_img=$file_url;//原图地址
    if(!file_exists($file_url)){
        $file_url=$_REQUEST['pic_path'];
    }

    $nowtime = date('m月d日 H:i');
    //获取值进行判断
    if(!empty($_REQUEST['pic_path'])){
        $img=$_REQUEST['pic_path'];
        $file_url_s=$_REQUEST['pic_path'];
    }

    $sql="select content_s_img from rv_groups_xiaoxi where 1=1 and from_uid=? order by addtime1 desc limit 1";
    $db->p_e($sql,array($uid));
    $s_img=$db->fetchRow();
    $s_img=$s_img['content_s_img'];
    $send_name = $db->select(0, 1, "rv_group_to_users", "gu_group_nick", array(
        "gu_gid=$gid",
        "gu_uid=$uid"
    ), "gu_id desc");
    $sql="select head_img from rv_user where 1=1 and id=?";
    $db->p_e($sql,array(
        $uid
    ));
    $head=$db->fetchRow();
    $head_img=$head['head_img'];
    $cont = array(
        'lx' => 1,
        "sj" => 0,//事件添加 1
        'nr' => $y_img,
        'thumb_pic'=>$img,
        'time' => date('m月d日 H:i'),
        "from_id" => $uid,      
        "send_name" => $send_name[gu_group_nick],
        "head_img"=>$head_img,
        "gid"=>$gid,
        "groups_room"=>$groups_room
    );
    $cont = json_encode($cont);

    if($img==$s_img){
        if($db->update(0, 1, "rv_groups_xiaoxi", array(
            "content='$y_img'"
        ),array(
            "content_s_img='$img'"
        ))){
             to_msg(array(
                'type' => 'sixin_to_groups',
                'cont' => $cont,
                'to' => $groups_room            
            )); // 推送消息
             exit();
        }
    }else{
        $sql = "insert into rv_groups_xiaoxi (from_uid,togid,content_s_img,content_type,addtime1) values(?,?,?,1,$time)";
        if ($db->p_e($sql, array(
            $uid,
            $gid,
            $img
        ))) { // 成功后像socket 服务端推送数据
            // to_msg(array(
            //     'type' => 'sixin_to_groups',
            //     'cont' => $cont,
            //     'to' => $groups_room
            // )); // 推送消息
            
            echo '{"code":"200","url":"' . $file_url_s . '","time":"' . $nowtime . '","send_name":"' . $send_name[gu_group_nick] . '","head_img":"'.$head_img.'"}';
            exit();
        }
     }
    echo '{"code":"500"}';
    exit();
}

?>
