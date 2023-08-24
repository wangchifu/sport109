<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

// login_chk();
login_chk('F');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

//a.建立物件
$obj= new sport_main($CONN,$smarty);

// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();


/*
sport_main物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','title','year','signtime','stoptime','work_start','work_end','memo'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`title`,`year`,`signtime`,`stoptime`,`work_start`,`work_end`,`memo`);
------------工具箱用
id,title,year,signtime,stoptime,work_start,work_end,memo
ID,名稱,日期,報名時間,結束報名,大會操作時間,大會結束時間,備註
------------檢核用
id,ID
title,名稱
year,日期
signtime,報名時間
stoptime,結束報名
work_start,大會操作時間
work_end,大會結束時間
memo,備註

id,ID
title,名稱
year,日期
sign,可報名否？
work,可操作否？
memo,備註
 
*/

class sport_main{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=10;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_main';
	//資料表欄位
	var $fields=array('id','title','year','sign','work','memo');
	var $YN1=array('Y'=>'可以報名','N'=>'報名結束');
	var $YN2=array('Y'=>'可以操作','N'=>'禁止操作');
	

	/* 1.建構函式 */
	function __construct($CONN,$smarty){
		$this->CONN=$CONN;
		$this->smarty=$smarty;
	}

	/* 2.初始化一些數值處理函式  */
	function init() {
		$page=gpVar('page');
		$this->page=($page=='') ? 0:(int)$page;
	}

	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();

		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			$form_post=pVar('form_act');
			switch($form_post){
				case 'add':	$this->add();break;
				case 'update':$this->update();break;
				case 'del':	$this->del();break;
			}
		}
		//隨機驗證碼
		$this->token=$myToken->make();
		//擷取資料
		$this->all();

		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/sport_main.htm";
		//$tpl = dirname(__file__)."/sport_main.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->user_name = $_SESSION['Auth']['name'];
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}

	/* 5.擷取資料給網頁呈現處理函式*/
	function all(){
		//先處理搜尋條件
		$Search_syntax='';//$Search_syntax=$this->Search();

		//先算總筆數
		$SQL="select  count(id) from `sport_main`  $Search_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax='';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `sport_main`  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all=$arr;//return $arr;

		//產生連結鈕
		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}

	/* 6.新增處理函式 */
	function add(){
		//echo "<pre>";print_r($_POST);die();
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','title','year','signtime','stoptime','work_start','work_end','memo');
		//$fields=array('title','year','memo');
		$fields=array('title','year','sign','work','memo');
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		
		
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="INSERT INTO `sport_main`(title , year , sign, work, memo)values ('{$title}' ,'{$year}' ,'{$sign}' ,'{$work}' ,'{$memo}' )";
		$rs=$this->CONN->query($SQL) or die($SQL);

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	function gTime($a,$b){
		if ($_POST[$a]=='') backe('日期未填！');
		if ($_POST[$b]=='') backe('時間未填！');
		$A=pVar($a);$B=pVar($b).':00';
		$time=	$A.' '.$B;
		if (strlen($time)!=19) backe('日期時間格式有誤！');
		return $time;	
	}


	/* 7.更新處理函式  */
	function update(){
		//Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('id','title','year','signtime','stoptime','work_start','work_end','memo');
		$fields=array('title','year','sign','work','memo');
		if ($_POST['id']=='') return ;//無索引值
		$id=(int) $_POST['id'];
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='' || $FF=='id') continue ;//空值不更新,流水號不必更新
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
			//$SQL[]="$FF=\"{$$FF}\"";
		}
		//if (count($SQL)==0)  return ;//無任何更新資料
		$SQL="update `sport_main`  set title='{$title}' ,year='{$year}' ,sign='{$sign}',work='{$work}' ,memo='{$memo}'  where id='{$id}' ";
		//$SQL="update `{$this->TB}` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int) $_GET['id'];
		$SQL="Delete from  `{$this->TB}`  where  id='{$id}'  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 9.過濾及SQL語法處理函式 */
	function gSQLStr($_action){	}

	/* 	10.尋找資料函式	*/
	function Search(){}

	/* 	11.排序資料函式	*/
	function myOrder(){}



}
//end class



