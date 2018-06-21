<?php

namespace Admin\Controller;

/**
 * 文章管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ArticleController extends CommonController
{
	/**
	 * [_initialize 前置操作-继承公共前置方法]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-03T12:39:08+0800
	 */
	public function _initialize()
	{
		// 调用父类前置方法
		parent::_initialize();

		// 登录校验
		$this->Is_Login();

		// 权限校验
		$this->Is_Power();
	}

	/**
     * [Index 文章列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     */
	public function Index()
	{
		// 参数
		$param = array_merge($_POST, $_GET);

		// 模型对象
		$m = M('Article');

		// 条件
		$where = $this->GetIndexWhere();

		// 分页
		$number = MyC('admin_page_number');
		$page_param = array(
				'number'	=>	$number,
				'total'		=>	$m->where($where)->count(),
				'where'		=>	$param,
				'url'		=>	U('Admin/Article/Index'),
			);
		$page = new \My\Page($page_param);

		// 获取列表
		$list = $this->SetDataHandle($m->where($where)->limit($page->GetPageStarNumber(), $number)->order('id desc')->select());

		// 是否启用
		$this->assign('common_is_enable_list', L('common_is_enable_list'));

		// 文章分类
		$this->assign('article_class_list', M('ArticleClass')->field(array('id', 'name'))->where(array('is_enable'=>1))->select());

		// 参数
		$this->assign('param', $param);

		// 分页
		$this->assign('page_html', $page->GetPageHtml());

		// 数据列表
		$this->assign('list', $list);

		// Excel地址
		$this->assign('excel_url', U('Admin/Article/ExcelExport', $param));

		$this->display('Index');
	}

	/**
	 * [ExcelExport excel文件导出]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-01-10T15:46:00+0800
	 */
	public function ExcelExport()
	{
		// 条件
		$where = $this->GetIndexWhere();

		// 读取数据
		$data = $this->SetDataHandle(M('Article')->where($where)->select());

		// Excel驱动导出数据
		$excel = new \My\Excel(array('filename'=>'article', 'title'=>L('excel_article_title_list'), 'data'=>$data, 'msg'=>L('common_not_data_tips')));
		$excel->Export();
	}

	/**
	 * [SetDataHandle 数据处理]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-29T21:27:15+0800
	 * @param    [array]      $data [文章数据]
	 * @return   [array]            [处理好的数据]
	 */
	private function SetDataHandle($data)
	{
		if(!empty($data))
		{
			$ac = M('ArticleClass');
			foreach($data as $k=>$v)
			{
				// 时间
				$data[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
				$data[$k]['upd_time'] = date('Y-m-d H:i:s', $v['upd_time']);

				// 是否启用
				$data[$k]['is_enable_text'] = L('common_is_enable_list')[$v['is_enable']]['name'];

				// 文章分类
				$data[$k]['article_class_name'] = $ac->where(array('id'=>$v['article_class_id']))->getField('name');

				// url
				$data[$k]['url'] = str_replace('admin.php', 'index.php', U('Home/Article/Index', array('id'=>$v['id'])));
			}
		}
		return $data;
	}

	/**
	 * [GetIndexWhere 文章列表条件]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-10T22:16:29+0800
	 */
	private function GetIndexWhere()
	{
		$where = array();

		// 模糊
		if(!empty($_REQUEST['keyword']))
		{
			$where[] = array(
					'title'		=>	array('like', '%'.I('keyword').'%'),
				);
		}

		// 是否更多条件
		if(I('is_more', 0) == 1)
		{
			// 等值
			if(I('is_enable', -1) > -1)
			{
				$where['is_enable'] = intval(I('is_enable', 1));
			}
			if(I('article_class_id', -1) > -1)
			{
				$where['article_class_id'] = intval(I('article_class_id'));
			}

			// 表达式
			if(!empty($_REQUEST['time_start']))
			{
				$where['add_time'][] = array('gt', strtotime(I('time_start')));
			}
			if(!empty($_REQUEST['time_end']))
			{
				$where['add_time'][] = array('lt', strtotime(I('time_end')));
			}
		}
		return $where;
	}

	/**
	 * [SaveInfo 文章添加/编辑页面]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-14T21:37:02+0800
	 */
	public function SaveInfo()
	{
		// 文章信息
		if(empty($_REQUEST['id']))
		{
			$data = array();
		} else {
			$data = M('Article')->find(I('id'));
			if(!empty($data['content']))
			{
				// 静态资源地址处理
				$data['content'] = ContentStaticReplace($data['content'], 'get');
			}
		}
		$this->assign('data', $data);

		// 是否启用
		$this->assign('common_is_enable_list', L('common_is_enable_list'));

		// 文章分类
		$this->assign('article_class_list', M('ArticleClass')->field(array('id', 'name'))->where(array('is_enable'=>1))->select());

		$this->display('SaveInfo');
	}

	/**
	 * [Save 文章添加/编辑]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-14T21:37:02+0800
	 */
	public function Save()
	{
		// 是否ajax请求
		if(!IS_AJAX)
		{
			$this->error(L('common_unauthorized_access'));
		}

		// 添加
		if(empty($_POST['id']))
		{
			$this->Add();

		// 编辑
		} else {
			$this->Edit();
		}
	}

	/**
	 * [Add 文章添加]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-18T16:20:59+0800
	 */
	private function Add()
	{
		// 文章模型
		$m = D('Article');

		// 数据自动校验
		if($m->create($_POST, 1))
		{
			// 额外数据处理
			$m->add_time	=	time();
			$m->upd_time	=	time();
			$m->title 		=	I('title');
			
			// 静态资源地址处理
			$m->content 	=	ContentStaticReplace($m->content, 'add');

			// 正则匹配文章图片
			$temp_image		=	$this->MatchContentImage($m->content);
			$m->image 		=	serialize($temp_image);
			$m->image_count	=	count($temp_image);
			
			// 数据添加
			if($m->add())
			{
				$this->ajaxReturn(L('common_operation_add_success'));
			} else {
				$this->ajaxReturn(L('common_operation_add_error'), -100);
			}
		} else {
			$this->ajaxReturn($m->getError(), -1);
		}
	}

	/**
	 * [Edit 文章编辑]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-17T22:13:40+0800
	 */
	private function Edit()
	{
		// 文章模型
		$m = D('Article');

		// 数据自动校验
		if($m->create($_POST, 2))
		{
			// 额外数据处理
			$m->upd_time	=	time();
			$m->title 		=	I('title');

			// 静态资源地址处理
			$m->content 	=	ContentStaticReplace($m->content, 'add');

			// 正则匹配文章图片
			$temp_image		=	$this->MatchContentImage($m->content);
			$m->image 		=	serialize($temp_image);
			$m->image_count	=	count($temp_image);

			// 数据更新
			if($m->where(array('id'=>I('id')))->save())
			{
				$this->ajaxReturn(L('common_operation_edit_success'));
			} else {
				$this->ajaxReturn(L('common_operation_edit_error'), -100);
			}
		} else {
			$this->ajaxReturn($m->getError(), -1);
		}
	}

	/**
	 * [MatchContentImage 正则匹配文章图片]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-01-22T18:06:53+0800
	 * @param    [string]         $content [文章内容]
	 * @return   [array]    			   [文章图片数组（一维）]
	 */
	private function MatchContentImage($content)
	{
		if(!empty($content))
		{
			$pattern = '/<img.*?src=[\'|\"](\/Public\/Upload\/Article\/image\/.*?[\.gif|\.jpg|\.jpeg|\.png|\.bmp])[\'|\"].*?[\/]?>/';
			preg_match_all($pattern, $content, $match);
			return empty($match[1]) ? array() : $match[1];
		}
		return array();
	}

	/**
	 * [Delete 文章删除]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2016-12-15T11:03:30+0800
	 */
	public function Delete()
	{
		// 是否ajax请求
		if(!IS_AJAX)
		{
			$this->error(L('common_unauthorized_access'));
		}

		// 删除数据
		if(!empty($_POST['id']))
		{
			// 更新
			if(M('Article')->delete(I('id')))
			{
				$this->ajaxReturn(L('common_operation_delete_success'));
			} else {
				$this->ajaxReturn(L('common_operation_delete_error'), -100);
			}
		} else {
			$this->ajaxReturn(L('common_param_error'), -1);
		}
	}

	/**
	 * [StateUpdate 状态更新]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-01-12T22:23:06+0800
	 */
	public function StateUpdate()
	{
		// 参数
		if(empty($_POST['id']) || !isset($_POST['state']))
		{
			$this->ajaxReturn(L('common_param_error'), -1);
		}

		// 数据更新
		if(M('Article')->where(array('id'=>I('id')))->save(array('is_enable'=>I('state'))))
		{
			$this->ajaxReturn(L('common_operation_edit_success'));
		} else {
			$this->ajaxReturn(L('common_operation_edit_error'), -100);
		}
	}
}
?>