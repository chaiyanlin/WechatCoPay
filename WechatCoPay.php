<?php

/**
 * 微信企业支付类
 * 付款/查询
 * 腾讯企业支付文档：https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_1
 * 此Demo可被直接使用，主要目的是集成必要的底层函数实现，便于开发者根据自己特定的需求重构；
 * @author Yanlin Chai
 */
class WechatCoPay {

	/**
	 * post参数
	 * @var array
	 */
	private $values = array();


	/**
	 * 绑定支付的APPID
	 * @var string
	 */
	private $APPID = null;


	/**
	 * 商户号
	 * @var string
	 */
	private $MCHID = null;


	/**
	 * 商户支付密钥
	 * @var string
	 */
	private $KEY = null;

	//商户证书路径
	private $SSLCERT_PATH = null;
	private $SSLKEY_PATH = null;


	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	private $CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	private $CURL_PROXY_PORT = 0;//8080;

	/**
	 * @param string $APPID     		绑定支付的APPID
	 * @param string $MCHID     		商户号
	 * @param string $KEY       		商户支付密钥
	 * @param string $SSLCERT_PATH
	 * @param string $SSLKEY_PATH
	 */
	public function __construct($APPID, $MCHID, $KEY, $SSLCERT_PATH = null, $SSLKEY_PATH = null) {
		$this->APPID        = $APPID;
		$this->MCHID        = $MCHID;
		$this->KEY          = $KEY;
		$this->SSLCERT_PATH = $SSLCERT_PATH;
		$this->SSLKEY_PATH  = $SSLKEY_PATH;
	}


	/**
	 * @param mixed $key [description]
	 * @param mixed $val [description]
	 */
	public function setAttribute($key, $val) {
		$this->values[$key] = $val;
	}


	/**
	 * curl代理设置
	 * @param string  $curl_proxy_host 代理机器
	 * @param integer $curl_proxy_port 代理端口
	 */
	public function setCurlProxy( $curl_proxy_host, $curl_proxy_port ) {
		$this->CURL_PROXY_HOST = $curl_proxy_host;
		$this->CURL_PROXY_PORT = $curl_proxy_port;
	}


