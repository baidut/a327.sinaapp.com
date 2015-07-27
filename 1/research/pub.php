<?php
header("Content-type:text/html;charset=utf-8");

// 待解决：摘要显示不全问题，需要追加<p>分段 JQ自动分段特性考察

$name = $_GET['name'];

// 模板容易失效

// echo 'hello'.$name;
$url = 'https://www.researchgate.net/'. $name;

// 载入爬虫库
define('CORE_PATH','../core/');
require_once(CORE_PATH.'php_web_spider.php');
require_once(CORE_PATH.'simple_html_dom.php');

set_time_limit(0);
$sp = new Spider;
// echo $sp->test();return;
// echo $sp->fetch($url);return;
$pubs = $sp->fetch_list($url, 'c-list');
// print_r($pubs);return;

// 下面输出数据 提供链接 用php_simple_ui 有气泡
// 说明数据排版
// $data['href'] = $pubs[]
// 每一篇
// $data = array(); // 初始化为一维数组则出问题
foreach($pubs as $key => $pub){
	$data[$key]['title'] = (strcmp(substr($pub['publication-type'], 0, 7 ), 'Article')?'[C]':'[J]') .' '. $pub['publication-title js-publication-title'];
	$data[$key]['subtitle'] = $pub['details'];
	if(isset($pub['full'])) $data[$key]['content'] = substr($pub['full'], 16);
	$data[$key]['aside'] = $pub['authors'];
}
// print_r($data);return;
// echo $data[1]['title'];return;

require_once(CORE_PATH.'php_simple_ui.php');
$list = new ui_JMListView(array($data), 'Chengzhou_Tang');// 注意这里array($data)
$list->addFilter('搜索');
$page = new ui_JMPage('论文',array($list));
$ui = new ui_jQueryMobile($page);

echo $ui;
return;
// 个人页面实时抓取

// 后期存入数据库，开发好原型后发布到github先
// https://www.researchgate.net/profile/Ronggang_Wang/publications

// 不全需要三页所有内容
// 自动拼接
// https://www.researchgate.net/profile/Ronggang_Wang/publications?sorting=newest&page=2
// 确定页数信息
// 抓取内容信息
// IEEE容易失效，采用research gate的导师页面进行抓取  论文还可以直接访问
// IEEE高级搜索后期再提供实现
