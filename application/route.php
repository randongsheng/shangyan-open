<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
// 注册路由到index模块的Index控制器的index操作
// Route::rule('/','index/Index/index');
// 管理员登录
Route::rule('/login','index/Admin/login');
// 管理员添加
Route::rule('/regster','index/Admin/adminRegster');
// 获取所有普通用户
Route::rule('/users','index/User/queryUsers');
// 普通用户统计
Route::rule('/user/statistics','index/User/statistics');
// 用户详情
Route::rule('/user/details','index/User/details');
// 导出用户信息
Route::rule('/download/users','index/User/downExcel','GET');
// 获取用户评论
Route::rule('/user/comments','index/User/getUserComment','GET');
// 删除文章评论
Route::rule('/user/comments/del','index/Article/delComment','GET');
// 文章点赞
Route::rule('/user/article/fabulous','index/User/getUserFabulous','GET');
// 用户文章收藏
Route::rule('/user/article/collection','index/User/getUserCollection','GET');
// 机构规则说明
Route::rule('/clinic/rules','index/Clinic/getRule','GET');
// 已关停机构申请开启
Route::rule('/clinic/apply/reopen','index/Clinic/reOpenClinic','POST');
// 添加机构规则
Route::rule('/clinic/rule/add','index/Clinic/addRule','POST');
// 修改规则状态
Route::rule('/clinic/rule/edit','index/Clinic/editRule','POST');
// 所有机构
Route::rule('/clinics','index/Clinic/all','GET');
// 机构统计
Route::rule('/clinic/count','index/Clinic/clinicCount','GET');
// 机构详情
Route::rule('/clinic/details','index/Clinic/details','GET');
// 机构访客
Route::rule('/clinic/visitors','index/Clinic/visitors','GET');
// 机构评论
Route::rule('/clinic/evaluate','index/Clinic/evaluate','GET');
// 机构合作记录
Route::rule('/clinic/relevant','index/Clinic/relevantRecord','GET');
// 某用户的测试订单
Route::rule('/user/order/test','index/Order/userOrderTest','GET');
// 倾听订单记录
Route::rule('/user/order/listen','index/Order/userListenOrder','GET');
// 咨询订单记录
Route::rule('/user/order/consult','index/Order/userConsultOrder','GET');
// 添加机构账号
Route::rule('/clinic/account/add','index/Clinic/createAccount','POST');
// 填写机构信息
Route::rule('/clinic/account/info','index/Clinic/clinicInfoInsert','POST');
// 修改机构信息
Route::rule('/clinic/account/editinfo','index/Clinic/applyInfo','POST');
// 机构上传图片
Route::rule('/clinic/upload','index/Clinic/uploadImgs','POST');
// 机构动态
Route::rule('/clinic/details/trends','index/Clinic/clinicTrends','GET');
// 机构订单动态
Route::rule('/clinic/details/orders','index/Clinic/trendsOrders','GET');
// 老师统计
Route::rule('/teacher/count','index/Teacher/teacherCount','GET');
// 所有老师
Route::rule('/teachers','index/Teacher/teachers','GET');
// 老师详情
Route::rule('/teacher/details','index/Teacher/details','GET');
// 老师动态
Route::rule('/teacher/details/trends','index/Teacher/trends','GET');
// 老师访客
Route::rule('/teacher/visitors','index/Teacher/visitors','GET');
// 导出老师数据
Route::rule('/download/teachers','index/Teacher/download','GET');
// 老师审核
Route::rule('/teacher/examine','index/Teacher/examine','POST');
// 老师专业信息修改审核
Route::rule('/teacher/majorinfo/examine','index/Teacher/teacherEditInfoExamine','POST');
// 老师被评价
Route::rule('/teacher/details/evaluate','index/Teacher/evaluate','GET');
// 老师动态（订单）
Route::rule('/teacher/details/orders','index/Teacher/trendsOrders','GET');
// 老师合作记录
Route::rule('/teacher/details/relevant','index/Teacher/relevantRecord','GET');
// 标签
Route::rule('/labels','index/Label/labels','GET');
// 标签详情
Route::rule('/label/details','index/Label/labelDetails','GET');
// 标签添加
Route::rule('/label/add','index/Label/addLabel','POST');
// 标签关联
Route::rule('/label/relevance','index/Label/relevance','POST');
// 文章列表
Route::rule('/articles','index/Article/articles','GET');
// 动态发布
Route::rule('/clinic/trends/send','index/Clinic/sendNews','POST');
// 动态修改
Route::rule('/clinic/trends/edit','index/Clinic/editStatus','POST');
// 修改动态内容
Route::rule('/clinic/trends/editinfo','index/Clinic/editTrends','POST');
// 获取动态列表
Route::rule('/clinic/trends','index/Clinic/trends','GET');
// 机构上下架
Route::rule('/clinic/shelf','index/Clinic/shelf','POST');
// 保证金
Route::rule('/clinic/deposit','index/Clinic/deposit','GET');
// 保证金详情
Route::rule('/clinic/deposit/details','index/Clinic/depositDetails','GET');
// 关停申请
Route::rule('/clinic/closure','index/Clinic/closureList','GET');
// 关停申请详情
Route::rule('/clinic/closure/details','index/Clinic/closureDetails','GET');
// 所有保证集统计
Route::rule('/clinic/deposit/count','index/Clinic/depositCount','GET');
// 关停审核
Route::rule('/clinic/closure/apply','index/Clinic/editClosureStatus','GET');
// 机构通过审核
Route::rule('/clinic/examine','index/Clinic/examine','POST');
// 黑名单列表
Route::rule('/blacklist','index/User/blacklist','GET');
// 黑名单统计
Route::rule('/blacklist/count','index/User/blacklistCount','GET');
// 机构加入黑名单
Route::rule('/clinic/blacklist/add','index/Clinic/blacklistAction','POST');
// 取消加入黑名单
Route::rule('/clinic/blacklist/del','index/Clinic/blacklistClean','POST');
// 老师黑名单
Route::rule('/teacher/blacklist/add','index/Teacher/blacklistAction','POST');
// 撤回老师黑名单
Route::rule('/teacher/blacklist/del','index/Teacher/blacklistClean','POST');
// 用户黑名单
Route::rule('/user/blacklist/add','index/User/blacklistAction','POST');
// 用户撤出黑名单
Route::rule('/user/blacklist/del','index/User/blacklistClean','POST');
// 上传图片
Route::rule('/teacher/upload','index/Teacher/uploadImgs');
// 修改老师图片
Route::rule('/teacher/upload/edit','index/Teacher/editImg');
// 添加老师第一步
Route::rule('/teacher/add/one','index/Teacher/addTeacher','POST');
// 添加老师第二步
Route::rule('/teacher/add/two','index/Teacher/teacherRole','POST');
// 添加老师第三步
Route::rule('/teacher/add/three','index/Teacher/personalProfile','POST');
// 添加老师第四步
Route::rule('/teacher/add/four','index/Teacher/insertTeacher','POST');

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
