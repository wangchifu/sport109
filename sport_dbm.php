<?php

include_once "109.conf.php";
include_once "db_connect.php";
// if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

// login_chk();
login_chk('F');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限


//a.建立物件
$obj= new sport_main($CONN,$smarty);
$obj->Cache=$Cache;
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
	var $TB='';
	//資料表欄位
	var $fields=array('id','title','year','signtime','stoptime','work_start','work_end','memo');
	//var $tb=array('teach','stud','sport_var','sport_main','sport_item','sport_res','sport_login');
	var $tb=array('teach','stud','sport_var','sport_main','sport_item','sport_res');
	var $tb_memo=array('教師資料表','學生資料表','變數資料表','比賽名稱表','項目名稱表','報名成績表','登入記錄');
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
			$formAct=pVar('form_act');
			if ($formAct=='add') $this->add();
			if ($formAct=='update') $this->update();
			if ($formAct=='del')	$this->del();
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
		$tpl = __SitePtah."/sport_dbm.htm";
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
		$SQL="SELECT * FROM sqlite_master WHERE  type = 'table'";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$arr=$rs->fetchAll();
		//$this->all=$arr;
		$A=array();
		foreach ($arr as $ary){
			$K=$ary['name'];
			$SQL="select  count(*) from `{$K}` ";
			$rs=$this->CONN->query($SQL) or die($SQL);
			$tol=$rs->fetchColumn(); // $this->tol=$rs->rowCount();
			$ary['tol']=$tol;
			$A[$K]=$ary;
		}
		$this->all=$A;
		
		//echo '<pre>';print_r($A);
	}

	/* 6.新增處理函式 */
	function add(){
		//echo "<pre>";print_r($_POST);die();
		/* 建立 stud 資料表*/
		$SQL1="select * from `stud` limit 1";
		$SQL2="CREATE TABLE `stud`( `id` INTEGER PRIMARY KEY,`stuid` TEXT NOT NULL UNIQUE,`stuname` TEXT NOT NULL,`idclass` TEXT NOT NULL,`cla` TEXT NOT NULL,`seatnum` INTEGER DEFAULT '0',`sex` TEXT NOT NULL,`edukey` TEXT,`created` TEXT ,`modify` TEXT )";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) {
			$rs=$this->CONN->query($SQL2);
			$arr=API_data();
			$this->add_stud($arr);
		}

		/* 建立 teach 資料表*/
/*
		$SQL1="select * from `teach` limit 1";
		$SQL2="CREATE TABLE `teach`( `id` INTEGER PRIMARY KEY,`tname` TEXT NOT NULL,`sex` TEXT,`office` TEXT,`title` TEXT,`user` TEXT,`pass` TEXT,`cla` TEXT,`prem` TEXT,`edukey` TEXT NOT NULL UNIQUE,`created` TEXT ,`modify` TEXT )";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) {
			$rs=$this->CONN->query($SQL2);
			//$this->makeTB_teach();
			$arr=API_data();
			$this->add_teach($arr);
		}
*/

		/* 建立 sport_main 資料表*/
		$SQL1="select * from `sport_main` limit 1";
		//$SQL2="CREATE TABLE `sport_main`( `id` INTEGER PRIMARY KEY,`title`,`year`,`signtime`,`stoptime`,`work_start`,`work_end`,`memo`);";
		$SQL2="CREATE TABLE `sport_main`( `id` INTEGER PRIMARY KEY,`title`,`year`,`sign`,`work`,`memo`);";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $this->CONN->query($SQL2);

		/* 建立 sport_item 資料表*/
		$SQL1="select * from `sport_item` limit 1";
		$SQL2="CREATE TABLE `sport_item`( `id` INTEGER PRIMARY KEY,`mid` INTEGER DEFAULT '0',`item` INTEGER DEFAULT '0',`enterclass`,`sportorder` INTEGER DEFAULT '0',`sportkind` INTEGER DEFAULT '0',`sunit`,`sord` INTEGER DEFAULT '0',`playera` INTEGER DEFAULT '0',`passera` INTEGER DEFAULT '0',`kgp` INTEGER DEFAULT '0',`kgm` INTEGER DEFAULT '0',`place`,`kind` INTEGER DEFAULT '0',`skind` INTEGER DEFAULT '0',`sporttime`,`overtime`,`imemo`,UNIQUE(mid,item,enterclass,kind));";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $this->CONN->query($SQL2);

		/* 建立 sport_res 資料表*/
		$SQL1="select * from `sport_res` limit 1";
		$SQL2="CREATE TABLE `sport_res`( `id` INTEGER PRIMARY KEY,`mid` INTEGER DEFAULT '0',`itemid` INTEGER DEFAULT '0',`kmaster` INTEGER DEFAULT '0',`kgp` INTEGER DEFAULT '0',`kend` INTEGER DEFAULT '0',`stud_id`,`sportkind`,`cname`,`idclass`,`sportnum`,`num` INTEGER DEFAULT '0',`results`,`sportorder` INTEGER DEFAULT '0',`memo`,`passOK` INTEGER DEFAULT '0',UNIQUE(mid,itemid,kgp,idclass));";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $this->CONN->query($SQL2);

		/* 建立 sport_login 資料表*/
		$SQL1="select * from `sport_login` limit 1";
		$SQL2="CREATE TABLE `sport_login`( `id` INTEGER PRIMARY KEY,`title`,`iday`,`addr`,`info`);";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $this->CONN->query($SQL2);

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function API_data(){
		$file = __SiteData.date("Y").'_elps.txt';
		//echo $file;
		if (file_exists($file)) {
			$sch_json=file_get_contents($file);
			}else{
			$sch_json=elps_API();
			$num=file_put_contents($file,$sch_json);//寫入echo $num;
			}
		$arr = json_decode($sch_json);
		return $arr;
	}
	/* 8.刪除處理函式  */
	function del(){
		//echo "<pre>";print_r($_POST);die();
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int) $_GET['id'];
		$tb=pVar('TB');
		if ($tb=='') backe('未傳值！');
		$SQL="drop table  `$tb` ";
		
		$this->Cache->del('sport_item_all_A');
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
	}

	/* 9.過濾及SQL語法處理函式 */
	function gSQLStr($_action){	}

	/* 	10.尋找資料函式	*/
	function Search(){}

	/* 	11.排序資料函式	*/
	function myOrder(){	}

