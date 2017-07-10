<?php
header('Content-type:text');
define('TOKEN', 'weixin');

$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
} else {
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
    public function valid() {
        $echoStr = $_GET['echostr'];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature() {
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 响应消息
     */
    public function responseMsg() {
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];

        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE) {
                case 'event':
                    $result = $this->receiveEvent($postObj);
                    break;
                case 'text':
                    $result = $this->receiveText($postObj);
                    break;
                case 'image':
                    $result = $this->receiveImage($postObj);
                    break;
                case 'location':
                    $result = $this->receiveLocation($postObj);
                    break;
                case 'voice':
                    $result = $this->receiveVoice($postObj);
                    break;
            }

            echo $result;
        } else {
            echo '';
            exit;
        }
    }

    /**
     * 接收事件消息
     * @param $object
     * @return string
     */
    private function receiveEvent($object) {
        $content = '';
        switch ($object->Event) {
            case 'subscribe':
                $content = "欢迎关注测试微信公众号 \n请回复以下关键字：文本 表情 单图文 多图文 音乐\n请按住说话 或 点击 + 再分别发送以下内容：语音 图片 小视频 我的收藏 位置";
                break;
            case 'unsubscribe':
                $content = '取消关注';
                break;
            default:
                $content = 'receive a new event: ' . $object->Event;
                break;
        }
        if (is_array($content)) {
            $result = $this->transmitNews($object, $content);
        } else {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    /**
     * 接收文本消息
     * @param $object
     * @return string
     */
    private function receiveText($object) {
        $keyword = $object->Content;

        if (strstr($keyword, '文本')) {
            $content = '这是个文本消息';
        } elseif (strstr($keyword, '表情')) {
            $content = "微笑：/::)\n乒乓：/:oo\n中国：" . $this->bytes_to_emoji(0x1F1E8) . $this->bytes_to_emoji(0x1F1F3)."\n仙人掌：" . $this->bytes_to_emoji(0x1F335);
        } elseif (strstr($keyword, '单图文')) {
            $content = array();
            $content[] = array('Title' => '单图文标题', 'Description' => '单图文内容', 'PicUrl' => 'http://img.pconline.com.cn/images/upload/upc/tx/photoblog/1110/09/c13/9215281_9215281_1318168925866_mthumb.jpg', 'Url' => 'http://m.cnblogs.com/?u=txw1958');
        } elseif (strstr($keyword, '图文') || strstr($keyword, '多图文')) {
            $content = array();
            $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://img.pconline.com.cn/images/upload/upc/tx/photoblog/1110/09/c13/9215281_9215281_1318168925866_mthumb.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
        } elseif (strstr($keyword, '音乐')) {
            $content = array();
            $content = array('Title' => '最炫民族风', 'Description' => '歌手：凤凰传奇', 'MusicUrl' => 'http://mascot-music.stor.sinaapp.com/zxmzf.mp3', 'HQMusicUrl' => 'http://mascot-music.stor.sinaapp.com/zxmzf.mp3');
        } else {
            $content = date("Y-m-d H:i:s",time()) . "\nOpenID：" . $object->FromUserName . "\n技术支持 ZHU";
        }

        if (is_array($content)) {
            if (isset($content[0])) {
                $result = $this->transmitNews($object, $content);
            } elseif (isset($content['MusicUrl'])) {
                $result = $this->transmitMusic($object, $content);
            }
        } else {
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }

    /**
     * 接收图片消息
     * @param $object
     * @return string
     */
    private function receiveImage($object) {
        $content = array('MediaId' => $object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    /**
     * 接收位置消息
     * @param $object
     * @return string
     */
    private function receiveLocation($object) {
        $content = "你发送的是位置，经度为：" . $object->Location_Y . "；纬度为：" . $object->Location_X . "；缩放级别为：" . $object->Scale . "；位置为：" . $object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /**
     * 接收语音消息
     * @param $object
     * @return string
     */
    private function receiveVoice($object) {
        if (isset($object->Recognition) && !empty($object->Recognition)) {
            $content = "你刚才说的是：" . $object->Recognition;
            $result = $this->transmitText($object, $content);
        } else {
            $content = array('MediaId' => $object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }

        return $result;
    }

    /**
     * 发送文字消息
     * @param $object
     * @param $content
     * @return string
     */
    private function transmitText($object, $content) {
        if (!isset($content) || empty($content)) {
            return '';
        }

        $xmlTpl = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[text]]></MsgType>
                     <Content><![CDATA[%s]]></Content>
                 </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

        return $result;
    }

    /**
     * 回复图文信息
     * @param $object
     * @param $newsArray
     * @return string
     */
    private function transmitNews($object, $newsArray) {
        if (!is_array($newsArray)) {
            return '';
        }

        $itemTpl= "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                  </item>";

        $item_str = '';
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }

        $xmlTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>$item_str</Articles>
                  </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    /**
     * 回复音乐消息
     * @param $object
     * @param $musicArray
     * @return string
     */
    private function transmitMusic($object, $musicArray) {
        if (!is_array($musicArray)) {
            return '';
        }

        $itemTpl = "<Music>
                       <Title><![CDATA[%s]]></Title>
                       <Description><![CDATA[%s]]></Description>
                       <MusicUrl><![CDATA[%s]]></MusicUrl>
                       <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                    </Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[music]]></MsgType>
                    $item_str
                  </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());

        return $result;
    }

    /**
     * 回复图片消息
     * @param $object
     * @param $imageArray
     * @return string
     */
    private function transmitImage($object, $imageArray) {
        $item_Tpl = "<Image>
                      <MediaId><![CDATA[%s]]></MediaId>
                    </Image>";

        $item_str = sprintf($item_Tpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    $item_str
                   </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复语音消息
     * @param $object
     * @param $voiceArray
     * @return string
     */
    private function transmitVoice($object, $voiceArray) {
        $itemTpl  = "<Voice>
                      <MediaId><![CDATA[%s]]></MediaId>
                     </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);

        $xmlTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[voice]]></MsgType>
                    $$item_str
                   </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 比特转emoji字符串
     * @param $cp
     * @return string
     */
    function bytes_to_emoji($cp) {
        if ($cp > 0x10000) {       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x800){   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x80){    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
        } else {                    # 1 byte
            return chr($cp);
        }
    }
}