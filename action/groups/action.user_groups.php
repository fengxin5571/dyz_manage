<?php
if (! defined('CORE'))
    exit("error!");
$time=time();
// 加入群聊
if ($do == "add_groups") {
    $admin_id = $_REQUEST['admin_id']; // 群主用户id
    $groups_users = json_decode($_REQUEST['groups_users']); // 群内用户id
    if (! empty($admin_id) && ! empty($groups_users) && is_array($groups_users)) {
        $admin_name = $db->select(0, 1, "rv_user", "name", array(
            "id = $admin_id"
        ), "id desc");
        array_unshift($groups_users, array(
            $admin_id,
            $admin_name[name]
        )); // 将自己加入到群组数组中
        $ug_id = $db->insert(0, 2, "rv_users_groups", array(
            "ug_admin_id = $admin_id",
            "ug_create_time1=$time"
        )); // 如果插入成功，则返回群组id
        if ($ug_id) { // 如果创建群聊成功，则往群成员表插入群成员
            $sql = "INSERT INTO rv_group_to_users(gu_gid,gu_uid,gu_group_nick) VALUES";
            $item_list_tmp = '';
            $params = array();
            $groups_users = unique($groups_users); // 去除重复人员
            
            foreach ($groups_users as $value) {
                $item_list_tmp .= $item_list_tmp ? ",(?,?,?)" : "(?,?,?)";
                array_push($params, $ug_id, $value[0], "$value[1]");
            }
            $sql .= $item_list_tmp;
            $db->p_e($sql, $params);
            to_msg(array(
                'type' => 'update_msg',//mani.html socket事件名称
                'cont' => "",//obj
                'to' => $groups_users//群聊的房间号
            )); // 推送消息
            echo '{"code":"200","msg":"创建群聊成功","gid":"' . $ug_id . '"}';
            
            exit();
        } else {
            echo '{"code":"500","msg":"创建群聊有误"}';
        }
    } else {
        echo '{"code":"500","msg":"创建群聊有误"}';
    }
} elseif ($do == "qlxx") { // 获取群聊信息
    $gid = $_REQUEST['gid']; // 群聊id
    if ($gid) {
        $sql = " SELECT * from rv_group_to_users where gu_gid=?";
        $db->p_e($sql, array(
            $gid
        ));
        $groups_users_list = json_encode($db->fetchAll()); // 群聊组员
        $groups_info = $db->select(0, 1, "rv_users_groups", "*,date_format(ug_create_time,'m月d日 H:i') as ug_create_time_format", array(
            "ug_id = $gid"
        ), ' ug_id desc'); // 群聊信息
        foreach($groups_info as &$v){
            $v['ug_create_time_format']=date('m月d日 H:i',$v['ug_create_time1']);
        }
        echo '{"code":"200","gid":"' . $gid . '","groups_info":' . json_encode($groups_info) . ',"groups_users_list":' . $groups_users_list . '}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
} elseif ($do == "qldhxx") { // 打开群聊框信息
    $gid = $_REQUEST['gid']; // 群聊id
    $uid = $_REQUEST['uid'];
    $is_openwin = 0;
    $pagenum=15;
    if ($gid) {

        $sql="delete from rv_groups_msg_details where 1=1 and guid=? and gid=?";
        $db->p_e($sql, array(
            $uid,
            $gid
        ));

        $sql="select name,head_img from rv_user where id=?";
        $db->p_e($sql, array($uid));
        $userinfo=$db->fetchRow();
        if(empty($userinfo['head_img'])){
                $head_img='http://static.duyiwang.cn/tc_log.jpg';
        }
        if ($db->update(0, 1, "rv_user", array(
            "is_openwin=1"
        ), array(
            "id=$uid"
        ))) {
            $is_openwin = 1;
        }
        $sql = " SELECT count(*) from rv_group_to_users where gu_gid=?";
        $db->p_e($sql, array(
            $gid
        ));
        $groups_users_count = $db->fetch_count();
        $groups_info = json_encode($db->select(0, 1, "rv_users_groups", "ug_name,ug_notice,ug_img,ug_notice_time,ug_notice_username", array(
            "ug_id = $gid"
        ), ' ug_id desc')); // 群聊信息
        $sql = "select count(*) from rv_groups_xiaoxi where 1=1 and togid =?";      
        $db->p_e($sql, array(
            $gid
        ));
        $total = $db->fetch_count();
        $total = ceil($total / $pagenum);   
        echo '{"code":"200","gid":"' . $gid . '","groups_info":' . $groups_info . ',"groups_users_count":"' . $groups_users_count . '","is_openwin":"' . $is_openwin . '","total":"' . $total . '","userinfo":'.json_encode($userinfo).'}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
} elseif ($do == 'edit') { // 编辑群聊信息(群名，昵称，公告)
    $gid = $_REQUEST['gid'];
    $flag = $_REQUEST['flag'];
    if ($gid && $flag) {
        if ($flag == "groups_name") { // 修改群名
            if ($db->update(0, 1, "rv_users_groups", array(
                "ug_name='$_REQUEST[groups_name]'"
            ), array(
                "ug_id=$gid"
            ))) {
                echo '{"code":"200","msg":"修改成功","value":"' . $_REQUEST[groups_name] . '"}';
                exit();
            }
        } elseif ($flag == "nick_name") { // 修改昵称
            $uid = $_REQUEST['uid'];
            if ($db->update(0, 1, "rv_group_to_users", array(
                "gu_group_nick='$_REQUEST[nick_name]'"
            ), array(
                "gu_gid =$gid",
                "gu_uid=$uid"
            ))) {
                echo '{"code":"200","msg":"修改成功","value":"' . $_REQUEST[nick_name] . '"}';
                exit();
            }
        }elseif ($flag == "notice") { // 修改公告
            $time=date('Y.m.d H:i',time());
            $uid=$_REQUEST['uid'];
            $sql="select name from rv_user where id=?";
            $db->p_e($sql, array($uid));
            $name=$db->fetchRow()['name'];
            if ($db->update(0, 1, 'rv_users_groups', array(
                "ug_notice='$_REQUEST[notice]'",
                "ug_notice_time='$time'",
                "ug_notice_username='$name'"
            ), array(
                "ug_id=$gid"
            ))) {
                echo '{"code":"200","msg":"修改成功","value":"' . $_REQUEST[notice] . '","name":"' . $name . '","time":"' . $time . '"}';
                exit();
            }
        }elseif($flag=='groups_pic'){//修改群头像
            if($db->update(0, 1, "rv_users_groups", array(
                "ug_img='$_REQUEST[ug_img]'"
            ),array(
                "ug_id=$gid"
            ))){
                echo '{"code":"200","msg":"修改成功","value":"' . $_REQUEST[ug_img] . '"}';
                exit();
            }
        }
    }
    echo '{"code":"500","msg":"修改失败"}';
    exit();
} elseif ($do == "leave_groups") { // 删除or推出群聊
    $gid = $_REQUEST['gid']; // 群聊id
    $uid = $_REQUEST['uid']; // 用户id
    $groups_info = $db->select(0, 1, "rv_users_groups", "*", array(
        "ug_id = $gid"
    ), ' ug_id desc'); // 群聊信息
    if ($groups_info['ug_admin_id'] == $uid) { // 如果是群主删除整个群
        if ($db->delete(0, 1, "rv_users_groups", array(
            "ug_id=$gid"
        ))) {
            echo '{"code":"200","msg":"解散群聊成功"}';
            exit();
        }
    } else { // 不是群主则退出此群聊
        if ($db->delete(0, 1, "rv_group_to_users", array(
            "gu_uid=$uid",
            "gu_gid=$gid"
        ))) {
            echo '{"code":"200","msg":"离开群聊成功"}';
            exit();
        }
    }
    echo '{"code":"500","msg":"操作失败"}';
    exit();
} elseif ($do == "qldhk") { // 群聊对话框
    $gid = $_REQUEST['gid']; // 群聊id
    $uid = $_REQUEST['uid']; // 用户id   
    //分页
    $pagenum = 15;
    $page = $_REQUEST['page'] ?? 1;
    $page = ($page - 1) * $pagenum;
    if ($gid && $uid) {
        // 变已读
        $sql = "select * from rv_groups_xiaoxi where 1=1 and togid =? order by id desc limit " . $page . "," . $pagenum;
        $sql="select *,date_format(addtime,'m月d日 H:i') as addtime1 from rv_groups_xiaoxi where 1=1 and togid =? order by id desc limit " . $page . "," . $pagenum;
        $db->p_e($sql, array(
            $gid
        ));
        $qdh = $db->fetchAll(); 
        foreach($qdh as &$val){
            $val['addtime1']=date('m月d日 H:i',$val['addtime1']);
        }
        $sort = array(
         'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
         'field'     => 'id',       //排序字段
        );
        $arr=array();
        foreach($qdh as $k=>$v){
            foreach($v as $kk=>$vv){
                $arr[$kk][$k]=$vv;
            }
        }
        if($sort['direction']){
            array_multisort($arr[$sort['field']],constant($sort['direction']),$qdh);
        }
        $total = $db->fetch_count();
        $total = ceil($total / $pagenum);
        foreach ($qdh as $key => &$value) {
            $value['from_uid'] == $uid ? $qdh[$key]['type'] = 1 : $qdh[$key]['type'] = 2; // 获取是收消息or发消息
            $qdh[$key]['from'] = user($value['from_uid']); // 获取发消息人
            if(stripos($value['from']['head_img'],"http://")===false && $value['from']['head_img']!=null){
                $value['from']['head_img']="../../image/header_picture/".$value['from']['head_img'];              
            }
        }
        // 模版
        $smt = new smarty();
        smarty_cfg($smt);
        $smt->assign('qdh', $qdh);
        $smt->assign('total', $total);
        $smt->display('qdhk.html');
        exit();
    }
} elseif ($do == "fasixin") { // 发群聊
    $uid = $_REQUEST['uid'];
    $gid = $_REQUEST['gid'];
    $groups_room = $_REQUEST['groups_room'];

    $at_user_ids = $_REQUEST['at_user_ids'];
    $txt = $_REQUEST['txt'];
    $nowtime = date('m月d日 H:i');
    $send_name = $db->select(0, 1, "rv_group_to_users", "gu_group_nick", array(
        "gu_gid=$gid",
        "gu_uid=$uid"
    ), "gu_id desc");
    $sql="select head_img from rv_user where id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $head=$db->fetchRow();
    $head_img=$head['head_img'];
    $last_id=$db->insert(0, 2, "rv_groups_xiaoxi", array(
        "from_uid='$uid'",
        "togid='$gid'",
        "content='$txt'",
        "content_type=0",
        "at_user_ids='$at_user_ids'",
        "addtime1=$time"
    ));
    $cont = array(
        'sj'=>0,
        'lx' => 0,
        'nr' => $txt,
        'time' => date('m月d日 H:i'),
        "from_id" => $uid,
        "send_name" => $send_name[gu_group_nick],
        "at_user_ids" => $at_user_ids,
        "gid" => $gid,
        "head_img"=>$head_img,
        "xid"=>$last_id,
        "groups_room"=>$groups_room
    );
    $cont = json_encode($cont);
    if($last_id){
        to_msg(array(
            'type' => 'sixin_to_groups',//mani.html socket事件名称
            'cont' => $cont,//obj
            'to' => $groups_room//群聊的房间号
        )); // 推送消息
        
        echo '{"code":"200","time":"' . $nowtime . '","send_name":"' . $send_name[gu_group_nick] . '","head_img":"'.$head_img.'","xid":"'.$last_id.'"}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
} elseif ($do == "check_user_groups") { // 查看本用户所在的群组
    $uid = $_REQUEST['uid'];
    $sql = "select gu_gid from rv_group_to_users where gu_uid= ?";
    $db->p_e($sql, array(
        $uid
    ));
    $gids = $db->fetchAll();
    
    if ($gids) {
        echo '{"code":"200","groups_gids":' . json_encode($gids) . '}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
} elseif ($do == "get_at_user_list") { // 获取群内用户
    $gid = $_REQUEST['gid'];
    $uid = $_REQUEST['uid'];
    $arr1=array($gid,$uid);
    if($_REQUEST['name']){
        $search .= " and gu_group_nick like ? ";
        $arr1[]="%".$_REQUEST['name']."%";
    }
    $sql = "select * from  rv_user LEFT JOIN rv_group_to_users on gu_uid=id where gu_gid= ? and gu_uid != ?".$search;
    $db->p_e($sql,$arr1);
    $at_user_list = $db->fetchAll();
    if ($at_user_list) {
        
        echo '{"code":"200","at_user_list":' . json_encode($at_user_list) . '}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
} elseif ($do == "update_groups_user") { // 修改群组成员
    $groups_users = json_decode($_REQUEST['groups_users']); // 群内用户id
    $gid = $_REQUEST['gid']; // 群组id;
    if (! empty($groups_users) && is_array($groups_users) && ! empty($gid)) {
        $sql = "select gu_uid from rv_group_to_users where 1=1 and gu_gid =? ";
        $db->p_e($sql, array(
            $gid
        ));
        $guids = $db->fetchAll();
        foreach ($guids as $key => $value) {
            $guids[$key] = $value[gu_uid];
        }
        foreach ($groups_users as $key => $user) {
            if (in_array($user[0], $guids)) { // 如果已存在，则剔除
                unset($groups_users[$key]);
               // array_splice($groups_users, $key, 1);
            }
            
        }
        if (empty($groups_users)) {
            echo '{"code":"500","msg":"更新联系人成功"}';
            exit();
        }
        $sql = "INSERT INTO rv_group_to_users(gu_gid,gu_uid,gu_group_nick) VALUES";
        $item_list_tmp = '';
        $params = array();
        $groups_users = unique($groups_users); // 去除重复人员
        foreach ($groups_users as $value) {
            $item_list_tmp .= $item_list_tmp ? ",(?,?,?)" : "(?,?,?)";
            array_push($params, $gid, $value[0], "$value[1]");
        }
        $sql .= $item_list_tmp;
        if ($db->p_e($sql, $params)) {
            to_msg(array(
                'type' => 'update_msg',//mani.html socket事件名称
                'cont' => "",//obj
                'to' => $groups_users//群聊的房间号
            )); // 推送消息
            echo '{"code":"200","msg":"更新联系人成功"}';
            exit();
        }
        echo '{"code":"500","msg":"更新联系人有误"}';
        exit();
    } else {
        echo '{"code":"500","msg":"更新联系人有误"}';
        exit();
    }
} else if ($do == "get_user_openwin") { // 获取用户窗口是否打开
    $uid = $_REQUEST[uid];
    $sql = "select is_openwin from rv_user where id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $is_openwin = $db->fetch_count();
    echo '{"is_openwin":"' . $is_openwin . '"}';
    exit();
} else if ($do == "update_openwin") { // 更新用户窗口状态
    $uid = $_POST['uid'];
    $db->update(0, 1, "rv_user", array(
        "is_openwin=0"
    ), array(
        "id=$uid"
    ));
} elseif ($do == "test") {
    $sql = "select id from rv_mendian where status=1";
    $db->p_e($sql, array());
    $mendian = $db->fetchAll();
    foreach ($mendian as $value) {
        $db->insert(0, 1, 'rv_kucun', array(
            "mid=$value[id]",
            "gid=21 ",
            "kucun=100"
        ));
    }
}elseif($do=='del_groups_xiaoxi'){
    $id=$_REQUEST['xid'];
    $uid=$_REQUEST['uid'];
    $gid=$_REQUEST['gid'];
    $groups_room = $_REQUEST['groups_room'];
    $cont=array(
        'sj'=>1,
        'xid'=>$id
    );
    $cont=json_encode($cont);
    //查询群消息表用户id进行比对
    $sql="select from_uid from rv_groups_xiaoxi where id=?";
    $db->p_e($sql, array(
        $id
    ));
    $userid=$db->fetchRow();

    if(!empty($uid) && !empty($gid) && !empty($id) && $uid==$userid['from_uid']){
        $sql="delete from rv_groups_xiaoxi where 1=1 and id=? and from_uid=? and togid=? ";
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
            echo '{"code":"200","msg":"撤回消息成功"}';
            exit();
        }
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='xiaoxi'){
    $gid=$_REQUEST['gid'];
    if($gid){
        //查询该群最后一条消息的时间
        $sql="select addtime1 from rv_groups_xiaoxi where togid=? order by addtime1 desc limit 1";
        $db->p_e($sql, array($gid));
        $addtime=$db->fetch_count();
        $end=$addtime;//最后一条消息时间戳
        $start=($end-3600*72);//三天前时间
        $sql="select a.*,b.name,b.head_img from rv_groups_xiaoxi as a left join rv_user as b on a.from_uid=b.id where togid=? and addtime1 BETWEEN ? AND ?";
        $db->p_e($sql, array($gid,$start,$end));
        $xiaoxi=$db->fetchAll();
        echo '{"code":"200","xiaoxi":'.json_encode($xiaoxi).'}';
        exit();
    }else{
        echo '{"code":"500"}';
        exit();
    }
}

