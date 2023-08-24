<?php

include_once "109.conf.php";
include_once "db_connect.php";
// if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
//session_start();

//login_chk('E');
login_chk('E');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

//a.建立物件
$obj= new teach($CONN,$smarty);

// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();


/*
teach物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','tname','sex','office','title','user','pass','cla','edukey','created','modify'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`tname`,`sex`,`office`,`title`,`user`,`pass`,`cla`,`edukey`,`created`,`modify`);
------------工具箱用
id,tname,sex,office,title,user,pass,cla,edukey,created,modify
流水號,姓名,性別,處室,職稱,帳號,密碼,班級,編碼,建立時間,變更時間
------------檢核用
id,流水號
tname,姓名
sex,性別
office,處室
title,職稱
user,帳號
pass,密碼
cla,班級
edukey,編碼
created,建立時間
modify,變更時間

*/

class teach{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=20;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='teach';
	var $prem=array('A'=>'A報名操作','B'=>'B檢錄工作','C'=>'C成績輸入',
	'D'=>'D項目管理','E'=>'E帳號管理','F'=>'F系統設定','All'=>'All全部權限');
	//資料表欄位
	var $fields=array('id','tname','sex','office','title','user','pass','cla','edukey','created','modify');

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

	/* 2-0.檢察token碼  */
	function chkToken() {
		$Token='';
		if (isset($_GET['token'])) $Token=strip_tags($_GET['token']);
		if (isset($_POST['token'])) $Token=strip_tags($_POST['token']);
		if ($Token=='') return 'N';
		if ($Token==$_SESSION['token']) { return 'Y';}else{ return 'N';}
	}

	/* 2-1.建立資料表處理函式  */
	function crTB() {
		$SQL1="select * from `{$this->TB}` limit 1";
		$SQL2="CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`tname`,`sex`,`office`,`title`,`user`,`pass`,`cla`,`edukey`,`created`,`modify`);";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $this->CONN->query($SQL2);
	}



	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();

		//資料表建立檢查--運作後可以註解掉
		//$this->crTB();

		//處理表單送出的資料

		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			$act=pVar('form_act');
			if ($act=='add') $this->add();
			if ($act=='update') $this->update();
			if ($act=='del') $this->del();
			if ($act=='Switch') $this->SwitchUser();
			
		}
		//隨機驗證碼
		$this->token=$myToken->make();

		//擷取資料
		$this->all();

		//隨機驗證碼
		$this->token = md5(uniqid(rand(), true));
		$_SESSION['token']= $this->token;

		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/teach.htm";
		//$tpl = dirname(__file__)."/teach.htm";
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
		$SQL="select  count(id) from `{$this->TB}`  $Search_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax='';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `teach`  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all=$arr;//return $arr;
		//print_r($arr);
		//產生連結鈕
		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}

	/* 6.新增處理函式 */
	function add(){
		//echo "<pre>";print_r($_POST);die();
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','tname','sex','office','title','user','pass','cla','edukey','created','modify');
		foreach ($this->fields as $FF){
			if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="INSERT INTO `{$this->TB}`(tname , sex , office , title , user , pass , cla , edukey , created , modify)values ('{$tname}' ,'{$sex}' ,'{$office}' ,'{$title}' ,'{$user}' ,'{$pass}' ,'{$cla}' ,'{$edukey}' ,'{$created}' ,'{$modify}' )";
		$rs=$this->CONN->query($SQL) or die($SQL);

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		//Auth();//簡易認證
		//echo '<pre>';print_r($_POST);die();
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int)pVar('id');
		if ($id==0 ||$id=='') backe('參數錯誤！');
		$fields=array('tname','sex','office','title','user','cla');
		foreach ($fields as $FF){
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		$prem1=myJoin('prem');
		$modify=date("Y-m-d H:i:s");
		//$SQL="update `teach`  set id='{$id}' ,tname='{$tname}' ,sex='{$sex}' ,office='{$office}' ,title='{$title}' ,user='{$user}' ,pass='{$pass}' ,cla='{$cla}' ,edukey='{$edukey}' ,created='{$created}' ,modify='{$modify}'  where id='{$id}'  ";
		$SQL="update `teach`  set tname='{$tname}' ,sex='{$sex}' ,office='{$office}' ,title='{$title}' ,user='{$user}' ,cla='{$cla}',prem='{$prem1}' ,modify='{$modify}'  where id='{$id}'  ";
		
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		$id=(int)pVar('id');
		if ($id==0 ||$id=='') backe('參數錯誤！');
		
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		
		$SQL="Delete from  `teach`  where  id='{$id}'  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 9.過濾及SQL語法處理函式 */
	function gSQLStr($_action){	}

	/* 	10.尋找資料函式	*/
	function Search(){}

	/* 	11.排序資料函式	*/
	function SwitchUser(){
		$id=(int)pVar('id');
		if ($id==0 ||$id=='') backe('參數錯誤！');	
		$SQL="select * from  `teach`  where  id='{$id}'  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		
		if (count($arr)==1){
			if( !isset( $_SESSION ) )  session_start();
			$code = $_SESSION['Auth']['code'];
			unset($_SESSION['Auth']);
			$_SESSION['Auth']=$arr[0];
			$_SESSION['Auth']['code']=$code;
			$_SESSION['Auth']['name']=$arr[0]['tname'];
			$URL='index.php';
			Header("Location:$URL");
		}

	}



}
//end class



