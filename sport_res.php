<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();
login_chk('F');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

// login_chk();
if (sVar('main_id')=='') Header("Location:main.php");
//a.建立物件
$obj= new sport_res($CONN,$smarty);

// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();


/*
sport_res物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`mid`,`itemid`,`kmaster`,`kgp`,`kend`,`stud_id`,`sportkind`,`cname`,`idclass`,`sportnum`,`num`,`results`,`sportorder`,`memo`,`passOK`);
------------工具箱用
id,mid,itemid,kmaster,kgp,kend,stud_id,sportkind,cname,idclass,sportnum,num,results,sportorder,memo,passOK
ID,主項,比賽項目,領隊,組別(接力),kend,學號,類別,姓名,班級座號,運動員編號,num,成績,順序/道次,備註,通過
------------檢核用
id,ID
mid,主項
itemid,比賽項目
kmaster,領隊
kgp,組別(接力)
kend,kend
stud_id,學號
sportkind,類別
cname,姓名
idclass,班級座號
sportnum,運動員編號
num,num
results,成績
sportorder,順序/道次
memo,備註
passOK,通過

*/

class sport_res{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=20;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_res';
	//資料表欄位
	var $fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');

	/* 1.建構函式 */
	function __construct($CONN,$smarty){
		$this->CONN=$CONN;
		$this->smarty=$smarty;
	}

	/* 2.初始化一些數值處理函式  */
	function init() {
		$page=gpVar('page');
		$this->page=($page=='') ? 0:(int)$page;
		$this->Mid=sVar('main_id');
	}

	/* 2-0.檢察token碼  */
	function chkToken() {
		$Token='';
		if (isset($_GET['token'])) $Token=strip_tags($_GET['token']);
		if (isset($_POST['token'])) $Token=strip_tags($_POST['token']);
		if ($Token=='') return 'N';
		if ($Token==$_SESSION['token']) { return 'Y';}else{ return 'N';}
	}



	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();

		//資料表建立檢查--運作後可以註解掉
		// $this->crTB();

		//建立Token
		$myToken=new myToken();
		//處理表單送出的資料
		if ($myToken->check()=='Y'){
		//處理表單送出的資料
			$form_post=pVar('form_act');
			if($form_post=='add') $this->add();
			if($form_post=='update') $this->update();
			if($form_post=='del') $this->del();
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
		$tpl = __SitePtah."/sport_res.htm";
		//$tpl = dirname(__file__)."/sport_res.htm";
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
		$SQL="select  count(id) from `sport_res` where mid='{$this->Mid}' ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax=' order by itemid,idclass ';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `sport_res`  where mid='{$this->Mid}'  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all=$arr;//return $arr;
		$this->Im=ItemName();//項目名稱
		//產生連結鈕
		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}

	/* 6.新增處理函式 */
	function add(){
		//echo "<pre>";print_r($_POST);die();
		Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		foreach ($this->fields as $FF){
			if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="INSERT INTO `sport_res`(mid , itemid , kmaster , kgp , kend , stud_id , sportkind , cname , idclass , sportnum , num , results , sportorder , memo , passOK)values ('{$mid}' ,'{$itemid}' ,'{$kmaster}' ,'{$kgp}' ,'{$kend}' ,'{$stud_id}' ,'{$sportkind}' ,'{$cname}' ,'{$idclass}' ,'{$sportnum}' ,'{$num}' ,'{$results}' ,'{$sportorder}' ,'{$memo}' ,'{$passOK}' )";
		$rs=$this->CONN->query($SQL) or die($SQL);

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		if ($_POST['id']=='') return ;//無索引值
		$id=(int) $_POST['id'];
		foreach ($this->fields as $FF){
			if ($_POST[$FF]=='' || $FF=='id') continue ;//空值不更新,流水號不必更新
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
			$SQL[]="$FF=\"{$$FF}\"";
		}
		if (count($SQL)==0)  return ;//無任何更新資料
		//$SQL="update `{$this->TB}`  set id='{$id}' ,mid='{$mid}' ,itemid='{$itemid}' ,kmaster='{$kmaster}' ,kgp='{$kgp}' ,kend='{$kend}' ,stud_id='{$stud_id}' ,sportkind='{$sportkind}' ,cname='{$cname}' ,idclass='{$idclass}' ,sportnum='{$sportnum}' ,num='{$num}' ,results='{$results}' ,sportorder='{$sportorder}' ,memo='{$memo}' ,passOK='{$passOK}'  where id='{$id}' ";
		$SQL="update `sport_res` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		//Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int) $_POST['id'];
		$SQL="Delete from  `sport_res`  where  id='{$id}'  ";
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



