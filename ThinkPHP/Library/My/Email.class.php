<?php

namespace My;

/**
 * Email驱动
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2017-01-10T21:51:08+0800
 */
class Email
{
	private $interval_time;
	private $expire_time;
	private $key_code;
	public $error;
	private $obj;

	/**
	 * [__construct 构造方法]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-07T14:03:02+0800
	 * @param    [int]        $param['interval_time'] 	[间隔时间（默认30）单位（秒）]
	 * @param    [int]        $param['expire_time'] 	[到期时间（默认30）单位（秒）]
	 * @param    [string]     $param['key_prefix'] 		[验证码种存储前缀key（默认 空）]
	 */
	public function __construct($param = array())
	{
		$this->interval_time = isset($param['interval_time']) ? intval($param['interval_time']) : 30;
		$this->expire_time = isset($param['expire_time']) ? intval($param['expire_time']) : 30;
		$this->key_code = isset($param['key_prefix']) ? trim($param['key_prefix']).'_sms_code' : '_sms_code';
	}

	/**
	 * [EmailInit 邮件初始化]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-10T11:03:38+0800
	 */
	private function EmailInit()
	{
		// 引入邮件发送类库
		vendor("PHPMailer.class#phpmailer");

		// 建立邮件发送类  
		$this->obj = new \phpmailer();

		// 使用smtp方式发送
		$this->obj->IsSMTP();

		// 服务器host地址
		$this->obj->Host = MyC('common_email_smtp_host');

		//smtp验证功能；
		$this->obj->SMTPAuth = true;
		// 端口
		$this->obj->Port = MyC('common_email_smtp_port', 25, true);

		// 邮箱用户名
		$this->obj->Username =  MyC('common_email_smtp_name');

		// 邮箱密码
		$this->obj->Password = MyC('common_email_smtp_pwd');

		// 发件人
		$this->obj->From = MyC('common_email_smtp_account');

		// 发件人姓名
		$this->obj->FromName = MyC('common_email_smtp_send_name');

		// 是否开启html格式
		$this->obj->IsHTML(true);

		// 设置编码
		$this->obj->CharSet = C('DEFAULT_CHARSET');
	}

	/**
	 * [SendHtml html邮件发送]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-10T10:56:43+0800
	 * @param    [string]   $param['email'] 		[收件邮箱]
	 * @param    [string]   $param['content'] 		[内容]
	 * @param    [string]   $param['title'] 		[标题]
	 * @param    [string]   $param['code'] 			[验证码]
	 * @param    [string]   $param['username'] 		[收件人名称]
	 */
	public function SendHtml($param = array())
	{
		if(empty($param['email']))
		{
			$this->error = L('common_library_email_empty_error');
			return false;
		}
		if(empty($param['content']))
		{
			$this->error = L('common_library_content_empty_error');
			return false;
		}
		if(empty($param['title']))
		{
			$this->error = L('common_library_title_empty_error');
			return false;
		}

		// 是否频繁操作
		if(!$this->IntervalTimeCheck())
		{
			$this->error = L('common_prevent_harassment_error');
			return false;
		}

		// 验证码替换
		if(!empty($param['code']))
		{
			$param['content'] = str_replace('#code#', $param['code'], $param['content']);
		}

		// 邮件初始化
		$this->EmailInit();

		// 收件人地址，可以替换成任何想要接收邮件的email信箱,格式("收件人email","收件人姓名")
		$this->obj->AddAddress($param['email'], isset($param['username']) ? $param['username'] : $param['email']);

		// 邮件标题
		$this->obj->Subject = $param['title'];

		// 邮件内容
		$this->obj->Body = $param['content'];

		// 邮件正文不支持HTML的备用显示
		$this->obj->AltBody = strip_tags($param['content']);

		// 发送邮件
		if($this->obj->Send())
		{
			// 是否存在验证码
			if(!empty($param['code']))
			{
				$this->KindofSession($param['code']);
			}
			return true;
		} else {
			$this->error = $this->obj->ErrorInfo;
		}
		return false;
	}

	/**
	 * [KindofSession 种验证码session]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-07T14:59:13+0800
	 * @param    [string]      $code [验证码]
	 */
	private function KindofSession($code)
	{
		$_SESSION[$this->key_code] = array(
				'code' => $code,
				'time' => time(),
			);
	}

	/**
	 * [CheckExpire 验证码是否过期]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-05T19:02:26+0800
	 * @return   [boolean] [有效true, 无效false]
	 */
	public function CheckExpire()
	{
		if(isset($_SESSION[$this->key_code]))
		{
			$data = $_SESSION[$this->key_code];
			return (time() <= $data['time']+$this->expire_time);
		}
		return false;
	}

	/**
	 * [CheckCorrect 验证码是否正确]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-05T16:55:00+0800
	 * @param    [string] $code    [验证码（默认从post读取）]
	 * @return   [booolean]        [正确true, 错误false]
	 */
	public function CheckCorrect($code = '')
	{
		if(isset($_SESSION[$this->key_code]['code']))
		{
			if(empty($code) && isset($_POST['code']))
			{
				$code = trim($_POST['code']);
			}
			return ($_SESSION[$this->key_code]['code'] == $code);
		}
		return false;
	}

	/**
	 * [Remove 验证码清除]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-08T10:18:20+0800
	 * @return   [other] [无返回值]
	 */
	public function Remove()
	{
		if(isset($_SESSION[$this->key_code]))
		{
			unset($_SESSION[$this->key_code]);
		}
	}

	/**
	 * [IntervalTimeCheck 是否已经超过控制的间隔时间]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-10T11:26:52+0800
	 * @return   [booolean]        [已超过间隔时间true, 未超过间隔时间false]
	 */
	private function IntervalTimeCheck()
	{
		if(isset($_SESSION[$this->key_code]))
		{
			$data = $_SESSION[$this->key_code];
			return (time() > $data['time']+$this->interval_time);
		}
		return true;
	}
}
?>