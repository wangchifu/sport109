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
if ($_SESSION['main_ary']['work']=='N') backe('目前無法操作！');
//PP($_SESSION);
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
			if($form_act=='printA1') {$this->printA1();exit;}
			if($form_act=='printA2L') {$this->printA2('L');exit;}
			if($form_act=='printA2H') {$this->printA2('H');exit;}
		}
		//隨機驗證碼
		$this->token=$myToken->make();

		//$A=$this->getNum(23,6,2);
		//print_r($A);
		//擷取資料
		$this->all();

		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/sp_orderA.htm";
		//$tpl = dirname(__file__)."/sport_res.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->user_name = $_SESSION['Auth']['name'];
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}
	function printA1(){
		$tmp=ItemName();//項目名稱
		$Im=$tmp[$this->item];
		if ($Im['C']['sportkind']=='2') backe('本表僅印競賽類！');
		//判斷是否接力類
		if ($Im['C']['sportkind']=='5') {
			$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='2' ";//$Search_syntax=$this->Search();
		}else{
			$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='0' ";
			}
		$SQL="select  count(id) from `sport_res` $Search_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		//總筆數
		$tolA=$rs->fetchColumn(); 
		

		$GP=pVar('GP');//第幾組
		$add=$this->getNum($tolA,$Im['C']['playera'],$GP);
		$Search_syntax.=" and sportorder in ({$add}) ";

		$SQL="select * from `sport_res` $Search_syntax  order by sportorder  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$tol=count($arr);//echo $tol;
		$im=array();
		//主項目標題
		$im['title']=$_SESSION['main_ary']['title'];
		$im['A']=$Im['A'].' 第'.$GP.'組';
		$im['D']=$Im['C']['sporttime'];
		$im['B']="共".ceil($tolA/$Im['C']['playera'])."組，每組".$Im['C']['playera']."人， 錄取".$Im['C']['passera']."名 。";		
		$im['C']=$Im['C']['imemo'];
		//PP($im);
		//取得系統各種選項
		$Options=getOptions();//PP($Options);
		//跑道數,資料陣列
		$All=$this->getNum2($Options['Oth']['runNum'],$arr);

		
		/*載入函式*/
		include(__SitePtah.'libs/tinyButStrong.class.php');
		include(__SitePtah.'libs/tinyDoc.class.php');
		$doc = new tinyDoc();
		$doc->setZipMethod('ziparchive');
		$doc->setProcessDir(__SiteData.'odftmp/');
		//$odf_file= __SitePtah."mods/".$this->Mod."/ex6.odt";
		//if ($Im['C']['sportkind']=='2')	//$odf_file= __SitePtah."libs/田賽檢錄表.odt";
		//$odf_file= __SitePtah."libs/競賽檢錄表.odt";
		
		$odf_file= __SitePtah."libs/競賽檢錄表.odt";
		$doc->createFrom($odf_file);
		$doc->loadXml('content.xml');
		/*合併資料*/
		$doc->mergeXmlField('Im',$im);
		//$doc->mergeXmlField('Im1',$im);
		// $doc->mergeXmlBlock('Im',$im);
		$doc->mergeXmlBlock('block1',$All);
		//$doc->mergeXmlField('block2',$All);
		$doc->mergeXmlBlock('block2',$All);
		$doc->saveXml();
		$doc->close();
		/*輸出檔名*/
		$doc->sendResponse((date("Y")-1911).'年'.$Im['A'].'競賽類檢錄記錄表.odt');
		$doc->remove();	
	}

	function printA2($sKind){
		$tmp=ItemName();//項目名稱
		$Im=$tmp[$this->item];
		if ($Im['C']['sportkind']!='2') backe('僅田賽類表格！');
		$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='0' ";
		//$GP=pVar('GP');//第幾組
		$SQL="select * from `sport_res` $Search_syntax  order by sportorder  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$tol=count($arr);
		$im=array();
		//主項目標題
		$im['title']=$_SESSION['main_ary']['title'];
		$im['A']=$Im['A'].' 全1組';
		$im['D']=$Im['C']['sporttime'];
		$im['B']="共".ceil($tol/$Im['C']['playera'])."組，每組".$Im['C']['playera']."人， 錄取".$Im['C']['passera']."名 。";		
		$im['C']=$Im['C']['imemo'];

		$No=1;
		foreach ($arr as $ary){
			$cla=substr($ary['idclass'],0,3);
			//道次,單位,號碼,姓名,成績記錄 ,名次,備註
			$ary['No']=$No;
			$ary['cla']=$cla;
			if ($ary['num']==0) $ary['num']='';
			$All[]=$ary;
			$No++;	
		}

		
		/*載入函式*/
		include(__SitePtah.'libs/tinyButStrong.class.php');
		include(__SitePtah.'libs/tinyDoc.class.php');
		$doc = new tinyDoc();
		$doc->setZipMethod('ziparchive');
		$doc->setProcessDir(__SiteData.'odftmp/');
		//$odf_file= __SitePtah."mods/".$this->Mod."/ex6.odt";
		if ($Im['C']['sportkind']=='2')	//$odf_file= __SitePtah."libs/田賽檢錄表.odt";
		//$odf_file= __SitePtah."libs/競賽檢錄表.odt";
		
		if ($sKind=='L') {
			$odf_file= __SitePtah."libs/跳遠L_檢錄表.odt";
			$out_Name=(date("Y")-1911).'年'.$Im['A'].'_檢錄記錄表.odt';
			}
		if ($sKind=='H') {
			$odf_file= __SitePtah."libs/跳高H3_檢錄表.odt";
			$out_Name=(date("Y")-1911).'年'.$Im['A'].'_檢錄記錄表.odt';
			}
		
		$doc->createFrom($odf_file);
		$doc->loadXml('content.xml');
		/*合併資料*/
		$doc->mergeXmlField('Im',$im);
		//$doc->mergeXmlField('Im1',$im);
		// $doc->mergeXmlBlock('Im',$im);
		$doc->mergeXmlBlock('block1',$All);
		//$doc->mergeXmlField('block2',$All);
		//$doc->mergeXmlBlock('block2',$All);
		$doc->saveXml();
		$doc->close();
		/*輸出檔名*/
		//$doc->sendResponse((date("Y")-1911).'年'.$Im['A'].'田賽類檢錄記錄表.odt');
		$doc->sendResponse($out_Name);		
		//$doc->sendResponse((date("Y")-1911).'競賽類檢錄記錄表.odt');
		$doc->remove();	
	}

	
	/* 跑道數，人員陣列 -- 回傳列印的陣列*/
