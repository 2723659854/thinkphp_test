<?php
/**
 * Created by PhpStorm.
 * User: 35711
 * Date: 2018/11/7
 * Time: 13:31
 */

namespace app\open\controller;
use think\Controller;
use think\Session;
class Wx extends Controller
{
    //个人账号测试账号
    protected $appId     = "wxbcd8fbc4195b5390";
    protected $appSecret = "7110945d20dfafe7e43232e9a4b71c91";
    //校验
    public function getToken()
    {
        if (!isset($_GET["echostr"])){
            $this->responseMsg();
        }else{
            $this->checkSignature();
        }
    }
    //执行接收器方法
    public function responseMsg()
    {

//        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");//接受数据
        libxml_disable_entity_loader(true);//xml转换为对象


        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            switch($RX_TYPE){
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
            }
            echo $result;
        }else{
            echo "";
            exit;
        }
    }
    private function receiveEvent($postObj){
        $content = "";
        switch ($postObj->Event){
            case "subscribe"://关注事件
                $content = "欢迎关注莘知教育微信公众账号";//这里是向关注者发送的提示信息
                break;
            case "unsubscribe"://取消关注事件
                $content = "你怎么就要离开我了呢？";
                break;
        }
        $result = $this->transmitText($postObj,$content);
        return $result;

    }
    private function transmitText($object,$content){
        $textTpl = "<xml>
                       <ToUserName><![CDATA[%s]]></ToUserName>
                       <FromUserName><![CDATA[%s]]></FromUserName>
                       <CreateTime>%s</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                       <Content><![CDATA[%s]]></Content>
                       <FuncFlag>0</FuncFlag>
                   </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;

    }
    public function checkSignature(){
        //        //获取参数 signature nonce token timestamp echostr
        $nonce      = $_GET['nonce'];
        $token      = 'zhoufei';
        $timestamp  = $_GET['timestamp'];
        $echostr    = $_GET['echostr'];
        $signature  = $_GET['signature'];
        //形成数组 按字典排序
        $arr = [$timestamp, $nonce, $token];
        sort($arr);
        //拼接成字符串 sha1加密 ,然后与signature对比
        $str = sha1(implode($arr));
        if ($str == $signature) {//第一次接入微信 api接口需要对比
            echo $echostr;exit;
        }else{
            return false;
        }
    }

    //菜单添加
    public function defineMenus(){
        $array=[
            'button'=>[
                ['name'=>urlencode('按钮'),'type'=>'view','url'=>'http://sz517.com/mobile/store/index'],
                ['name'=>urlencode('题库'),'type'=>'view','url'=>'http://sz517.com/mobile/questions/lists'],
                ['name'=>urlencode('个人中心'),'type' =>'view', 'url'=>'http://sz517.com/mobile/individual/index',]
            ]
        ];
        //$accessToken = "15_lesbSkBc_9a5Chuk5dWSLOW56vFGJPkVcR2udqjG1P8iwyhdi6X-zbu2mtL5j-EQ6yO-gyE9ye3LBPBqaIeBz6vgfePo8Dq7pkgqSr1XSKwJtnmqL7aYOOSF7_oMDTeAAABSI";//self::getAccessToken($this->appId,$this->appSecret);
        $accessToken = self::getAccessToken($this->appId,$this->appSecret);
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$accessToken;
        $postJson = urldecode(json_encode($array));
        $res = self::getCurl($url,'post','json',$postJson);
        print_r($res);
    }
    private function getCurl($url,$type,$res=false,$arr=false)
    {//1.初始化
        $ch = curl_init();
        //2.设置粗略得参数[默认GET请求]
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($type=='post'){//post请求
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$arr);
        }
        //3.采集url
        $result = curl_exec($ch);
        //4.返回采集结果
        if($res=='json'){
            if (curl_errno($ch)){
                return curl_error($ch);
            }else{
                return json_decode($result,true);
            }
        }
        //5.关闭
//        curl_close($ch);
    }
    public function getAccessToken($appId,$appSecret){
        //将access_token 存入session中
//        $token = Session::get('access_token');
//        $tokenTime = Session::get('token_time');
//        if($token && $tokenTime>time()){
//            return $token;
//        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
//                   "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET"
            $jsonRes = $this->getCurl($url,'get','json');
            $access_token = $jsonRes['access_token'];
            Session::set('access_token',$access_token);
            Session::set('token_time',time()+7000);
            return $access_token;
//        }
    }
}