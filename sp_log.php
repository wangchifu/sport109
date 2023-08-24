<?php
include_once "109.conf.php";
include_once "db_connect.php";
//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();
// if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

// login_chk();
login_chk('F');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

//a.建立物件
$obj= new sport_login($CONN,$smarty);

// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();


/*
sport_login物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','iday','info'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`iday`,`info`);
------------工具箱用
id,iday,info
流水號,日期,內容
------------檢核用
id,流水號
iday,日期
info,內容

*/

class sport_login{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=10;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_login';
	//資料表欄位
	var $fields=array('id','iday','info');

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

	/* 2-1.建立資料表處理函式  */
	function crTB() {
		$SQL1="select * from `{$this->TB}` limit 1";
		$SQL2="CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`iday`,`info`);";
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
			if(pVar('form_act')=='add') $this->add();
			if(pVar('form_act')=='update') $this->update();
			if(pVar('form_act')=='del') $this->del();
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
		$tpl = __SitePtah."/sp_log_php.htm";
		//$tpl = dirname(__file__)."/sport_login.htm";
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
		$Order_syntax='order by id desc';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `{$this->TB}`  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
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
		Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','iday','info');
		foreach ($this->fields as $FF){
			//if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="INSERT INTO `sport_login`(iday , info)values ('{$iday}' ,'{$info}' )";
		$rs=$this->CONN->query($SQL) or die($SQL);

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		//Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		$fields=array('title','iday','info');
		if ($_POST['id']=='') return ;//無索引值
		$id=(int) $_POST['id'];
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='' || $FF=='id') continue ;//空值不更新,流水號不必更新
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
			// $SQL[]="$FF=\"{$$FF}\"";
		}
		if (count($SQL)==0)  return ;//無任何更新資料
		$SQL="update `{$this->TB}`  set title='{$title}' ,iday='{$iday}' ,info='{$info}'  where id='{$id}' ";
		//$SQL="update `{$this->TB}` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		//Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int) $_POST['id'];
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



