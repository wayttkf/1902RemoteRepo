<?php

header('Content-Type: text');

/**
 * 给定用户openid和访问令牌, 获取/返回该用户信息
 *
 * @return String
 */
function getUserInfo($openID, $accessToken)
{
    // 1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $accessToken . '&openid=' . $openID . '&lang=zh_CN';

    // 2.发送GET请求
    $jsonArr = sendHTTPRequest($url);

    // 2.解析读取需要字段
    $nickName = '您好,' . $jsonArr['nickname'];
    /*
    $tmpArr = ['未知', '男', '女'];
    $sexStr = '性别'.$tmpArr[$jsonArr['sex']];*/
    $sexStr    = '性别:' . (($jsonArr['sex'] == 1) ? '男' : (($jsonArr['sex'] == 2) ? '女' : '未知'));
    $regionStr = '地区:' . $jsonArr['country'] . ' ' . $jsonArr['province'] . ' ' . $jsonArr['city'];
    $lanStr    = '语言:' . (($jsonArr['language'] == 'zh_CN') ? '简体中文' : '其他');
    $timeStr   = '关注:' . date('Y年m月d日', $jsonArr['subscribe_time']);

    // 3.拼接五行文本”\n”
    $userInfo = $nickName . "\n" . $sexStr . "\n" . $regionStr . "\n" . $lanStr . "\n" . $timeStr;

    return $userInfo;
}

/**
 * 给定开发者ID和开发者密码, 获取access_token字符串
 *
 * @param  String $appID
 * @param  String $appSecret
 *
 * @return String
 *
 */
function getAccessToken($appID, $appSecret)
{
    // 1.url地址
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appID . '&secret=' . $appSecret;

    // 2.发送GET请求
    $jsonArr = sendHTTPRequest($url);

    // 3.解析读取access_token
    $accessToken = $jsonArr['access_token'];

    return $accessToken;
}

/**
 * 给定URL地址, 发送GET请求, 返回JSON关联数组
 *
 * @param  String $url
 *
 * @return Array
 */
function sendHTTPRequest($url)
{
    // 1 初始化对象
    $ch = curl_init();
    // 2 设置选项: URL, 默认就是GET请求
    curl_setopt($ch, CURLOPT_URL, $url);
    // curl_exec()获取的信息以字符串返回
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 3 执行请求; 接收返回JSON
    $result = curl_exec($ch);
    // 4 关闭会话(释放内存)
    curl_close($ch);
    // 5 解析
    $jsonArr = json_decode($result, true);

    return $jsonArr;
}
