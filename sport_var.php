<?php
include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

login_chk('F');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

//a.建立物件
$obj= new sport_var($CONN,$smarty);
$obj->Cache=$Cache;
// 初始化工作-- 
// $obj->init();

//b.處理程序
$obj->process();


/*
sport_var物件 class 應用說明
用到的變數$_POST['form_act']的值為 add、update、Search處理程式新增、更新、搜尋
'id','gp','kkey','na'
------------建表語法
CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`gp`,`kkey`,`na`);
------------工具箱用
id,gp,kkey,na
編號,分類/變數名稱,索引值,資料值
------------檢核用
id,編號
gp,分類/變數名稱
kkey,索引值
na,資料值

*/

class sport_var{
	var $CONN;//PHP5的PDO物件
	var $smarty;//smarty物件
	var $size=30;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_var';
	//資料表欄位
	var $fields=array('id','iKey','memo','data','iday');

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
		$SQL1="select * from `sport_var` limit 1";
		$SQL2="CREATE TABLE `{$this->TB}`( `id` INTEGER PRIMARY KEY,`iKey` TEXT NOT NULL UNIQUE,`memo`,`data`,`iday`);";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) {
			$this->CONN->query($SQL2);	
			$URL=$_SERVER['SCRIPT_NAME'];
			Header("Location:$URL");}
	}



	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();

		//資料表建立檢查--運作後可以註解掉
		$this->crTB();

		//處理表單送出的資料
		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			$formAct=pVar('form_act');
			if ($formAct=='add') $this->add();
			if ($formAct=='update') $this->update();
			if ($formAct=='del')	$this->del();
			if ($formAct=='delF')	$this->dropTB();			
			if ($formAct=='add_def') $this->add_def();
			
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
		$tpl = __SitePtah."/sport_var.htm";
		//$tpl = dirname(__file__)."/sport_var.htm";
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
		//echo $this->tol;
		//處理排序依據
		$Order_syntax='';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `{$this->TB}`  $Search_syntax  $Order_syntax  limit ".($this->page*$this->size).", {$this->size}  ";
		//$SQL="select * from `{$this->TB}`  ";
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
		$fields=array('iKey' , 'memo' , 'data');
		foreach ($fields as $FF){
		//	if ($_POST[$FF]=='') continue ;
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
		}
		$iday=date("Y-m-d H:i:s");
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$SQL="INSERT INTO `{$this->TB}`(iKey,memo,data,iday)values ('{$iKey}' ,'{$memo}' ,'{$data}','{$iday}' )";
		$rs=$this->CONN->query($SQL) or die($SQL);
		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$this->Cache->del('Options');
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		// Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		$fields=array('iKey','memo','data');
		//PP($_POST);
		if ($_POST['id']=='') return ;//無索引值
		$id=(int) $_POST['id'];
		foreach ($fields as $FF){
			//if ($_POST[$FF]=='' || $FF=='id') continue ;//空值不更新,流水號不必更新
			$tmp=filter_var($_POST[$FF], FILTER_SANITIZE_STRING);
			$$FF= strip_tags(trim($tmp));
			//$SQL[]="$FF=\"{$$FF}\"";
		}
		$iday=date("Y-m-d H:i:s");
		$SQL="update `sport_var`  set iKey='{$iKey}' ,memo='{$memo}' ,data='{$data}',iday='{$iday}'  where id='{$id}' ";
		// $SQL="update `{$this->TB}` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$this->Cache->del('Options');
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		//Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$id=(int)pVar('id');
		$SQL="Delete from  `sport_var`  where  id='{$id}'  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$this->Cache->del('Options');
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 9.過濾及SQL語法處理函式 */
	function add_def(){
		$iday=date("Y-m-d H:i:s");
		$SQL1="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('sportname' ,'項目名稱' ,'{$iday}', '1:作文
2:演講
3:注音
4:查字典
5:書法
6:朗讀
7:跳高
8:跳遠
9:棒球擲遠
10:打字
11:壘球擲遠
12:60公尺
13:80公尺
14:100公尺
15:200公尺
16:學生調查
17:大隊接力
18:推鉛球
19:鐵餅
20:標槍')";

$SQL2="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('sportclass' ,'組別代碼' ,'{$iday}','1a:一男
1b:一女
2a:二男
2b:二女
3a:三男
3b:三女
4a:四男
4b:四女
5a:五男
5b:五女
6a:六男
6b:六女
1c:1年級
2c:2年級
3c:3年級
4c:4年級
5c:5年級
6c:6年級
7a:國一男
7b:國一女
8a:國二男
8b:國二女
9a:國三男
9b:國三女
7c:1年級
8c:2年級
9c:3年級' )";

$SQL3="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('sportkind' ,'競賽類別' ,'{$iday}','1:競賽類
5:競賽(接力)
2:田賽類
3:語文類
4:其他類')";
$SQL4="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('sportkind2' ,'競賽序別' ,'{$iday}','1:初賽
2:決賽
3:不分')";
$SQL5="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('classA' ,'班級選單' ,'{$iday}','101:1年1班
102:1年2班
103:1年3班
104:1年4班
105:1年5班

201:2年1班
202:2年2班
203:2年3班
204:2年4班
205:2年5班
206:2年6班

301:3年1班
302:3年2班
303:3年3班
304:3年4班
305:3年5班
306:3年6班

401:4年1班
402:4年2班
403:4年3班
404:4年4班
405:4年5班

501:5年1班
502:5年2班
503:5年3班
504:5年4班
505:5年5班
506:5年6班
507:5年7班

601:6年1班
602:6年2班
603:6年3班
604:6年4班
605:6年5班
606:6年6班
607:6年7班')";

$SQL6="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('sportkind3' ,'競賽序別' ,'{$iday}','1:1.初賽
2:2.決賽
3:3.不分')";

$SQL7="INSERT INTO `sport_var`(iKey,memo,iday,data)values ('Oth' ,'其他選項' ,'{$iday}','runNum:8
info:(跑道數)runNum是指跑道數
BY:B
infoBY:BY如果是B，就輸出道次')";

		$rs=$this->CONN->query($SQL1);
		$rs=$this->CONN->query($SQL2);
		$rs=$this->CONN->query($SQL3);
		$rs=$this->CONN->query($SQL4);
		$rs=$this->CONN->query($SQL5);
		$rs=$this->CONN->query($SQL6);
		$rs=$this->CONN->query($SQL7);
		
		$this->Cache->del('Options');
		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
		}

	/* 	10.尋找資料函式	*/
	function Search(){}

	/* 	11.排序資料函式	*/
	function dropTB(){
		$SQL="Delete from  `sport_var` ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$this->Cache->del('Options');
		$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
	}



}
//end class



/*
sportname
1:作文
2:演講
3:注音
4:查字典
5:書法
6:朗讀
7:跳高
8:跳遠
9:棒球擲遠
10:打字
11:壘球擲遠
12:60公尺
13:80公尺
14:100公尺
15:200公尺
16:學生調查
17:大隊接力
18:推鉛球
19:鐵餅
20:標槍

sportclass
1a:一男
1b:一女
2a:二男
2b:二女
3a:三男
3b:三女
4a:四男
4b:四女
5a:五男
5b:五女
6a:六男
6b:六女
1c:1年級
2c:2年級
3c:3年級
4c:4年級
5c:5年級
6c:6年級
7a:國一男
7b:國一女
8a:國二男
8b:國二女
9a:國三男
9b:國三女
7c:1年級
8c:2年級
9c:3年級

*/
