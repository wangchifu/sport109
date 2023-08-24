<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

login_chk('D');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

// login_chk();
if (sVar('main_id')=='') Header("Location:main.php");
if ($_SESSION['main_ary']['work']=='N') backe('目前無法操作！');

//a.建立物件
$obj= new sport_item($CONN,$smarty);
$obj->Cache=$Cache;


// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();





/*
sport_item物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','mid','item','enterclass','sportorder','sportkind','sunit','sord','playera','passera','kgp','kgm','place','kind','skind','res','sporttime','overtime','imemo'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`mid`,`item`,`enterclass`,`sportorder`,`sportkind`,`sunit`,`sord`,`playera`,`passera`,`kgp`,`kgm`,`place`,`kind`,`skind`,`res`,`sporttime`,`overtime`,`imemo`);
------------工具箱用
id,mid,item,enterclass,sportorder,sportkind,sunit,sord,playera,passera,kgp,kgm,place,kind,skind,res,sporttime,overtime,imemo
ID,主項,項目名稱,組別,順序,類別,計分格式,排列方式,初賽,錄取,可報名組數,每組人數,地點,類別,子類別,res,比賽時間,結束時間,額外說明
------------檢核用
id,ID
mid,主項
item,項目名稱
enterclass,組別
sportorder,順序
sportkind,類別(競賽類,競賽(接力),田賽類,語文類,其他類)
sunit,計分格式
sord,排列方式--1分數低，成績好,2分數高，成績好
playera,初賽
passera,錄取
kgp,可報名組數(限接力)
kgm,每組人數(限接力)
place,地點
kind,類別
skind,子類別
res,res==>取消了
sporttime,比賽時間
overtime,結束時間
imemo,額外說明

*/

class sport_item{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=20;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_item';
	var $sportkind_name=array(1=>"競賽類",5=>"競賽(接力)",2=>"田賽類",3=>"語文類",4=>"其他類");//關連到sport_item.sportkind 
	var $kind_unit=array(1=>"x.xx.xx.x(時.分.秒.x)",2=>"xx.xx.x(公尺.公分.x)");
	var $k_unit=array('1'=>'0.00.00.0','2'=>'00.00.0');//計分格式
	var $itemkind=array("1"=>"初賽","2"=>"決賽","3"=>"不分");//關連到sport_item.kind 
	var $Sord=array('1'=>'1.分數低，成績好','2'=>'2.分數高，成績好');
	//資料表欄位
	var $fields=array('id','mid','item','enterclass','sportorder','sportkind','sunit','sord','playera','passera','kgp','kgm','place','kind','skind','res','sporttime','overtime','imemo');

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




	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();

		//資料表建立檢查--運作後可以註解掉
		//$this->crTB();

		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			$formAct=pVar('form_act');
			if ($formAct=='item_add') $this->add();
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
		$tpl = __SitePtah."/sport_item.htm";
		//$tpl = dirname(__file__)."/sport_item.htm";
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
		$SQL="select  count(id) from `{$this->TB}`   where mid='{$this->Mid}' ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax='order by sportorder,enterclass,sportkind';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `{$this->TB}`  where mid='{$this->Mid}'  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";// echo $SQL;
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all=$arr;//return $arr;

