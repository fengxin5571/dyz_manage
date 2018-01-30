<?php 
if (! defined('CORE'))
    exit("error!"); // 检查某常量是否存在。

    $time=time();
  /*   $dir_name = "F:wamp/www/apptupian/amr/";
    $save_url = "http://192.168.1.143/apptupian/amr/"; */
    $save_url = "http://static.duyiwang.cn/amr/";
    $dir_name = "E:/apptupian/amr/";
    $upload_flag=false;
    $file_url="";
    if($_FILES['file']['size']>5242880){
        echo '{"code":"404","msg":"文件过大"}';
        exit();
    }
    if(is_uploaded_file($_FILES['file']['tmp_name'])){
        // 新文件名
        //file_put_contents("e://houzhui.txt",substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1));
        $suffix=substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1);
        $new_file_name = date("YmdHis") . '_' . mt_rand(10000, 99999) . '.'.$suffix;
        $file_url = $save_url . $new_file_name;
        $file_path = $dir_name . $new_file_name;
        if(move_uploaded_file($_FILES['file']['tmp_name'], $file_path)){
            $upload_flag=true;
        }
    }
   
if($do == "send_voice"){//发送语音（单聊）
    $uid = $_POST['uid'];
    $toid = $_POST['toid'];
    $amr = $file_url;
    $nowtime = date('m月d日 H:i');
    $local_url=$_REQUEST["local_url"];
    $time_length=$_REQUEST['time_length'];
    $send_length=$_REQUEST['send_length'];
    $sql="select head_img from rv_user where 1=1 and id=?";
    $db->p_e($sql,array(
        $toid
    ));
    $head=$db->fetchRow();
    $head_img=$head['head_img'];
    $cont = array('lx' =>2,'nr' => $file_url, 'time' => date('m月d日 H:i'), 'time_length'=>$time_length,'send_length'=>$send_length,"head_img"=>$head_img);
    $cont = json_encode($cont);
    $sql = "insert into rv_xiaoxi(uid,toid,content,local_url,time_length,send_length,content_type,type,status,is_du) values(?,?,?,?,?,?,2,1,1,1)";
    if ($db->p_e($sql, array( $uid,$toid,$amr,$local_url,$time_length,$send_length))) {
        $sql = "insert into rv_xiaoxi(uid,toid,content,local_url,time_length,send_length,content_type,type,status,is_du) values(?,?,?,?,?,?,2,2,1,0)";
        if ($db->p_e($sql, array($toid,$uid,$amr,$local_url,$time_length,$send_length))) {
            to_msg(array('type' => 'sixin_to','cont' => $cont,'to' => $toid));
            echo '{"code":"200","url":"' . $file_url . '","time":"' . $nowtime . '"}';
        } else {
            echo '{"code":"404"}';
        }
    } else {
        echo '{"code":"404"}';
    }
    exit();
}elseif ($do == "send_voice_groups"){//发送语音群聊
    if($upload_flag){
        $uid = $_REQUEST['uid'];
        $gid = $_REQUEST['gid'];
        $groups_room = $_REQUEST['groups_room'];
        $amr = $file_url;
        $nowtime = date('m月d日 H:i');
        $local_url=$_REQUEST["local_url"];
        $time_length=$_REQUEST['time_length'];
        $send_length=$_REQUEST['send_length'];
        $send_name = $db->select(0, 1, "rv_user", "name", array(
            "id=$uid"
           
        ), "id desc");
        $sql="select head_img from rv_user where 1=1 and id=?";
        $db->p_e($sql,array(
            $uid
        ));
        $head=$db->fetchRow();
        $head_img=$head['head_img'];
        $last_id=$db->insert(0, 2, "rv_groups_xiaoxi", array(
            "from_uid='$uid'",
            "togid='$gid'",
            "content='$amr'",
            "local_url='$local_url'",
            "time_length='$time_length'",
            "send_length='$send_length'",
            "content_type=2",
            "addtime1=$time"
        ));
        $cont = array(
            'sj'=>0,//语音添加事件 0
            'lx' =>2,
            'nr' => $file_url,
            'time' => date('m月d日 H:i'),
            "from_id" => $uid,
            "send_name" =>$send_name[name],
            'time_length'=>$time_length,
            'send_length'=>$send_length,
            "head_img"=>$head_img,
            'xid'=>$last_id,
            'gid'=>$gid,
            "groups_room"=>$groups_room
        );
        
        $cont = json_encode($cont);
        if($last_id){
            to_msg(array('type' => 'sixin_to_groups','cont' => $cont,'to' => $groups_room)); // 推送消息
            
            echo '{"code":"200","url":"' . $file_url . '","time":"' . $nowtime . '","send_name":"' . $send_name[name] . '","head_img":"'.$head_img.'","xid":"'.$last_id.'"}';
            exit();
        } 
    }
    echo '{"code":"500"}';
    exit();
}elseif($do=='del_groups_amr'){
    $uid=$_REQUEST['uid'];
    $gid=$_REQUEST['gid'];
    $id=$_REQUEST['xid'];
    $groups_room = $_REQUEST['groups_room'];
    $cont=array(
        'sj'=>1,//语音删除事件1
        'xid'=>$id
    );
    $cont=json_encode($cont);
    //查询群消息表用户id进行比对
    $sql="select * from rv_groups_xiaoxi where id=?";
    $db->p_e($sql, array(
        $id
    ));
    $userid=$db->fetchRow();
    if(!empty($uid) && !empty($id) && !empty($gid) && $uid==$userid['from_uid']){
        $arr=explode("/", $userid['content']);
        $amr_url=end($arr);
        if(unlink('../apptupian/amr/'.$amr_url)){
            $sql="delete from rv_groups_xiaoxi where 1=1 and id=? and from_uid=? and togid=?";
            if($db->p_e($sql, array(
                $id,
                $uid,
                $gid
            ))){
                to_msg(array(
                    'type'=>'sixin_to_groups',
                    'cont'=>$cont,
                    'to'=>$groups_room
                ));
                echo '{"code":"200","msg":"语音撤回成功"}';
                exit();
            }
        }
    }
    echo '{"code":"500","msg":"关键数据缺失"}';
    exit();
}

?>