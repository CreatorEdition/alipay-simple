<?php
/**
 * 支付宝 PC 交易订单查询
 * 单文件60秒即可跑通支付宝支付
 * @author https://github.com/louaolin/alipay-sdk
 */
header('Content-type:text/html; Charset=utf-8');

/*** API 基本信息配置 ***/
//应用ID,您的APPID。  https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$appid = '';

//商户私钥  填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
$rsaPrivateKey='';

//支付宝网关 沙箱：openapi.alipaydev.com
$gatewayUrl='https://openapi.alipay.com/gateway.do';

//签名方式 支持RSA2和RSA，推荐使用RSA2
$signType = 'RSA2';
/*** API 基本信息 结束 ***/

/*** 订单信息 ***/
//商户订单号，商户网站订单系统中唯一订单号
$outBizBo = '';
//支付宝交易号
$orderId = '';
//商户订单号与支付宝交易号二选一
/*** 订单信息 结束 ***/


$aliPay = new AlipayService($appid,$gatewayUrl,$rsaPrivateKey);
$result = $aliPay->doQuery($outBizBo,$orderId);
$result = $result['alipay_trade_query_response'];

if($result['code'] && $result['code']=='10000'){
    //——注意这里的 Code=10000 只是判断调用成功了，并非支付成功——
    if($result["trade_status"]=="TRADE_SUCCESS"){
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
        echo '<h1>该笔交易支付成功</h1>';
        //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    }else{
        //——其他订单状态 见文档 触发通知类型部分 ——
        echo '<h1>该笔交易尚未支付</h1>';
    }

}else{
    echo $result['msg'].' : '.$result['sub_msg'];
}

class AlipayService
{
    protected $appId;
    //私钥值
    protected $rsaPrivateKey;

    public function __construct($appid, $gatewayUrl, $rsaPrivateKey,)
    {
        $this->appId = $appid;
        $this->gatewayUrl = $gatewayUrl;
        $this->charset = 'utf8';
        $this->rsaPrivateKey=$rsaPrivateKey;
    }

    /**
     * 转帐查询
     * @param string $outBizBo 商户转账唯一订单号（商户转账唯一订单号、支付宝转账单据号 至少填一个）
     * @param string $orderId 支付宝转账单据号（商户转账唯一订单号、支付宝转账单据号 至少填一个）
     * @return array
     */
    public function doQuery($outBizBo='',$orderId='')
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outBizBo,
            'trade_no'=>$orderId,
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.query',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost($this->gatewayUrl,$commonConfigs);
        return json_decode($result,true);
    }

    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA") {
        $priKey=$this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }

    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}