		$SQL="select * from `sport_var`  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		
		$this->Options=array();
		foreach ($arr as $ary){
			$Ka=$ary['iKey'];
			$this->Options[$Ka]=tranAry($ary['data']);
		}
		//產生連結鈕
		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
		//echo '<pre>';print_r($this->Options);
	}

	/* 6.新增處理函式 */
	function add(){
		//echo "<pre>";print_r($_POST);die();
/*
Array
(
    [act] => item_add
    [x] => 20
    [y] => 18
    [id] => 
    [mid] => 
    [sportkind] => 1
    [item] => 12
    [createnexttime] => yes
    [day1] => 2020-01-17
    [day2] => 09:00
    [enterclass] => Array
        (
            [0] => 1b
            [1] => 2b
            [2] => 4b
            [3] => 5a
        )

    [playera] => 8
    [passera] => 5
    [imemo] => 擇優2人
    [sunit] => 00.00.0
    [sord] => 1
    [place] => 操場
    [kgp] => 0
    [kgm] => 0
*/

		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求,sportorder賽程序
		$fields=array('item','enterclass','sportkind','sunit','sord','playera','passera','kgp','kgm','place','imemo');
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		$sporttime=pVar('day1');
		$overtime=pVar('day2');
		$Next=pVar('createnexttime');
		$sportorder=0;
		$mid=1;$res=0;
		foreach ($_POST['enterclass'] as $ent){
			//是否競賽(接力)
			if($sportkind!='5'){$kgp=0;$kgm=0;}
			if ($Next=='yes'){
			$SQL="INSERT INTO `sport_item`(mid , item , enterclass , sportorder , sportkind , sunit , sord , playera , passera , kgp , kgm , place , kind , skind , sporttime , overtime , imemo)values ('{$this->Mid}' ,'{$item}' ,'{$ent}' ,'{$sportorder}' ,'{$sportkind}' ,'{$sunit}' ,'{$sord}' ,'{$playera}' ,'{$passera}' ,'{$kgp}' ,'{$kgm}' ,'{$place}' ,'1' ,'0' ,'{$sporttime}' ,'{$overtime}' ,'{$imemo}' )";
			$rs=$this->CONN->query($SQL) or die($SQL);
			//建立決賽			
			$Insert_ID= $this->CONN->lastInsertId();
			$SQL="INSERT INTO `sport_item`(mid , item , enterclass , sportorder , sportkind , sunit , sord , playera , passera , kgp , kgm , place , kind , skind ,  sporttime , overtime , imemo)values ('{$this->Mid}' ,'{$item}' ,'{$ent}' ,'{$sportorder}' ,'{$sportkind}' ,'{$sunit}' ,'{$sord}' ,'{$playera}' ,'{$passera}' ,'{$kgp}' ,'{$kgm}' ,'{$place}' ,'2' ,'{$Insert_ID}' ,'{$sporttime}' ,'{$overtime}' ,'{$imemo}' )";
			$rs=$this->CONN->query($SQL) or die($SQL);
			
			//只建立決賽
			}else{
			$SQL="INSERT INTO `sport_item`(mid , item , enterclass , sportorder , sportkind , sunit , sord , playera , passera , kgp , kgm , place , kind , skind , sporttime , overtime , imemo)values ('{$this->Mid}' ,'{$item}' ,'{$ent}' ,'{$sportorder}' ,'{$sportkind}' ,'{$sunit}' ,'{$sord}' ,'{$playera}' ,'{$passera}' ,'{$kgp}' ,'{$kgm}' ,'{$place}' ,'2' ,'0' ,'{$sporttime}' ,'{$overtime}' ,'{$imemo}' )";
			$rs=$this->CONN->query($SQL) or die($SQL);
			}
			
		}
		// echo "<pre>";print_r($_POST);die();	
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$this->Cache->del('Sport_All_item');//刪除快取
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		// Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('item','enterclass','sportorder','sportkind','sunit','sord','playera','passera','kgp','kgm','place','kind','skind','res','sporttime','overtime','imemo');
		
		$fields=array('sportkind','enterclass','item','kind','skind','sportorder','sunit','sord','playera','passera', 'kgp','kgm','place', 'sporttime','overtime','imemo');
		//echo '<pre>';print_r($_POST);die();
		if ($_POST['id']=='') return ;//無索引值
		$id=(int) $_POST['id'];
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='' || $FF=='id') continue ;//空值不更新,流水號不必更新
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
			//$SQL[]="$FF=\"{$$FF}\"";
		}
		//$sporttime=pVar('day1');$overtime=pVar('day2');
		//echo '<pre>';print_r($_POST);die();
		$SQL="update `sport_item`  set item='{$item}' ,enterclass='{$enterclass}' ,sportorder='{$sportorder}' ,sportkind='{$sportkind}' ,sunit='{$sunit}' ,sord='{$sord}' ,playera='{$playera}' ,passera='{$passera}' ,kgp='{$kgp}' ,kgm='{$kgm}' ,place='{$place}' ,kind='{$kind}' ,skind='{$skind}'  ,sporttime='{$sporttime}' ,overtime='{$overtime}' ,imemo='{$imemo}'  where id='{$id}' ";
		// $SQL="update `{$this->TB}` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$this->Cache->del('Sport_All_item');//刪除快取
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		//Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=pVar('id');
		if ($id=='') backe('未傳值！');
		$SQL="Delete from  `sport_item`  where  id='{$id}' and mid='{$this->Mid}' ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$this->Cache->del('Sport_All_item');//刪除快取
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



