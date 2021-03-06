<?php
// ====================================================
// FileName: func.class.php
// Summary: 系统函数配置
// ====================================================
if (! defined('CORE'))
    exit("error!");
// 当前时区
date_default_timezone_set('asia/shanghai');

// 初始化数据库连接
$db = new pdo_mysql($Config);

// 安全验证
function smarty_cfg($self)
{
    global $dir;
    $self->setTemplateDir('./tpl/' . $dir . '/');
    $self->setCompileDir('./tmp/compile/' . $dir . '/');
    $self->setCacheDir('./tmp/cache/' . $dir . '/');
    
}

function isLogin()
{
    if ($_SESSION['app_c_id'] < 1) {
        echo "<script>alert(\"您还未登录!\");window.location=\"index.php?action=zygw_index\";</script>";
        exit();
    }
}

function ismobile($mobile)
{
    return (strlen($mobile) == 11 || strlen($mobile) == 12) && (preg_match("/^13\d{9}$/", $mobile) || preg_match("/^14\d{9}$/", $mobile) || preg_match("/^15\d{9}$/", $mobile) || preg_match("/^17\d{9}$/", $mobile) || preg_match("/^18\d{9}$/", $mobile) || preg_match("/^0\d{10}$/", $mobile) || preg_match("/^0\d{11}$/", $mobile));
}

function cha_province($id)
{
    global $db;
    $sql = "select province from rv_province where 1=1 and provinceid=?";
    $db->p_e($sql, array(
        $id
    ));
    $province = $db->fetch_count();
    return $province;
}

function cha_city($id)
{
    global $db;
    $sql = "select city from rv_city where 1=1 and cityid=?";
    $db->p_e($sql, array(
        $id
    ));
    $city = $db->fetch_count();
    return $city;
}

function cha_area($id)
{
    global $db;
    $sql = "select area from rv_area where 1=1 and areaid=?";
    $db->p_e($sql, array(
        $id
    ));
    $area = $db->fetch_count();
    return $area;
}

function cha_dizhi($id)
{
    global $db;
    $sql = "select * from rv_mendian where 1=1 and id=?";
    $db->p_e($sql, array(
        $id
    ));
    $md = $db->fetchRow();
    
    $sql = "select * from rv_fengongsi where 1=1 and id=?";
    $db->p_e($sql, array(
        $md['fid']
    ));
    $fgs = $db->fetchRow();
    if ($fgs) {
        $dizhi = $fgs['name'] . '-' . $md['name'];
    } else {
        $dizhi = $md['name'];
    }
    // $dizhi=cha_province($md['provinceid']).cha_city($md['cityid']).cha_area($md['areaid']).$md['dizhi'].$md['name'];
    return $dizhi;
}

function user($uid)
{
    global $db;
    $sql = "select * from rv_user where 1=1 and id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $user = $db->fetchRow();
    if ($user['roleid'] == 1) {
        $user['stroe_id'] = $user['zz'];
        $user['zz'] = '总部';
    } elseif ($user['roleid'] == 2 || $user['roleid'] == 4) {
        $sql = "select name from rv_fengongsi where 1=1 and id=?";
        $db->p_e($sql, array(
            $user['zz']
        ));
        $user['stroe_id'] = $user['zz'];
        $user['zz'] = $db->fetch_count();
    } elseif ($user['roleid'] == 3 || $user['roleid'] == 5) {
        $sql = "select * from rv_mendian where 1=1 and id=?";
        $db->p_e($sql, array(
            $user['zz']
        ));
        $md = $db->fetchRow();
        $user['stroe_id'] = $user['zz'];
        $user['zz'] = cha_province($md['provinceid']) . cha_city($md['cityid']) . cha_area($md['areaid']) . $md['dizhi'] . $md['name'];
    }
    return $user;
}

function get_time_buy($mid, $start, $end)
{
    global $db;
    $sql="select *,(num* money) as total_price from (select bg.id,bg.goods_id,bg.goods_type,g.name,g.money,g.dw,SUM(count) as num from rv_buy_goods as bg,rv_goods as g  where bg.goods_id=g.id and bg.buy_id in(select id from rv_buy where mid=? and status=1 and UNIX_TIMESTAMP(addtime)  BETWEEN ? AND ? ) and bg.goods_type=0 GROUP BY bg.goods_id ) as b ORDER BY num desc";
    $db->p_e($sql, array(
        $mid,
        $start,
        $end
    ));
    $list = $db->fetchAll();
    return $list;
}
function get_time_buy_asc($mid, $start, $end)
{
    global $db;
    $sql="select *,(num* money) as total_price from (select bg.id,bg.goods_id,bg.goods_type,g.name,g.money,g.dw,SUM(count) as num from rv_buy_goods as bg,rv_goods as g  where bg.goods_id=g.id and bg.buy_id in(select id from rv_buy where mid=? and status=1 and UNIX_TIMESTAMP(addtime)  BETWEEN ? AND ? ) and bg.goods_type=0 GROUP BY bg.goods_id ) as b ORDER BY num asc";
    $db->p_e($sql, array(
        $mid,
        $start,
        $end
    ));
    $list = $db->fetchAll();
    return $list;
}

// 发送短信
function send_sms($phone, $content)
{
    $param = array(
        'u' => 'dyw123',
        'p' => md5('duyiwang123'),
        'm' => $phone,
        'c' => urlencode($content)
    );
    foreach ($param as $key => $val) {
        $param_url .= "$key=$val&";
    }
    $ch = curl_init();
    $url = 'http://www.smsbao.com/sms?' . $param_url;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = json_decode(curl_exec($ch), true);
    if ($res == 0) {
        return true;
    } else {
        return false;
    }
}

// 验证码
function getverifycode()
{
    $length = 6;
    PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
    $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    return $hash;
}

// 推送消息
function to_msg($post_data)
{

    $ch = curl_init('http://127.0.0.1:4002');
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_HEADER => 0,
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_POSTFIELDS => http_build_query($post_data)
    ));
    
    $aa = curl_exec($ch);
   
    curl_close($ch);
    return $aa == 'ok' ? true : false;
}

// 去除重复数据
function unique($data = array())
{
    $tmp = array();
    foreach ($data as $key => $value) {
        // 把一维数组键值与键名组合
        foreach ($value as $key1 => $value1) {
            $value[$key1] = $key1 . '_|_' . $value1; // _|_分隔符复杂点以免冲突
        }
        $tmp[$key] = implode(',|,', $value); // ,|,分隔符复杂点以免冲突
    }
    // 对降维后的数组去重复处理
    $tmp = array_unique($tmp);
    // 重组二维数组
    $newArr = array();
    foreach ($tmp as $k => $tmp_v) {
        $tmp_v2 = explode(',|,', $tmp_v);
        foreach ($tmp_v2 as $k2 => $v2) {
            $v2 = explode('_|_', $v2);
            $tmp_v3[$v2[0]] = $v2[1];
        }
        $newArr[$k] = $tmp_v3;
    }
    return $newArr;
}
?>