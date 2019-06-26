<?php

header('Content-Type: text');

// 测试账号id和secret
define(APP_ID, 'wxadfce85759f4629d');
define(APP_SECRET, 'e03b11b28d406a42b04f60920ab72c7a');
define(TOKEN, 'weixin');

require "wechat.base.api.php";

// 1.实例化对象; obj公认object缩写; API: Application Program Interface缩写
$weChatObj = new WechatAPI();
// 2.调用方法; msg是公认message缩写
if (isset($_GET['echostr'])) {
    $weChatObj->validMsg();
} else {
    // 3.调用方法;
    $weChatObj->responseMsg();
}

class WechatAPI
{
    /**
     * 验证消息的确来自于微信服务器
     *
     * @return String echostr参数值(校验成功)
     */
    public function validMsg()
    {
        $echostr = $_GET['echostr'];
        if ($this->isCheckSignature()) {
            echo $echostr;
            exit;
        }
    }

    /**
     * 生成加密字符串, 和signature比较, 返回比较结果
     *
     * @return boolean 校验成功, 返回true; 否则返回false
     */
    private function isCheckSignature()
    {
        // 1.获取三个参数(signature, nonce, timestamp)
        $signature = $_GET['signature'];
        $nonce     = $_GET['nonce'];
        $timestamp = $_GET['timestamp'];
        $token     = TOKEN;

        // 2.生成数组(token, nonce, timestamp), 排序
        $arr = [$token, $timestamp, $nonce];
        sort($arr);

        // 3.三个字符串拼接为一个, sha1加密; tmp是temporary(临时)缩写
        $tmpStr = implode($arr);
        $tmpStr = sha1($tmpStr);

        // 4. 加密字符串和signature字符串比对
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 接收消息, 返回消息XML
     *
     * @return String
     */
    public function responseMsg()
    {
        // 1.接收文本消息XML数据包结构(字符串)
        $xmlStr = file_get_contents('php://input');

        if (!empty($xmlStr)) {
            // 2.解析XML字符串
            $xmlObj = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            // 3.消息类型判断
            switch ($xmlObj->MsgType) {
                case 'text': // 文本消息
                    $result = $this->receiveTextMsg($xmlObj);
                    break;
                case 'image': // 图片消息
                    $result = $this->receiveImageMsg($xmlObj);
                    break;
                case 'event': // 事件消息
                    $result = $this->receiveEventMsg($xmlObj);
                    break;
                default: // 返回a超链接标签
                    $str    = '<a href="http://www.apple.com.cn">点击获取更多详情</a>';
                    $result = $this->transmitTextXML($xmlObj, $str);
                    break;
            }

            echo $result;
        }
    }

    /**
     * 接收用户事件消息类型; 六种事件
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function receiveEventMsg($xmlObj)
    {
        switch ($xmlObj->Event) {
            case 'CLICK': // 点击菜单事件
                $result = $this->clickButtonEvent($xmlObj);
                break;
            case 'subscribe': // 关注事件
                $result = $this->subscribeEvent($xmlObj);
                break;
            default: // 剩余没有处理事件
                # code...
                break;
        }
        return $result;
    }

    /**
     * 用户关注公众号, 返回定制化欢迎文本消息
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function subscribeEvent($xmlObj)
    {
        $accessToken = getAccessToken(APP_ID, APP_SECRET);
        $userInfo    = getUserInfo($xmlObj->FromUserName, $accessToken);
        $result      = $this->transmitTextXML($xmlObj, $userInfo);

        return $result;
    }

    /**
     * 处理点击自定义菜单按钮逻辑
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function clickButtonEvent($xmlObj)
    {
        // 判断点击哪个按钮
        switch ($xmlObj->EventKey) {
            case 'V1002': // 创建自定义菜单JSON设置; 宅急送
                // TODO: 数据来自MySQL数据库服务器
                $newsArr = [
                    ['Title' => '华为荣耀P30', 'Description' => '四个前置摄像头, 5000万', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/596c7157N852de046.jpg', 'Url' => 'http://m.dianping.com'],
                    ['Title' => 'MINILA Filco键盘', 'Description' => '88键, 蓝牙...', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/CW-t-fypceiq6378139.jpg', 'Url' => 'http://www.apple.com.cn'],
                    ['Title' => '小米 Max9', 'Description' => '全屏指纹....', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/5959f2beNbb7c699b.jpg', 'Url' => 'http://m.jd.com'],
                ];
                $result = $this->transmitNewsXML($xmlObj, $newsArr);
                break;
            default: // 剩余所有click类型按钮
                $str    = '<a href="http://www.apple.com.cn">点击其他Click类型按钮</a>';
                $result = $this->transmitTextXML($xmlObj, $str);
                break;
        }
        return $result;
    }

    /**
     * 给定二维数组, 返回对应图文消息XML字符串
     *
     * @param  SimpleXMLElement $xmlObj
     * @param  Array $newsArr
     *
     * @return String
     */
    private function transmitNewsXML($xmlObj, $newsArr)
    {
        if (!is_array($newsArr)) {
            return;
        }

        // 1.item; $item关联数组
        $tmpStr = '<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>';
        $itemStr = '';
        foreach ($newsArr as $item) {
            $itemStr .= sprintf($tmpStr, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        // 2.剩下
        $leftStr = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>$itemStr</Articles>
</xml>";
        // 3.整合
        $resultStr = sprintf($leftStr, $xmlObj->FromUserName, $xmlObj->ToUserName, time(), count($newsArr));

        // 4.返回
        return $resultStr;
    }

    /**
     * 接收文本消息, 返回XML字符串
     *
     * @return String
     */
    private function receiveTextMsg($xmlObj)
    {
        // 1.读取用户发送消息内容(Content标签内容)
        $content = '你发送的是文本消息, 输入消息内容:' . trim($xmlObj->Content);
        // 2.拼接XML
        $result = $this->transmitTextXML($xmlObj, $content);

        // 3.返回XML
        return $result;
    }
    /**
     * 接收图片消息, 返回XML字符串
     *
     * @return String
     */
    private function receiveImageMsg($xmlObj)
    {
        // 1.读取用户发送消息内容(Content标签内容)
        $content = '你发送的是图片消息, 图片URL:' . trim($xmlObj->PicUrl);
        // 2.拼接XML
        $result = $this->transmitTextXML($xmlObj, $content);

        // 3.返回XML
        return $result;
    }

    /**
     * 拼接文本消息XML字符串
     *
     * @return String
     */
    private function transmitTextXML($xmlObj, $content)
    {
        // 1.拼接文本消息XML数据包结构(字符串)
        $tmpStr = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
        $resultStr = sprintf($tmpStr, $xmlObj->FromUserName, $xmlObj->ToUserName, time(), $content);

        // 2.return
        return $resultStr;
    }
}

/**
 * 1.用户发送文本消息XML数据包结构(流程图红色)
ToUserName: 接收方微信号(公众号)
FromUserName: 发送方微信号(用户微信号转换/OpenID)
CreateTime: 发送消息时间戳
MsgType: 消息类型; text关键词表示发送文本消息类型
Content: 用户发送消息内容
MsgId: 用户发送消息ID标识
<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[fromUser]]></FromUserName>
<CreateTime>1348831860</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[this is a test]]></Content>
<MsgId>1234567890123456</MsgId>
</xml>

 * 2. 返回文本消息XML数据包结构(字符串)(流程图紫色)
ToUserName: 发送方微信号(用户微信号转换/OpenID)
FromUserName:接收方微信号(公众号)
CreateTime: 返回消息时间戳
MsgType: 返回消息类型; text关键词表示文本消息类型
Content: 返回消息内容
<xml>
<ToUserName><![CDATA[???]]></ToUserName>
<FromUserName><![CDATA[???]]></FromUserName>
<CreateTime>???</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[???]]></Content>
</xml>

3. 用户发送图片消息XML数据包结构
ToUserName: 接收方微信号(公众号)
FromUserName: 发送方微信号(用户微信号转换/OpenID)
CreateTime: 发送消息时间戳
MsgType: 消息类型; image关键词表示发送图片消息类型
PicUrl: 用户发送图片URL地址
MediaId: 多媒体ID标识
MsgId: 消息ID标识
<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[fromUser]]></FromUserName>
<CreateTime>1348831860</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<PicUrl><![CDATA[this is a url]]></PicUrl>
<MediaId><![CDATA[media_id]]></MediaId>
<MsgId>1234567890123456</MsgId>
</xml>

4. 图文消息XML字符串
<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[fromUser]]></FromUserName>
<CreateTime>12345678</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>1</ArticleCount>
<Articles>
<item>
<Title><![CDATA[title1]]></Title>
<Description><![CDATA[description1]]></Description>
<PicUrl><![CDATA[picurl]]></PicUrl>
<Url><![CDATA[url]]></Url>
</item>
</Articles>
</xml>

工作代码提交流程: 需求(老大分配) ==> 代码分析/实现/测试(自己电脑) <==> 自动测试(shell脚本文件自动测试: 语法/代码规范) <==> 人工review(老大: 算法复杂度/接口/抽象类......) ==> git服务器(包含所有开发人员代码)
 */
