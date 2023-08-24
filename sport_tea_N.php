<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

login_chk();
if (sVar('main_id')=='') Header("Location:main.php");

$cla=$_SESSION['Auth']['cla'];
//$cla='5_2';
if ($cla=='') backe('限導師操作！');
if ($_SESSION['main_ary']['sign']=='N') backe('目前無法報名！');

// echo $cla;

//a.建立物件
$obj= new sport_res($CONN,$smarty);
$obj->Cache=$Cache;
$obj->Mid=sVar('main_id');
$obj->midAry=$_SESSION['main_ary'];//主項目
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
	var $itemkind=array("1"=>"初賽","2"=>"決賽","3"=>"不分");//關連到sport_item.kind 
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
		$page=gpVar('page');
		$this->page=($page=='') ? 0:(int)$page;
		$this->Mid=sVar('main_id');
		$this->midAry=$_SESSION['main_ary'];
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
			if ($form_post=='updateA') $this->updateA();
			if ($form_post=='updateB') $this->updateB();
			
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
		$tpl = __SitePtah."/sport_tea_N.htm";
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
		$str=substr($this->nowClass,0,1);
		//可報名項目
		$SQL="select  *  from `sport_item`  where enterclass like '$str%'  and skind='0' and mid='{$this->Mid}' and sportkind='5' order by id ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		//PP($arr);
		$this->Item=$arr;//return $arr;
		$this->ItemTol=count($arr);
		//echo '<pre>';print_r($arr);exit;
		$SQL="select  *  from `stud`  where idclass like '{$this->nowClass}%'  order by idclass ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$A=array();
		foreach ($arr as $ar){
			$K=$ar['idclass'];
			$A[$K]=$ar;
		}
		$this->Stu=$A;
		

		//$this->Stu2=ckBox('stu',$A,5,'');

		//取得系統選項
		$this->Options=getOptions();
		//PP($this->Options);

		$SQL="select * from `sport_res` where idclass like '{$this->nowClass}%' and mid='{$this->Mid}' and kmaster!='2' group by idclass order by idclass ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->AddStu=$arr;

		$SQL="select * from `sport_res` where idclass like '{$this->nowClass}%' and mid='{$this->Mid}' and kmaster!='2' and sportkind='5' order by sportorder,idclass ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$tmp=array();
		foreach ($arr as $ary){
			$K=$ary['itemid'];
			$tmp[$K][]=$ary;			
		}
		$this->Stu5=$tmp;
		//PP($tmp);

	}



	/* 7.更新處理函式  */
	function updateA(){
		//Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');

		if (count($_POST['upNum'])==0) backe('未填寫！');
		foreach ($_POST['upNum'] as $idclass=>$sportnum){
			$SQL="update `sport_res`  set sportnum='{$sportnum}'  where mid='{$this->Mid}' and idclass='{$idclass}' ";
			$rs=$this->CONN->query($SQL) or die($SQL);
		}
		
		$this->Cache->del('sport_item_all_A');	
		
		//$SQL="update `{$this->TB}`  set id='{$id}' ,mid='{$mid}' ,itemid='{$itemid}' ,kmaster='{$kmaster}' ,kgp='{$kgp}' ,kend='{$kend}' ,stud_id='{$stud_id}' ,sportkind='{$sportkind}' ,cname='{$cname}' ,idclass='{$idclass}' ,sportnum='{$sportnum}' ,num='{$num}' ,results='{$results}' ,sportorder='{$sportorder}' ,memo='{$memo}' ,passOK='{$passOK}'  where id='{$id}' ";
		$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function updateB(){
		//Auth();//簡易認證
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');

		if (count($_POST['upOrd'])==0) backe('未填寫！');
		foreach ($_POST['upOrd'] as $id=>$val){
			$tmp=explode('-',$val);
			if (count($tmp)!=2)  backe('填寫格式錯誤1！');
			if ($tmp[0]==0 || $tmp[1]==0) backe('填寫格式錯誤2！');
			$SQL="update `sport_res`  set  kgp='{$tmp[0]}' ,sportorder='{$tmp[1]}'  where mid='{$this->Mid}' and id='{$id}' ";
			$rs=$this->CONN->query($SQL) or die($SQL);
		}
		
		$this->Cache->del('sport_item_all_A');	
		
		//$SQL="update `{$this->TB}`  set id='{$id}' ,mid='{$mid}' ,itemid='{$itemid}' ,kmaster='{$kmaster}' ,kgp='{$kgp}' ,kend='{$kend}' ,stud_id='{$stud_id}' ,sportkind='{$sportkind}' ,cname='{$cname}' ,idclass='{$idclass}' ,sportnum='{$sportnum}' ,num='{$num}' ,results='{$results}' ,sportorder='{$sportorder}' ,memo='{$memo}' ,passOK='{$passOK}'  where id='{$id}' ";
		$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
	}



	/* 9.過濾及SQL語法處理函式 */
	function gSQLStr($_action){	}

	/* 	10.尋找資料函式	*/
	function Search(){}


	/* 	填寫盒函式	*/
	function myBox2($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			if ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		$Val="<font color=".$color.">".substr($ar['idclass'],3,2)."</font>.".$ar['cname'];
		$str.=$Val."<input type='text' name='".$name."[".$K."]' value='".$ar['sportnum']."' size=2 class=bt1>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	/* 	填寫盒函式	*/
	function myBox5($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		//PP($stu);
		foreach($stu as $ar){
			//$K1=$ar['id'];
			$K=$ar['idclass'];
			if ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		$Val="<font color=".$color.">".substr($ar['idclass'],3,2)."</font>.".$ar['cname'];
		$str.=$Val."<input type='text' name='".$name."[".$ar['id']."]' value='".$ar['kgp'].'-'.$ar['sportorder']."' size=2 class=bt1>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}




}
//end class