/*加入學生資料*/
function add_stud($arr){
	if (!isset($arr->學期編班)) return ;
	foreach($arr->學期編班 as $obj){
	/* 判斷有沒有學生 */
	if (isset($obj->學期編班)) {
		//echo $obj->年級.'-'.$obj->班序.'人數'.count($obj->學期編班).'<br>';
		foreach($obj->學期編班 as $ary){
			$created=date("Y-d-m H:i:s");
			$stuid=$ary->學號;
			$seatnum=$ary->座號;
			$stuname=$ary->姓名;
			$sex=$ary->性別;
			$edukey=$ary->身分證編碼;
			$cla=$obj->年級.'_'.$obj->班序;
			$idclass=$obj->年級.sprintf("%02d",$obj->班序).sprintf("%02d",$ary->座號);
			$SQL="INSERT INTO `stud`(stuid , stuname , idclass , cla , seatnum , sex , edukey , created)values ('{$stuid}' ,'{$stuname}' ,'{$idclass}' ,'{$cla}' ,'{$seatnum}' ,'{$sex}' ,'{$edukey}' ,'{$created}')";
			//echo $SQL."<br>";
			$rs=$this->CONN->query($SQL);// or die($SQL);
			}
		}
	}
}

/*加入教師資料表*/
function add_teach($arr){
	if (!isset($arr->學期編班)) return ;
	$created=date("Y-m-d H:i:s");
	foreach($arr->學期編班 as $obj){
	/* 判斷有沒有導師 */
	if (isset($obj->導師)) {
		$cla=$obj->年級.'_'.$obj->班序;
		$N=count($obj->導師);
		for($i=0;$i<$N;$i++){
			$name=$obj->導師[$i]->姓名;
			$edukey=$obj->導師[$i]->身分證編碼;
			// echo $A.'班 '.$obj->導師[$i]->姓名.$obj->導師[$i]->身分證編碼.'<br>';
			$SQL="INSERT INTO `teach`(tname,cla, edukey , created)values ('{$name}' ,'{$cla}' ,'{$edukey}' ,'{$created}')";
			//echo $SQL."<br>";
			$rs=$this->CONN->query($SQL);// or die($SQL);
			}
		}
	}
	/*加入其他老師*/
	if (isset($arr->學期教職員)) {
		$created=date("Y-m-d H:i:s");
		foreach ($arr->學期教職員 as $ob){
			$office=$ob->處室;
			$title=$ob->職稱;
			$tname=$ob->姓名;
			$user=$ob->帳號;
			$sex=$ob->性別;
			$edukey=$ob->身分證編碼;
			$SQL1="INSERT INTO `teach`(tname , sex , office , title , user, edukey , created)values ('{$tname}' ,'{$sex}' ,'{$office}' ,'{$title}' ,'{$user}' ,'{$edukey}' ,'{$created}')";
			$SQL2="update `teach`  set sex='{$sex}' ,office='{$office}' ,title='{$title}' ,user='{$user}' , modify='{$created}'  where edukey='{$edukey}' ";
			$rs=$this->CONN->query($SQL1);
			if (!$rs) $this->CONN->query($SQL2);
		}
	}




	}




//end class
}