	/**
	 * 输出xml字符
	 * @throws WxPayException
	**/
	public function ToXml()
	{
		if(!is_array($this->values)
			|| count($this->values) <= 0)
		{
    		throw new \Exception("数组数据异常！", 400);
    	}

    	$xml = "<xml>";
    	foreach ($this->values as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";
        return $xml;
	}

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
	public function FromXml($xml)
	{
		if(!$xml){
			throw new WxPayException("xml数据异常！");
		}
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $this->values;
	}

	/**
	 * 格式化参数格式化成url参数
	 */
	public function ToUrlParams()
	{
		$buff = "";
		foreach ($this->values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}

		$buff = trim($buff, "&");
		return $buff;
	}

	/**
	 * 生成签名
	 * @return 签名，本函数不覆盖sign成员变量
	 */
	public function MakeSign()
	{
		//签名步骤一：按字典序排序参数
		ksort($this->values);
		$string = $this->ToUrlParams();
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".$this->KEY;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

	/**
	 *
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public function getNonceStr($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 *
	 * @param string $xml      需要post的xml数据
	 * @param string $url      url
	 * @param bool   $useCert  是否需要证书，默认需要
	 * @param int    $second   url执行超时时间，默认30s
	 */
	private function postXmlCurl($xml, $url, $useCert = true, $second = 30)
	{
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);

		//如果有配置代理这里就设置代理
		if($this->CURL_PROXY_HOST != "0.0.0.0"
			&& $this->CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, $this->CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, $this->CURL_PROXY_PORT);
		}
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			throw new \Exception("curl出错，错误码:$error", 400);
		}
	}

	public function xml_to_array($xml) {
		$xml   = simplexml_load_string($xml, null, LIBXML_NOCDATA);
		$json  = json_encode($xml);
		$array = json_decode($json,TRUE);
		return $array;
	}


	/**
	 * 企业付款
	 * 成功时的返回格式：
	 * Array
	 * (
	 *    [return_code] = SUCCESS
	 *    [return_msg]  = Array
	 *         (
	 *         )
	 *
	 *     [mch_appid]   = wxc9e5c38ea51wdwdw4
	 *     [mchid]       = 1249048701
	 *     [device_info] = Array
	 *         (
	 *         )
	 *
	 *     [nonce_str]        = e4hxi5u22ak2voyzh4eagtvswg66bx22
	 *     [result_code]      = SUCCESS
	 *     [partner_trade_no] = 10000098201411111234567832 {}
	 *     [payment_no]       = 1000018301201508180611542306
	 *     [payment_time]     = 2015-08-18 17:44:16
	 * )
	 * 错误返回格式:
	 * Array
	 *  (
	 *      [return_code]  = FAIL
	 *      [return_msg]   = 参数错误:openid字段不正确,请检查是否合法
	 *      [result_code]  = FAIL
	 *      [err_code]     = PARAM_ERROR
	 *      [err_code_des] = 参数错误:openid字段不正确,请检查是否合法
	 *  )
	 * @return array
	 */
	public function pay() {
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
		$this->setAttribute('mch_appid', $this->APPID);
		$this->setAttribute('mchid', $this->MCHID);
		$this->setAttribute('nonce_str', $this->getNonceStr());
		$this->setAttribute('spbill_create_ip', $_SERVER['REMOTE_ADDR']);
		$this->setAttribute('sign', $this->MakeSign());
		$xml    = $this->ToXml();
		$result = $this->postXmlCurl($xml, $url);
		return $this->xml_to_array($result);
	}


	/**
	 * 企业付款查询接口
	 * @param  string $partner_trade_no
	 * 成功时返回格式：
	 * Array
     * (
     *     [return_code]      = SUCCESS
     *     [sign]             = 0669D61345F5EE64E5E92F8F317C3098
     *     [result_code]      = SUCCESS
     *     [partner_trade_no] = 10000098201411111234567823
     *     [mch_id]           = 1249048701
     *     [status]           = SUCCESS
     *     [openid]           = &oacGps5SvsILkNOJiF0gefefzKjXI
     *     [payment_amount]   = 100
     *     [transfer_time]    = 2015-08-18 17:54:41
     *     [desc]             = 企业付款测试
     *     [transfer_name]    = 王尼玛
     *     [return_msg]       = 获取成功
     * )
     *
     * 失败时返回格式：
     * Array
     * (
     *     [return_code]      = FAIL
     *     [result_code]      = FAIL
     *     [partner_trade_no] = 10000098201411111234567834
     *     [mch_id]           = 1249048701
     *     [return_msg]       = 指定单号数据不存在
     *     [err_code]         = NOT_FOUND
     *     [err_code_des]     = 指定单号数据不存在
     * )
	 * @return array
	 */
	public function query($partner_trade_no) {
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
		$this->setAttribute('appid', $this->APPID);
		$this->setAttribute('mch_id', $this->MCHID);
		$this->setAttribute('nonce_str', $this->getNonceStr());
		$this->setAttribute('partner_trade_no', $partner_trade_no);
		$this->setAttribute('sign', $this->MakeSign());
		$xml    = $this->ToXml();
		$result = $this->postXmlCurl($xml, $url);
		return $this->xml_to_array($result);
	}


}

/**
 * Example:
 */

$wechat = new WechatCoPay(
							'wxc9e5c38ea516e924',//绑定支付的APPID
							'1249048301',//商户号
							'cascwefweoi943j09j09fj4f4sh3',//商户支付密钥
							'/data/www/web/app/libraries/wxpay/cert/apiclient_cert.pem',//证书路径
							'/data/www/web/app/libraries/wxpay/cert/apiclient_key.pem'//证书路径
						 );

//企业支付
//具体参数请查看：https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_2
$wechat->setAttribute('partner_trade_no', '10000098201411111234567890');
$wechat->setAttribute('openid', 'oacGps5SvsILkNOJiF0efeKjXI');
$wechat->setAttribute('check_name', 'OPTION_CHECK');
$wechat->setAttribute('re_user_name', '王尼玛');
$wechat->setAttribute('amount', 1000);
$wechat->setAttribute('desc', '企业付款测试');
$result = $wechat->pay();
print_r($result);


//企业支付查询
//具体参数请查看：https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_3
$result = $wechat->query('10000098201411111234567890');
print_r($result);