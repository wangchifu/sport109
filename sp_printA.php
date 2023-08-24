<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();
login_chk('B');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

// login_chk();
if (sVar('main_id')=='') Header("Location:main.php");
//a.建立物件
$obj= new sport_res($CONN,$smarty);
$obj->Cache=$Cache;
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
		$this->item=gpVar('item');
	}

	/* 3.物件流程函式  */
	function process() {
		//初始化一些數值
		$this->init();
		if ($this->item=='') backe('未傳值！');
		//資料表建立檢查--運作後可以註解掉
		// $this->crTB();

		//建立Token
		$myToken=new myToken();
		//處理表單送出的資料
		if ($myToken->check()=='Y'){
			$form_act=pVar('form_act');
			//if($form_act=='add') $this->add();
			if($form_act=='updateA') $this->updateA();
			if($form_act=='updateB') $this->updateB();
			if($form_act=='delAll') $this->del();
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
		$tpl = __SitePtah."/sp_printA.htm";
		//$tpl = dirname(__file__)."/sport_res.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		// $this->pagehead= __SitePtah."/sport_head.htm";//表頭
		// $this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->user_name = $_SESSION['Auth']['name'];
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}

	function all(){
		$tmp=ItemName();//項目名稱
		$this->Im=$tmp[$this->item];
//		print_r($this->Im);

		//判斷是否接力類
		if ($this->Im['C']['sportkind']=='5') {
			$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='2' ";//$Search_syntax=$this->Search();
		}else{
			$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='0' ";
			}
		//先算總筆數
		$SQL="select  count(id) from `sport_res` $Search_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax=' order by itemid,idclass ';// $Order_syntax=$this->myOrder();

		//取分頁資料
		$SQL="select * from `sport_res` $Search_syntax  and sportorder='0' $Order_syntax  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all['A']=$arr;//return $arr;
		
		//取分頁資料
		$SQL="select * from `sport_res` $Search_syntax  and sportorder!='0' order by sportorder  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->all['B']=$arr;//return $arr;



		//產生連結鈕
//		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
//		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}

	/* 7.更新處理函式  */
	function updateA(){
		//echo "<pre>";print_r($_POST);die();upHand
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		$itemid=pVar('item');
		$tol=pVar('tol');
		if (count($_POST['upStu'])==0) backe('無任何更新資料！');
		foreach ($_POST['upStu'] as $id=>$Num){
			$SQL="update `sport_res` set sportorder='{$Num}' where id='{$id}' and itemid='{$itemid}' ";  
			$rs=$this->CONN->query($SQL) or die($SQL);
		}

		$this->Cache->del('sport_item_all_A');
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$itemid;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function updateB(){
		//echo "<pre>";print_r($_POST);die();upHand
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		$itemid=pVar('item');
		$tol=pVar('tol');
		if (count($_POST['upHand'])==0) backe('無任何更新資料！');
		foreach ($_POST['upHand'] as $id=>$Num){
			$SQL="update `sport_res` set sportorder='{$Num}' where id='{$id}' and itemid='{$itemid}' ";  
			$rs=$this->CONN->query($SQL) or die($SQL);
		}
		$this->Cache->del('sport_item_all_A');
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$itemid;
		Header("Location:$URL");
	}

	/* 8.刪除處理函式  */
	function del(){
		//Auth();//簡易認證
		/* 設定刪除語法； 可於這裡進行其他安全性或額外處理*/
		$itemid=pVar('item');
		$tol=pVar('tol');
		$SQL="update `sport_res` set sportorder='0' where itemid='{$itemid}' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$itemid;
		Header("Location:$URL");
	}

	function myBox2($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		$Val=$ar['idclass'].$ar['cname'];
		$str.=$Val."<input class=bt2 type='text' size=1 name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."'>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	function myBoxK52($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		$Val=$ar['cname'].'班_'.$ar['kgp'];
		$str.=$Val."<input class=bt2 type='text' size=1 name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."'>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	function myBox3($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;$j=1;
		$str='';
		$GP=1;
		foreach($stu as $ar){
			if ($i%$N==1){
				$URL="sp_printA.php?item=".$this->item."&gp=".$GP;
				$str.="◎第".$GP."組：--<span onclick=\"window.open('".$URL."', '_blabk', config='height=300,width=400');\">";
				$str.="〈列印第".$GP."組〉</span><br>\n";
				$GP++;$j=1;
				}
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			$Val="道次".$j.': '.$ar['idclass'].$ar['cname'];
			$str.="<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."' size=2>".$Val."<br>\n";
			$i++;$j++;
		}
		return $str;
	}

	function myBoxK53($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;$j=1;
		$str='';
		$GP=1;
		foreach($stu as $ar){
			if ($i%$N==1){
				$str.="◎第".$GP."組：<br>\n";
				$GP++;$j=1;
				}
			$K=$ar['idclass'];
			$Val="道次".$j.': '.$ar['cname'].'班_'.$ar['kgp'];
			$str.="<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."' size=2>".$Val."<br>\n";
			$i++;$j++;
		}
		return $str;
	}


}
//end class



