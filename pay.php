<?php
/**
 * 支付宝 PC 网站支付
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

//付款成功后 异步回调地址
$returnUrl = 'http://www.xxx.com/alipay/return.php';

//付款成功后 异步回调地址
$notifyUrl = 'http://www.xxx.com/alipay/notify.php';

//签名方式 支持RSA2和RSA，推荐使用RSA2
$signType = 'RSA2';

/*** API 基本信息 结束 ***/

/*** 订单信息 ***/
//商户订单号（不可重复，本Demo使用UUID）
$outTradeNo = uniqid();
//付款金额，单位:元
$payAmount = 0.01;
//订单标题
$orderName = '支付测试';
/*** 订单信息 结束 ***/

$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setGateway($gatewayUrl);
$aliPay->setReturnUrl($returnUrl);
$aliPay->setNotifyUrl($notifyUrl);

$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$sHtml = $aliPay->doPay();
echo $sHtml;

class AlipayService
{
    protected $appId;
    protected $rsaPrivateKey;
    protected $gatewayUrl;
    protected $returnUrl;
    protected $notifyUrl;
    protected $charset;
    //私钥值
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;

    public function __construct()
    {
        $this->charset = 'utf8';
    }

    public function setAppid($appid)
    {
        $this->appId = $appid;
    }

    public function setGateway($gatewayUrl)
    {
        $this->gatewayUrl = $gatewayUrl;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function setRsaPrivateKey($saPrivateKey)
    {
        $this->rsaPrivateKey = $saPrivateKey;
    }

    public function setTotalFee($payAmount)
    {
        $this->totalFee = $payAmount;
    }

    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }

    public function setOrderName($orderName)
    {
        $this->orderName = $orderName;
    }

    /**
     * 发起订单
     * @return array
     */
    public function doPay()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            'product_code'=>'FAST_INSTANT_TRADE_PAY',
            'total_amount'=>$this->totalFee, //单位 元
            'subject'=>$this->orderName,  //订单标题
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.page.pay',             //接口名称
            'format' => 'JSON',
            'return_url' => $this->returnUrl,
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $this->notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        return $this->buildRequestForm($commonConfigs);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp) {

        $sHtml = "正在跳转至支付页面...<form id='alipaysubmit' name='alipaysubmit' action='".$this->gatewayUrl."?charset=".$this->charset."' method='POST'>";
        while (list ($key, $val) = $this->fun_adm_each($para_temp)) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'","&apos;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }

    protected function fun_adm_each(&$array)
    {
        $res = array();
        $key = key($array);
        if ($key !== null) {
            next($array);
            $res[1] = $res['value'] = $array[$key];
            $res[0] = $res['key'] = $key;
        } else {
            $res = false;
        }
        return $res;
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
}