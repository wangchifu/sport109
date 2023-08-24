<?php

include_once "109.conf.php";
include_once "db_connect.php";

//if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();

login_chk('A');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

// login_chk();
if (sVar('main_id')=='') Header("Location:main.php");
if ($_SESSION['main_ary']['work']=='N') backe('目前無法操作！');


// echo $cla;

//a.建立物件
$obj= new sport_res($CONN,$smarty);
$obj->Cache=$Cache;
$obj->Mid=sVar('main_id');
$obj->midAry=$_SESSION['main_ary'];//主項目
//$obj->nowClass=change_class($cla);

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
		$this->nowClass=gpVar('nClass');
		$this->page=($page=='') ? 0:(int)$page;
		//$this->Mid=sVar('main_id');
		//$this->midAry=$_SESSION['main_ary'];
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
			if($form_post=='update') $this->update();
			if($form_post=='del') $this->del();
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
		$tpl = __SitePtah."/sport_ad_tea.htm";
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
		//取得系統各種選項
		$this->Options=getOptions();
		//PP($this->Options);
	

		if ($this->nowClass=='') return ;
		$Search_syntax='';//$Search_syntax=$this->Search();
		$str=substr($this->nowClass,0,1);
		//可報名項目
		$SQL="select  *  from `sport_item`  where enterclass like '$str%'  and skind='0' and mid='{$this->Mid}' order by id ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->Item=$arr;//return $arr;
		$this->ItemTol=count($arr);
		$this->AIm=ItemName();//全部項目
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
		
		
		foreach ($arr as $ar){
			if ($ar['sex']=='男') $K=$ar['idclass']."_a";
			if ($ar['sex']=='女') $K=$ar['idclass']."_b";			
			$A[$K]=$ar['seatnum'].$ar['stuname'];
			}
		//$this->Stu2=ckBox('stu',$A,5,'');
		//去除接力賽程班級單
		$SQL="select * from `sport_res` where idclass like '{$this->nowClass}%' and mid='{$this->Mid}' and kmaster!='2' order by idclass ,itemid ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$this->okStu=$arr;
		if (count($arr)!=0){
			$A=array();
			foreach($arr as $ar){$IM=$ar['itemid'];	$A[$IM][]=$ar;}
			$this->AddStu=$A;
			
		}

	}

	/* 6.新增處理函式 */
	function add(){
		if ($this->nowClass=='') backe('沒有處理的班級！');
		// $URL=$_SERVER['SCRIPT_NAME'];
		$URL=$_SERVER['SCRIPT_NAME'].'?nClass='.$this->nowClass;
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		/*取出項目資料*/
		$tmpItem=pVar('item');
		$mid=1;
		//////1.處理隊長
		if ($tmpItem=='all'){
			if (count($_POST['stu'])!=1) backe('隊長限一人！');
			list($idclass, $str) = each($_POST['stu']);
			$SS=explode('_',$str);
			$cname=$SS[2];
			//echo key($_POST['stu']);
			// echo array_values($_POST['stu']);
			//list $_POST['stu']=
			$in_sql="INSERT INTO sport_res(mid,kmaster,cname,idclass,memo) VALUES ('{$this->Mid}','1','$cname','{$idclass}','隊長')";
			$rs=$this->CONN->query($in_sql) or  backe('隊長限一人！');
			Header("Location:$URL");
			exit;
		}
		//拆開-- 項目 ,類別
		if ($tmpItem=='') backe('未選項目！');
		$SS=explode('_',$tmpItem);$item=$SS[0];$sportkind=$SS[1];
		if ($item=='' ||$sportkind=='') backe('項目錯誤！');
		//echo "<pre>";print_r($_POST);die();
		if (count($_POST['stu'])==0) backe('沒有選擇學生！');
		if ($item=='' || $item==0) backe('沒有選擇項目！');

		$SQL="select * from `sport_item`  where id='$item' and mid='{$this->Mid}' ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();
		$Im=$arr[0];
		if ($Im['sportkind']!=$sportkind) backe('項目錯誤不符！');

		/* 處理接力類 //主項,項目,可報組數,班級 */
		if ($sportkind=='5')  AddKind5($this->Mid,$item,$Im['kgp'],$this->nowClass);
		
		//$this->AddK5($item,$Im['kgp'],$this->nowClass);
	//$fields=array('id','mid','itemid', 'kmaster','kgp','kend','stud_id','sportkind', 'cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		
		foreach ($_POST['stu'] as $K=>$str){
			$SS=explode('_',$str);
			$cname=$SS[2];
			$CK=substr($K,0,1).$SS[1];
			$Im_CK=substr($Im['enterclass'],1,1);
			// 非男女分組時1c,2c,3c..時，要檢查分組
			if ($Im_CK!='c' && $Im['enterclass']!=$CK) backe($Im['enterclass'].'報名組別錯誤！'.$CK);

			$in_sql="INSERT INTO sport_res(mid , itemid ,sportkind , cname , idclass ) VALUES ('{$this->Mid}','$item','{$sportkind}', '$cname','$K')";
			//$up_sql="update sport_item set  res=res+1  where id='$item' ";
			$rs=$this->CONN->query($in_sql) or backe('不可以重複報名！');//die($in_sql);
			//$rs=$this->CONN->query($up_sql) or die($up_sql);		
		}
		
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		$this->Cache->del('sport_item_all_A');
		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();

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
		$SQL="update `{$this->TB}` set ".join(" , ",$SQL)." where id='$id' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}
	/* 8.刪除處理函式  */
	function del(){
		if ($this->nowClass=='') backe('沒有處理的班級！');
		$URL=$_SERVER['SCRIPT_NAME'].'?nClass='.$this->nowClass;		
		//echo "<pre>";print_r($_POST);die();
		//$URL=$_SERVER['SCRIPT_NAME'];
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		/*取出項目資料*/
		//$tmpItem=pVar('item');
		//$mid=1;
		if (!isset($_POST['delStu'])) backe('未選學生！');
		if (count($_POST['delStu'])==0) backe('未選學生！');
		$tmp=array();
		foreach ($_POST['delStu'] as $K=>$V){$tmp[]=$K;}
		$ID=join(',',$tmp);
		$SQL="Delete from  `sport_res`  where  id in ($ID) ";
		$rs=$this->CONN->query($SQL) or die($SQL);

		$this->Cache->del('sport_item_all_A');
		//$URL=$_SERVER['SCRIPT_NAME'];
		Header("Location:$URL");
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
	function myBox2($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			$K=$ar['idclass'];
			if ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		$Val="<font color=".$color.">".substr($ar['idclass'],3,2)."</font>.".$ar['cname'];
		$str.="<label><input type='checkbox' name='".$name."[".$ar['id']."]' value='".$Val."' >".$Val."</label>\n";
		if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}







}
//end class



