<?php
/*======================================================================*\
SPIDER - the PHP web spider
Author: Zhenqiang Ying <yingzhenqiang@163.com>
Version: 1.2.1

The latest version of SPIDER can be obtained from:
https://github.com/baidut/php_web_spider

Feature:
-fetch web pages 网页抓取
-get data 数据提取


重要更新：解决https下的抓取问题


TODO:
智能配置模式，输入页面元素关键字，快速定位，初始化信息位置到数据库，之后采集
直接定位，如果没有找到再重新进行初始化，初始化失败再请求人工配置
\*======================================================================*/
define('DATE_PATTEN',"/^d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])(s+(0?[0-9]|1[0-9]|2[0-3]):(0?[0-9]|[1-5][0-9]):(0?[0-9]|[1-5][0-9]))?$/");

// 异常类型定义
class CurlException extends Exception{} 
class ParserException extends Exception{} 

class Spider{

    private $ch;        // cURL handle
    private $error;     // error messages sent here
    private $html;     	// carry last fetched html page

    function __construct($url='') { 
        if(!extension_loaded('curl'))
            exit('Fatal error:The system does not extend php_curl.dll.');
        $this-> ch = curl_init();
        $this-> reset();
        if($url)$this->fetch($url);
    }
    function __destruct() { 
        curl_close($this-> ch);
    }
/*======================================================================*\
    Purpose:    reset spider
\*======================================================================*/
    function reset(){
        // curl_setopt($this-> ch, CURLOPT_USERAGENT,     "kind spider"); //"kind spider""Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; .NET4.0C; InfoPath.3; rv:11.0) like Gecko"
        curl_setopt($this-> ch, CURLOPT_COOKIEJAR,      "./cookie.txt");
        curl_setopt($this-> ch, CURLOPT_RETURNTRANSFER, 1); // 如果设置了这项则curl_exec会在成功时返回内容
// mixed curl_exec ( resource ch )
// Execute the given cURL session.
// This function should be called after you initialize a cURL session and all the options for the session are set.
        curl_setopt($this-> ch, CURLOPT_TIMEOUT,        120);
    }
/*======================================================================*\
    Purpose:    fetch   a web page by url
    Input:      $url    web page address
    Output:     web page content
\*======================================================================*/
    function fetch($url){
		$opt = array(
			CURLOPT_COOKIE  => "./cookie.txt",
			CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_URL     => $url,
            CURLOPT_HEADER  => 0,
            // CURLOPT_POST    => 1,
            // CURLOPT_POSTFIELDS      => (array)$data,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_TIMEOUT         => 120, //$timeout, 120s 2min
            );
		// 解决https下的抓取问题
		$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
		if ($ssl) {
			$opt[CURLOPT_SSL_VERIFYHOST] = 2;
			// Notice: curl_setopt_array(): CURLOPT_SSL_VERIFYHOST no longer accepts the value 1, value 2 will be used instead in D:\Program Files\xampp\htdocs\GitHub\a327.sinaapp.com\1\core\php_web_spider.php on line 79
			$opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
		}
		curl_setopt_array($this-> ch, $opt);
		
        $output = curl_exec($this-> ch); // 防止失败后覆盖掉上次结果

        if($output)$this-> html = $output;
        else {
			echo curl_error($this-> ch);
			throw new CurlException($this-> error = curl_error($this-> ch));
			return null;
		}
        return $output;
    }
/*======================================================================*\
    Purpose:    submit a form
    Input:      $url    web page address
                $fields form content
                    format: $fields["name"] = "value";
    Output:     web page content
\*======================================================================*/
    function post($url,$fields){
        curl_setopt($this-> ch,CURLOPT_POST,1);
        curl_setopt($this-> ch,CURLOPT_POSTFIELDS,$fields);
        curl_setopt($this-> ch,CURLOPT_URL,$url);
        curl_setopt($this-> ch,CURLOPT_COOKIE, COOKIE_FILE); 
        curl_setopt($this-> ch,CURLOPT_FOLLOWLOCATION,true);
        // 返回跳转后的页面 如果只提交表单，则返回1表示成功
        return $this-> html = curl_exec($this-> ch);
    }

/*======================================================================*\
    Purpose:    login
    Input:      $_url       
                $_username  
                $_password  
                $_hidden    
    Output:     the text output from the post
\*======================================================================*/
    function login($_url,$_username,$_password,$_hidden=""){
    // 分析网页，获得表单并分析
        $html = file_get_html($_url);           // 获取页面成功 
		// 添加智能分析判断表单位置 原来是直接取第一个表单
		$forms = $html-> find('form');
		foreach($forms as $form){
			if( $form-> find('input[type=password]',0) ){
				$inputs = $form-> find('input[name]');
				foreach($inputs as $input){
					switch($input-> type){
						case 'text': 
							$name_username = $input-> name;// 假设只有一个输入框，不需要填写验证码
							$fields[$name_username] = urlencode($_username);
							break;
						case 'password':
							$name_password = $input-> name;
							$fields[$name_password] = urlencode($_password);
							break;
						case 'radio':
							$name_radio =  $input-> name;
							if(!isset($first_radio[$name_radio]) || isset($input-> checked ) ){
								$first_radio[$name_radio]=false;
								$fields[$name_radio] = $input-> value;
							}
							break;
						default:
							if(trim($input-> value))
								$fields[$input-> name] = $input-> value;
					}
				}
				// 此处可能会有冲突问题，附加的hidden变化由js触发，需要重写
				if($_hidden) $fields = array_merge($fields, $_hidden); // 添加hidden
				// 添加自动补充默认选项的提交
				
				if( $action = $form-> action) $_url = $action; // 如果action为空的话，如果不为空还要分析出主机  // 修正bug
				//print_r($fields);
				//exit(0);
				return $this-> post($_url,$fields);
			}
		}
		$this-> error = 'ERROR: form cannnot be found!';
		return false;
    }
/*======================================================================*\
    Purpose:    根据搜索的页面地址，以及输入框位置，模拟一次输入文本搜索的操作
    Input:      $_url       
                $_txt  
                $_how2find  
    Output:     the search result
\*======================================================================*/
    function search($_url,$_txt,$_how2find){ // 
    // 分析网页，获得表单并分析，这一步不需要模拟登陆工具
        $html = file_get_html($_url);                   // 获取页面成功 
        $form = $html->find('form'.$_how2find,0);       // 定位表单echo $form;exit(0);
        // 填写搜索框
        $text = $form-> find('input[type=text]',0);
        $fields = array( $text-> name => $_txt );
        // 添加hidden域
        $hiddens = $form-> find('input[type=hidden]');
        foreach ($hiddens as $key => $hidden) {
            $fields[ $hidden-> name ] = $hidden->value;
        }
        // 分析提交动作
        $method = $form-> method;
        $action = $form-> action; // 假设是绝对路径，没有处理相对路径
        // 下面执行模拟搜索
        if($action)
            $_url = $action;
        if($method=='get')
            return $data = $this-> fetch( $_url . '?' . http_build_query($fields) );
        if($method=='post'){
            // print_r($_url);print_r($fields);exit(0);
            // echo $this-> fetch($action);
            return $this-> post($_url,$fields);
        }
    }
/*======================================================================*\
    Output:     the title of the web page
\*======================================================================*/
    function fetch_title($_url=""){ 
        if($_url&&$this-> fetch($_url)){
            $html = str_get_html($this-> html);
            return $html->find('title',0)->plaintext;
        }
        return false;
    }
/*======================================================================*\
    Output:     basic information of the web page
\*======================================================================*/
    function fetch_info($_url=""){ 
        if( (!$_url) || $this-> fetch($_url)){
            $html = str_get_html($this-> html);
            $header = $html->find('head',0);
            $info['title'] = $header ->find('title',0)->plaintext;
            if( $keywords = $header ->find('meta[name=keywords]',0) )
                $info['keywords'] = $keywords->content;
            if( $description = $header ->find('meta[name=description]',0))
                $info['description'] = $description ->content;
            return $info;
        }
        return false;
    }
/*======================================================================*\
    Purpose:    屏蔽网页分析工具实现，提供格式化的表格数据
    Input:       
    Output:     the text output from the post
	NOTICE:		没有声明url时，取上次页面
	解析为普通二维数组不便于查询
	解析为$data[第几个][某个字段]更自然
\*======================================================================*/
    function fetch_table($_how2find,$_url=""){ 
		if($_url)$this-> fetch($_url);
        $html = str_get_html($this-> html);
        $table = $html->find('table'.$_how2find,0);
		if(!$table) {
			$this-> error = "Failed to locate the informatin table";
			return false;
		}
		// 可能有th标题 这里暂不处理 thead tbody tfood 都暂不处理
		// table to array
		$trs = $table->find('tr');
		$th = array_shift($trs);
		foreach( $th-> find('td') as $key => $val) {
			$name[$key] = $val->innertext;//plaintext;
		}
		if(!$trs) {
			$error = "No data";
			return false;
		}
		foreach( $trs as $key => $val)
			foreach($val->find('td') as $k => $v)
				$data[$key][ $name[$k] ] = trim( $v-> innertext );
		
		//header("Content-type: text/html; charset=utf-8");
		//print_r($data);
		return $data;
    }
/*======================================================================*\
    Purpose:    
    初始化先分析ul结构，提取出时间字段的位置和链接的位置后快速处理
    转为数组结构
\*======================================================================*/
    function fetch_news($_url=null,$filter=null){ 
        try{
            if($_url) $this-> fetch($_url);// throw可以跨函数
            $html = str_get_html($this->html);
            $body = $html->find('body',0); 
            // 初始化 
            if(!$body)throw new ParserException("Fail to locate the html body.");// Call to a member function find() on a non-object
            // 智能分析内容所在位置
            $i_link = $i_date = '';
            $lis=array();
            foreach( $body->find('ul') as $key => $ul){
                $li = $ul->find('li',1); // 取一条分析即可 取第二条比较合适
                // echo $li->plaintext.'----------------------'; // 查看检索到的列表的第二项
                // 日期有可能包含在链接里面 目前的方法不是很通用 适应性差
                // 目前这种情况的处理是直接返回第一个元素作为日期 很有问题
                $find_link = $find_date = $date_in_link = false;
                foreach ($li->children() as $key => $element) {
                    switch($element->tag){
                    case 'a': 
                        $i_link = $key; 
                        $find_link = true;
                        if($element->find('span')){
                            $date_in_link = true;
                            $find_date = true;
                        }
                        break;
                    case 'p': 
                    case 'span':
                        if(1/*preg_match(DATE_PATTEN,trim($element->plaintext))*/){
                            $i_date = $key;
                            $find_date = true;
                        }
                        break;
                    default:break;
                    }
                }
                if($find_link&&$find_date){
                    $lis = $ul->find('li');
                    //print_r($lis);
                    break;
                }
            }
            if(!$find_link) throw new ParserException("Fail to locate the links.");
            // li to array
            foreach( $lis as $key => $val) {
                // $data[$key]['title'] = $val->children($i_link)->plaintext;
                if(trim($val->plaintext)!=''){ // 有的网页存在<li><br/></li>
                    $href = $val->children($i_link)->href;
                    $title = $val->children($i_link)->plaintext;
                    $enc_href = urlencode($href);
                    if($date_in_link){
                        $data[$key]['raw_link'] = $val->children($i_link)->outertext;
                        $data[$key]['date'] = $val->children($i_link)->children(0)->plaintext;
                    }
                    else {
                        $data[$key]['date'] = $val->children($i_date)->plaintext;
                        $data[$key]['raw_link'] = $val->children($i_link)->outertext;
                    } 
                    $data[$key]['link'] = "<a href='reader.php?url=$enc_href'>$title</a><a href='$href'>访问原网页</a>";

                    // 按日期过滤结果，后期通过数据库解决
                    $d = strtotime( $data[$key]['date'] );
                    $today = strtotime(date("Y-m-d"));
                    $dif_days = round(($today-$d)/3600/24);
                    $break = false;
                    switch($filter){
                        case 'week':
                            if( $dif_days> 7 )
                                $break = true;
                        case 'month':
                            if( $dif_days> 30 )
                                $break = true;
                    }
                    if($break) break;
                }
            }
            return $data;
        } catch(CurlException $e){
          $this-> error = $e->getMessage();
          echo '如果是本地测试请检查网络连接'.$this-> error;
        }catch(ParserException $e){
          $this-> error = $e->getMessage();
          echo '解析出错'.$this-> error;
        }
    }
    function fetch_results($url=null){
        if($url) $this->fetch($url);

        $html = str_get_html($this->html);
        $results = $html->find('ul[class=Results]',0);
//        echo $results; exit(0);
        $data = array();
        if(!empty($results)){
            foreach($results->find('li') as $key => $result){
                // todo  fix urls automatically (parse the base url from $url and html header or use snoopy instead of cURL)
                $title = $result->find('h3',0);
                $link =  $title-> find('a',0)->href; // todo use reference &
                $title-> find('a',0)->href = 'http://ieeexplore.ieee.org'.$link;
                $data[$key] = $title->innertext;
            }
        }
        return $data;
    }
	// 编写快速调试定位的工具
	// 都是定位列表ul 智能将网页列表转为关联数组 fetch_ul fetch_list 一般都为ul
	// 用正则匹配比较麻烦，直接取出信息节点
	// class可不填，用于定位列表 后期智能判断最显著的列表
	function fetch_list($url=null, $class=null){
        if($url) $this->fetch($url);

        $html = str_get_html($this->html);
		$results = $html->find('ul'.($class)?"[class=$class]":'',0);
//        echo $results; exit(0);
        $data = array();
        if(!empty($results)){
            foreach($results->find('li') as $key => $result){
                // 最简单暴力的方法，直接列举所有的 可能有重复，无所谓
				// 先div后span
				foreach($result->find('div') as $idiv => $div){
					$ispan = 0;
					foreach($div->find('span') as $ispan => $span){
						$data[$key][$span->class] = $span->plaintext;
					}
					if(!$ispan) {
						$data[$key][$div->class] = $div->plaintext;
					}
				}
            }
        }
        return $data;
    }
	// 测试是否正常
	// echo $sp->test();
	function test() {
		$url = 'http://www.baidu.com/s?ie=UTF-8&wd=%E4%BD%A0%E5%A5%BD';
		$data = $this->fetch($url);
		// eg2 可以直接get豆瓣图书检索结果
		$url = 'http://book.douban.com/subject_search?search_text=php&cat=1001';
		$data .= $this-> fetch($url);
		return $data;
	}
/*======================================================================*\
    Purpose:    
    获取页面核心内容
\*======================================================================*/
    function fetch_main_content($_url='',$_info='div[class=article]'){
        if($_url)$this->fetch($_url);
        $html = str_get_html($this-> html);
        $body = $html->find('body',0);
        $main = $body->find($_info,0);
        return $main;
    }
	// to be added

}


class NewsSpider extends Spider {
    // 新闻和抓取配置存储到数据库中
    // 数据库表 spider
    // news link date abstract read
    private $p_url;
    private $p_link;
    private $p_date;
    function __construct($url){
        // 如果数据库中可以查到对应url的配置，则载入，否则新建数据并载入

        parent::__construct($url);
    }
    function getLatest($num,$fromTime) {// 获取从$fromTime起最近的$num条信息

    }
    function getUnread($num,$fromTime){ // 获取没有读的消息

    }
}
?>