function getNum2($A,$arr){
	$No=1;
	$tol=count($arr);
	$All=array();
	$st=floor(($A-$tol)/2);
	if ($st>0){
		$ary=array();
	for($i=0;$i<$st;$i++){
		$ary['No']=$No;
		$ary['cla']='';$ary['num']='';$ary['idclass']='';$ary['sportnum']='';$ary['cname']='';$ary['results']='';
		$All[]=	$ary;
		$No++;
		}	
	}	
	foreach ($arr as $ary){
		$cla=substr($ary['idclass'],0,3);
		//道次,單位,號碼,姓名,成績記錄 ,名次,備註
		$ary['No']=$No;
		$ary['cla']=$cla;
		if ($ary['num']==0) $ary['num']='';
		$All[]=$ary;
		$No++;	
	}
	if ($No==($A+1)) {return $All;}
	else{
		$ary=array();
		for($i=$No;$i<($A+1);$i++){
			$ary['No']=$No;
			$ary['cla']='';$ary['num']='';$ary['idclass']='';$ary['sportnum']='';$ary['cname']='';$ary['results']='';
			$All[]=	$ary;
			$No++;
			}	
		}
	return $All;
}


/* 全部人數,每組人數,第幾組 */
	function getNum($tol,$one,$num){
		$Ag=ceil($tol/$one);
		$tmp=array();
		$N=1;
		for($i=0;$i<$Ag;$i++){
			$N=$i+1;
			$st=($i*$one)+1;
			$end=($i*$one) +$one;
			$tmp[$N]=range($st,$end);			
		}
		return join(',',$tmp[$num]);
	}


	function all(){
		$tmp=ItemName();//項目名稱
		$this->Im=$tmp[$this->item];


		//print_r($this->Im);

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
		$tmp=ItemName();//項目名稱
		$Im=$tmp[$itemid];//pp($Im);
		$addSQL='';
		if ($Im['C']['sportkind']=='5') $addSQL=" and kmaster='2' ";
		
		$SQL="update `sport_res` set sportorder='0' where itemid='{$itemid}' $addSQL ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$itemid;
		Header("Location:$URL");
	}

	function myBox1a($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		//$Val=$ar['idclass'].$ar['cname'];
		$Val=$ar['sportnum'].'_'.$ar['cname'];
		$str.=$Val."<input class=bt2 type='text' size=1 name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."'>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	function myBoxK5a($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		//$Val=$ar['cname'].'班'.$ar['kgp'];
		$Val=$ar['cname'];
		$str.=$Val."<input class=bt2 type='text' size=1 name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."'>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}
	function myBox1b($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;$j=1;
		$str='';
		$GP=1;
		foreach($stu as $ar){
			if ($i%$N==1){
				//$URL="sp_printA.php?item=".$this->item."&gp=".$GP;
				$str.="◎第".$GP."組：\n";
				$str.="<INPUT TYPE='button' class='bt1' value='印出第".$GP."組檢錄單' onclick=\"if( window.confirm('確定列印？確定？')){this.form.GP.value='".$GP."';this.form.form_act.value='printA1';this.form.submit()}\"><br>";
				//--<span onclick=\"window.open('".$URL."', '_blabk', config='height=300,width=400');\">";
				//$str.="〈列印第".$GP."組〉</span><br>\n";
				$GP++;$j=1;
				}
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			// $Val="道次".$j.': '.$ar['idclass'].$ar['cname'];
			$Val="道次".$j.': '.$ar['sportnum'].'_'.$ar['cname'];
			$str.="<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."' size=2>".$Val."<br>\n";
			$i++;$j++;
		}
		return $str;
	}
	function myBox2b($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;$j=1;
		$str='';
		$GP=1;
		foreach($stu as $ar){
			if ($i%$N==1){
				//$URL="sp_printA.php?item=".$this->item."&gp=".$GP;
				$str.="◎第".$GP."組：<br>\n";
				
				//--<span onclick=\"window.open('".$URL."', '_blabk', config='height=300,width=400');\">";
				//$str.="〈列印第".$GP."組〉</span><br>\n";
				$GP++;$j=1;
				}
			$K=$ar['idclass'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			// $Val="順序".$j.': '.$ar['idclass'].$ar['cname'];
			$Val="順序".$j.': '.$ar['sportnum'].'_'.$ar['cname'];
			$str.="<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."' size=2>".$Val."<br>\n";
			$i++;$j++;
		}
		return $str;
	}

	function myBoxK5b($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;$j=1;
		$str='';
		$GP=1;
		foreach($stu as $ar){
			if ($i%$N==1){
				$str.="◎第".$GP."組：<br>\n";
				$str.="<INPUT TYPE='button' class='bt1' value='印出第".$GP."組檢錄單' onclick=\"if( window.confirm('確定列印？確定？')){this.form.GP.value='".$GP."';this.form.form_act.value='printA1';this.form.submit()}\"><br>";
				$GP++;$j=1;
				}
			$K=$ar['idclass'];
			//$Val="道次".$j.': '.$ar['cname'].' 班_'.$ar['kgp'];
			$Val="道次".$j.': '.$ar['cname'];
			$str.="<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."' size=2>".$Val."<br>\n";
			$i++;$j++;
		}
		return $str;
	}

	function ItemLink(){
		$Link='';
		//決賽
		if ($this->Im['C']['skind']=='0' && $this->Im['Next']==''){
		$Link="■<a href='sp_orderA.php?item=".$this->item."'>決賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->item."'>□決賽成績</a>";
		}
		//初決賽的--初賽
		if ($this->Im['C']['skind']=='0' && $this->Im['Next']!=''){
		$Link="■<a href='sp_orderA.php?item=".$this->item."'>初賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->item."'>□初賽成績</a>
		<a href='sp_orderA.php?item=".$this->Im['Next']."'>□決賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->Im['Next']."'>□決賽成績</a>";
		}
		//初賽後的--決賽
		if ($this->Im['C']['skind']!='0' && $this->Im['Next']==''){
		$Link="<a href='sp_orderA.php?item=".$this->Im['C']['skind']."'>□初賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->Im['C']['skind']."'>□初賽成績</a>
		■<a href='sp_orderA.php?item=".$this->item."'>決賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->item."'>□決賽成績</a>";
		}
		return $Link;
	}

}
//end class



