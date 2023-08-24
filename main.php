<?php

include_once "109.conf.php";
include_once "db_connect.php";

// if (allowDomain()=='N') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

// login_chk();
//if( !isset( $_SESSION ) )  session_start();
// unset($_SESSION['main_id']);
//session_start();unset($_SESSION);
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
	var $fields=array('id','title','year','signtime','stoptime','work_start','work_end','memo');

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
			$form_post=pVar('act');
			switch($form_post){
				case 'add':	$this->add();break;
			//	case 'view':$this->view();break;
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
		$tpl = __SitePtah."/main.htm";
		//$tpl = dirname(__file__)."/sport_main.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}

	/* 5.擷取資料給網頁呈現處理函式*/
	function all(){

		//處理排序依據
		$Order_syntax='order by id desc ';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `sport_main`    $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all=$arr;//return $arr;
		
		//產生連結鈕
		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}

	/* 6.新增處理函式 */
	function add(){
		$mid=pVar('mid');
		if ($mid=='') backe('未選擇項目！');
		
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="select * from `sport_main` where id='{$mid}' ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		if (count($arr)==1){
			if( !isset( $_SESSION ) )  session_start();
			$_SESSION['main_id']=$arr[0]['id'];
			$_SESSION['main_ary']=$arr[0];
		}

		$URL=$_SERVER['SCRIPT_NAME'];
		$URL='index.php';
		Header("Location:$URL");
	}


}
//end class



