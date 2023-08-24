<?php

include "109.conf.php";
//if (allowDomain()=='N') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

login_chk();
//if (sVar('main_id')=='') Header("Location:main.php");
//if (allowDomain()=='N') backe("！不在授權使用範圍內！");
// PP($_SESSION);
// if (ipAllow($PassIP)!='Y') backe("！非學術網路IP\n不在授權使用範圍內！");


$cla=$_SESSION['Auth']['cla'];
//$cla='5_2';
if ($cla=='') backe('限導師操作！');
//if ($_SESSION['main_ary']['sign']=='N') backe('目前無法報名！');

// echo $cla;

//a.建立物件
$obj= new sport_res($CONN,$smarty);
$obj->Cache=$Cache;
//$obj->Mid=sVar('main_id');
//$obj->midAry=$_SESSION['main_ary'];//主項目
$obj->nowClass=change_class($cla);

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
	var $size=10;//每頁筆數
	var $page;//目前頁數
	var $tol;//資料總筆數
	var $token;//編碼
	var $TB='sport_res';
	var $nowClass;//班級
	var $YN1=array("1"=>"有","0"=>"無");
	var $YN2=array("1"=>"是","0"=>"否");
	//資料表欄位
	var $fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');

	/* 1.建構函式 */
	function __construct($CONN,$smarty){
		$this->CONN=$CONN;
		$this->smarty=$smarty;
	}

	/* 2.初始化一些數值處理函式  */
	function init() {
		//$this->nowClass='401';
		$this->createTB();
	}

	/* 2-0.檢察token碼  */
	function chkToken() {

	}



	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();
		//資料表建立檢查--運作後可以註解掉
		// $this->crTB();
		$myToken=new myToken();

		if ($myToken->check()=='Y'){
			$form_post=pVar('form_act');
			if($form_post=='add') $this->add();
			if($form_post=='out') $this->out();
		}
		/*
		if($form_post=='add') $this->add();
		if($form_post=='update') $this->update();
		if($form_post=='del') $this->del();
		*/
		// 頁面傳來GET
		//$page_get=gVar('form_act');
		//if($page_get=='del') $this->del();

		//隨機驗證碼
		$this->token=$myToken->make();
		//print_r($_SESSION);
		//擷取資料
		$this->all();



		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/2020CDC.htm";
		//$tpl = dirname(__file__)."/sport_res.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		//$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		//$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}

	/* 5.擷取資料給網頁呈現處理函式*/
	function all(){
		//先處理搜尋條件
		$Search_syntax='';//$Search_syntax=$this->Search();
		$str=substr($this->nowClass,0,1);

		//echo '<pre>';print_r($arr);exit;
		$SQL="select  *  from `stud`  where idclass like '{$this->nowClass}%'  order by idclass ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		$this->all=$arr;
		//PP($arr);
			//取分頁資料
		$SQL="select * from `from0331`  where idclass like '{$this->nowClass}%'  order by idclass  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		//if (count($arr)==0) return ;
		$tmp=array();
		$tt2=array();
		foreach($arr as $ar){
			$K=$ar['idclass'];
			$tmp[$K]=$ar;
			if ($ar['pc']=='1') @$tt2['pc']++;
			if ($ar['wifi']=='1') @$tt2['wifi']++;
			if ($ar['tv']=='1') @$tt2['tv']++;
			if ($ar['catv']=='1') @$tt2['catv']++;
			if ($ar['eco']=='1') @$tt2['eco']++;
			}
		$this->Stu=$tmp;//return $arr;
		$tt2['tol']=count($this->all);
		$this->Tol=$tt2;
		//PP($tmp);

	}
	function CK($name,$id){
		if ($this->Stu[$id][$name]=='1') return 'checked';		
	}
	/* 6.新增處理函式 */
	function add(){
	//echo "<pre>";print_r($_POST);die();
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','idclass','stuid','pc','wifi','tv','catv','rco','modify');
		
		foreach ($_POST['stu'] as $K=>$V){
			$K=strip_tags($K);
			$stuid=strip_tags($V);
			$pc=@strip_tags($_POST['StuPC'][$K]);
			$wifi=@strip_tags($_POST['StuNET'][$K]);
			$tv=@strip_tags($_POST['StuTV'][$K]);
			$catv=@strip_tags($_POST['StuCATV'][$K]);
			$eco=@strip_tags($_POST['sssECO'][$K]);
			$modify=date("Y-m-d H:i:s");
			
			//$SQL="INSERT INTO `from0331`(idclass , stuid , pc , wifi , tv , catv , rco , modify)values ('{$K}' ,'{$stuid}' ,'{$pc}' ,'{$wifi}' ,'{$tv}' ,'{$catv}' ,'{$rco}' ,'{$modify}' )";
			$SQL1="INSERT INTO `from0331`(idclass , stuid , pc , wifi , tv , catv , eco , modify)values ('{$K}' ,'{$stuid}' ,'{$pc}' ,'{$wifi}' ,'{$tv}' ,'{$catv}' ,'{$eco}' ,'{$modify}' )";
			$SQL2="update `from0331`  set pc='{$pc}' ,wifi='{$wifi}' ,tv='{$tv}' ,catv='{$catv}' ,eco='{$eco}'  where idclass='{$K}' and stuid='{$stuid}' ";
			$rs=$this->CONN->query($SQL1) or $this->CONN->query($SQL2) ;
		}

		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function out(){
		$Options=getOptions();//PP($Options);
		//$cla=$Options['classA'][$this->nowClass];
		$SQL1="select * from `from0331`  where idclass like '{$this->nowClass}%'  order by idclass  ";
		$SQL2="select * from `from0331`   order by idclass  ";
		$SQL=$SQL1;
		if ($_SESSION['Auth']['title']=='資訊組長') $SQL=$SQL2;
		if ($_SESSION['Auth']['title']=='教務主任') $SQL=$SQL2;
		if ($_SESSION['Auth']['title']=='教導主任') $SQL=$SQL2;
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);//PP($arr);
		$All=array();
	//[block1.pc]	[block1.wifi]	[block1.tv]	[block1.catv]	[block1.ecoA]	[block1.ecoB]
		foreach ($arr as $ar){
			$tmp=substr($ar['idclass'],0,3);
			$A['cla']=@$Options['classA'][$tmp];
			//if (strlen($ar['idclass'])=='6') $A['cla']='特教班';	
			$A['stuid']=$ar['stuid'];
			if ($ar['pc']=='1') {$A['pc']=$ar['pc'];}else{$A['pc']='';}
			if ($ar['wifi']=='1') {$A['wifi']=$ar['wifi'];}else{$A['wifi']='';}
			if ($ar['tv']=='1') {$A['tv']=$ar['tv'];}else{$A['tv']='';}
			if ($ar['catv']=='1') {$A['catv']=$ar['catv'];}else{$A['catv']='';}
			if ($ar['eco']=='1') {$A['ecoN']='';$A['ecoY']='1';}else{$A['ecoN']='1';$A['ecoY']='';}
			$All[]=$A;
			unset($A);
		}
//PP($All);
//print_r();
	include('libs/tinyButStrong.class.php');
	include('libs/tinyDoc.class.php');
  // create the document
	$doc = new tinyDoc();
	$doc->setZipMethod('ziparchive');
  //$doc->setZipMethod('shell');
  //$doc->setZipBinary('zip');
  //$doc->setUnzipBinary('unzip');
  
	$doc->setProcessDir(__SiteData.'odftmp');
	$odf_file= __SitePtah.'libs/調查表範本.ods';
	$doc->createFrom($odf_file);
	$doc->loadXml('content.xml');
	$doc->mergeXmlBlock('block1',$All);
	$doc->saveXml();
	$doc->close();
  // send and remove the document
	$doc->sendResponse($this->nowClass.'班_調查表.ods');
	$doc->remove();
	}

	/* 9.過濾及SQL語法處理函式 */
	function gSQLStr($_action){	}

	/* 	10.尋找資料函式	*/
	function Search(){}

	/* 	11.排序資料函式	*/
	function myBox($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
		if ($ar['sex']=='男') {$S='a';}else{$S='b';}
		$Val=$ar['idclass']."_".$S."_".$ar['stuname'];
		if ($ar['sex']=='男') {$color='blue';}else{$color='red';}
		$show="<font color='".$color."'>".sprintf("%02d",$ar['seatnum']).'</font>.'.$ar['stuname'];
		$str.="<label><input type='checkbox' name='".$name."[".$ar['idclass']."]' value='".$Val."' >".$show."</label>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	/* 	11.排序資料函式	*/
	function createTB(){
		$SQL1="select * from `from0331` limit 1";
		$SQL2="CREATE TABLE `from0331`(
`id` INTEGER PRIMARY KEY,
`idclass` TEXT NOT NULL UNIQUE,
`stuid` TEXT NOT NULL UNIQUE,
`pc`  INTEGER  DEFAULT '0',
`wifi`  INTEGER  DEFAULT '0',
`tv`  INTEGER  DEFAULT '0',
`catv`  INTEGER  DEFAULT '0',
`eco`   INTEGER  DEFAULT '0',
`modify` TEXT )";
		$rs=$this->CONN->query($SQL1);
		if (!$rs) $rs=$this->CONN->query($SQL2);
	}


}
//end class



