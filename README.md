# WechatCoPay
PHP Demo code for wechat company/enterprise payment 微信企业支付PHP示例代码

#集成必要底层函数的支付类，可直接调用，也可随意重构，便于开发者根据自身需求修改

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
$wechat = new WechatCoPay(
		           'wxc9e5c38ea516e924',//绑定支付的APPID
			   '1249048301',//商户号
			   'cascwefweoi943j09j09fj4f4sh3',//商户支付密钥
		           '/data/www/web/app/libraries/wxpay/cert/apiclient_cert.pem',//证书路径
			   '/data/www/web/app/libraries/wxpay/cert/apiclient_key.pem'//证书路径
		          );
$result = $wechat->query('10000098201411111234567890');
print_r($result);
