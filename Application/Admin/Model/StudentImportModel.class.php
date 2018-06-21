<?php

namespace Admin\Model;
use Think\Model;

/**
 * 学生导入模型
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class StudentImportModel extends CommonModel
{
	// 表名
	protected $tableName = 'student';

	// 开启批量验证状态
	protected $patchValidate = true;

	// 数据自动校验
	protected $_validate = array(
		// 添加,编辑
		array('username', 'CheckUserName', '{%student_username_format}', 1, 'callback', 3),
		array('id_card', 'CheckIdCard', '{%common_view_id_card_format}', 1, 'callback', 3),
		array('gender', array(0,1,2), '{%common_gender_tips}', 1, 'in', 3),
		array('birthday', 'CheckBirthday', '{%student_birthday_format}', 1, 'callback', 3),
		array('class_id', 'IsExistClass', '{%student_class_tips}', 1, 'callback', 3),
		array('region_id', 'IsExistRegion', '{%student_region_tips}', 1, 'callback', 3),
		array('state', array(0,1,2,3,4), '{%common_student_state_tips}', 1, 'in', 3),
		array('tel', 'CheckTel', '{%common_view_tel_error}', 2, 'callback', 3),
		array('my_mobile', 'CheckMyMobile', '{%student_my_mobile_error}', 2, 'callback', 3),
		array('parent_mobile', 'CheckParentMobile', '{%common_view_parent_mobile_error}', 1, 'callback', 3),
		array('email', 'CheckEmail', '{%common_email_format_error}', 2, 'callback', 3),
		array('tuition_state', array(0,1), '{%common_tuition_state_tips}', 1, 'in', 3),

		// 添加
		array('id_card', 'UniqueIdCard', '{%common_student_exist_error}', 1, 'callback', 1),
	);

	/**
	 * [UniqueIdCard 身份证和学期号必须唯一]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-29T17:12:27+0800
	 * @param    [string] $value [校验值]
	 * @return   [boolean]       [存在false, 不存在true]
	 */
	public function UniqueIdCard($value)
	{
		// 读取学期配置信息
		$semester_id = MyC('admin_semester_id');
		if(empty($semester_id) || empty($value))
		{
			return false;
		}

		// 校验是否唯一
		$id = $this->db(0)->where(array('id_card'=>$value, 'semester_id'=>$semester_id))->getField('id');
		return empty($id);
	}

	/**
	 * [CheckUserName 姓名校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T19:29:30+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckUserName($value)
	{
		$len = Utf8Strlen($value);
		return ($len >= 2 && $len <= 16);
	}

	/**
	 * [CheckIdCard 身份证号码校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckIdCard($value)
	{
		return (preg_match('/'.L('common_regex_id_card').'/', $value) == 1) ? true : false;
	}

	/**
	 * [CheckBirthday 生日校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckBirthday($value)
	{
		return (preg_match('/'.L('common_regex_birthday').'/', $value) == 1) ? true : false;
	}

	/**
	 * [IsExistClass 班级id是否存在]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-21T22:13:52+0800
	 * @param    [string] $value [校验值]
	 * @return   [boolean]       [存在true, 不存在false]
	 */
	public function IsExistClass($value)
	{
		// 当用户操作自身的情况下不需要校验
		$class = $this->db(0)->table('__CLASS__')->field(array('id', 'pid'))->find($value);
		if(empty($class))
		{
			return false;
		}

		if($class['pid'] == 0)
		{
			// 是否存在子级
			$count = $this->db(0)->table('__CLASS__')->where(array('pid'=>$class['id']))->count();
			return ($count == 0);
		} else {
			// 父级是否存在
			$count = $this->db(0)->table('__CLASS__')->where(array('id'=>$class['pid']))->count();
			return ($count > 0);
		}
	}

	/**
	 * [IsExistRegion 地区是否存在]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-10T14:09:40+0800
	 * @param    [string] $value [校验值]
	 * @return   [boolean]       [存在true, 不存在false]
	 */
	public function IsExistRegion($value)
	{
		$id = $this->db(0)->table('__REGION__')->where(array('id'=>$value))->getField('id');
		return !empty($id);
	}

	/**
	 * [CheckTel 座机号码校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckTel($value)
	{
		return (preg_match('/'.L('common_regex_tel').'/', $value) == 1) ? true : false;
	}

	/**
	 * [CheckMyMobile 学生手机号码校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckMyMobile($value)
	{
		return (preg_match('/'.L('common_regex_mobile').'/', $value) == 1) ? true : false;
	}

	/**
	 * [CheckParentMobile 家长手机号码校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckParentMobile($value)
	{
		return (preg_match('/'.L('common_regex_mobile').'/', $value) == 1) ? true : false;
	}

	/**
	 * [CheckEmail 电子邮箱校验]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-13T15:12:32+0800
	 * @param    [string] $value [校验值]
	 */
	public function CheckEmail($value)
	{
		return (preg_match('/'.L('common_regex_email').'/', $value) == 1) ? true : false;
	}
}
?>