<?php
namespace app\index\controller;
use think\Controller;

class Base extends Controller
{
	/**
	 * 常规返回信息方法
	 */
	protected function message($data, $httpCode=200)
	{
		// 回溯查询
		if(debug_backtrace()[2]['class']=='ReflectionMethod'){
			return json($data,$httpCode);
		}else{
			return $data;
		}
	}
}