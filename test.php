<?php

header('Content-Type: text');

require "wechat.base.api.php";

/**
 * 1. 测试wechat.base.api.php中三个函数正确性
 */
$openID      = 'ovbQZ1cz7qdrkOiShmr5SSbBU8k4';
$appID       = 'wxadfce85759f4629d';
$appSecret   = 'e03b11b28d406a42b04f60920ab72c7a';
$accessToken = getAccessToken($appID, $appSecret);
$userInfo    = getUserInfo($openID, $accessToken);
echo $userInfo;
