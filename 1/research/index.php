<?php
// Fixed:
// RG注册用户和非注册用户问题
// Added:
// 字母排序
// Todo：
// 论文资源抓取 根据出版社信息 或者google（推荐）
// 抓取google学术数据 加限定词较准确


// 主页仅索引，无需抓取内容
define('CORE_PATH','../core/');// 改成公共定义
require_once(CORE_PATH.'php_simple_ui.php');

// 后续自动生成人物列表，通过抓取分析导师论文页面
// 解析author，重复问题：作为键值，value为发表数目，可以同时进行统计

// 很多属性，后期从数据库中获取
// 11级

define('PUB_URL', 'pub.php?name='); 

$studs = array(
'Baicheng_Xin/辛柏成'=> 'researcher/2046203244_Baicheng_Xin',
'Bingjie_Han/韩冰杰' => 'researcher/2046040485_Bingjie_Han',
'Chengzhou_Tang/唐骋洲' => 'researcher/2046038085_Chengzhou_Tang',
'Jianbo_Jiao/焦剑波' => 'profile/Jianbo_Jiao/contributions',//'2063167891_Jianbo_Jiao',
'Jianlong_Zhang/张建龙' => 'researcher/2049677699_Jianlong_Zhang',
'Jie_Wan/万杰'=> 'researcher/2045803101_Jie_Wan',
'Lei_Zhang/张雷'=> 'profile/Lei_Zhang120/contributions',
'Long_Zhao/赵龙'=> 'researcher/2046142641_Long_Zhao',
'Qinshui_Chen/陈钦水'=> 'researcher/2049611613_Qinshui_Chen',
'Xufeng_Li/李旭峰' => 'researcher/2059813588_Xufeng_Li',
'Yang_Zhao/赵洋' => 'researcher/2077987018_Yang_Zhao',
'Zhenyu_Wang/王振宇' => 'researcher/2045936206_Zhenyu_Wang',
'Zhongxin_Liu/刘中欣'=> 'researcher/2049712533_Zhongxin_Liu',
'Zhengguang_Lv/吕正光'=> 'profile/Zhengguang_Lv/contributions',
);

// 添加编码
foreach($studs as $key => $stud){
	$studs[$key] = PUB_URL.urlencode($stud); // 没必要加编码
}

// 允许重定向 无法解决

// 未注册用户可以正确得到，注册用户会进行重定向，需要转入pub否则
// https://www.researchgate.net/researcher/2046038085_Chengzhou_Tang
// https://www.researchgate.net/researcher/2063167891_Jianbo_Jiao
// https://www.researchgate.net/profile/Jianbo_Jiao
// https://www.researchgate.net/profile/Jianbo_Jiao/contributions
// https://www.researchgate.net/researcher/$stud

$list = new ui_JMListView(array($studs));// 注意这里array($data)
$list->addFilter('搜索')->addDividers();
$page = new ui_JMPage('论文',array($list));
$ui = new ui_jQueryMobile($page);

echo $ui;
return;
