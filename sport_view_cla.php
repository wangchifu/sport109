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
res,res
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
/*
		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			
			if ($formAct=='APrint') $this->add();
			if ($formAct=='update') $this->update();
			if ($formAct=='del')	$this->del();
		}
		//隨機驗證碼
		$this->token=$myToken->make();
*/
		$formAct=pVar('form_act');
		if ($formAct=='APrint') $this->add();

		//擷取資料
		$this->all();

		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/sport_view_cla.htm";
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
		$this->Im=ItemName();//全部項目名稱
$this->Options=getOptions();//$Options['Oth']['runNum'];
//PP($this->Options);
		//處理排序依據
		$Order_syntax='order by a.sportkind,a.enterclass ';// $Order_syntax=$this->myOrder();

		/* 取初賽或直接決賽的名單 */
		$SQL="select a.id,count(*) as btol from sport_item a ,sport_res b  
		where a.id=b.itemid  and a.skind='0' group by a.id $Order_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		$this->all=$this->Full_TD($arr,4);//return $arr;
		//PP($this->Im);
	}

	/* 6.新增處理函式 */
	function add(){
//echo "<pre>";print_r($_POST);die();	
		if (@count($_POST['cla'])==0) backe('請選擇輸出班級！');
		$tmp=array();
		foreach ($_POST['cla'] as $K=>$V){
			if ($K!=$V) backe('資料錯誤！');
			$tmp[]=$K;
		}
		$IM="'".join("','",$tmp)."'";
		$Order_syntax='order by idclass,itemid ,sportorder ';
		$SQL="select * from `sport_res`  where mid='{$this->Mid}' and 
		substr(idclass,1,3) in ($IM) and kmaster!='2' $Order_syntax  ";
		//echo $SQL;
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll();

		$tmp=array();
		foreach ($arr as $ar){
			if ($ar['sportkind']=='5' && $ar['kmaster']=='2') continue;
			$K=substr($ar['idclass'],0,3);
			$tmp[$K][]=$ar;
		}
		//PP($tmp);

		$this->all=$tmp;
		$this->Im=ItemName();//全部項目名稱
		$this->Options=getOptions();//$Options['Oth']['runNum'];
		
		$tpl = __SitePtah."/sport_view_P2.htm";
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
		//PP($tmp);
		// echo "<pre>";print_r($_POST);die();	
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		
exit;
		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?page=".$this->page;
		Header("Location:$URL");
	}

	/* 	11.排序資料函式	*/
	function myBox($stu,$N){
		if (!is_array($stu)) return ;
		$i=1;
		$str='';
		foreach($stu as $ar){
			//$str.=$ar['idclass'].$ar['cname'];
			//$j2=sprintf("%02d",$ar['sportorder']);
			$im=$ar['itemid'];
			if ($im=='0') {$str.=$ar['sportnum'].$ar['cname'].'(隊長) ';}
			else {$str.=$ar['sportnum'].$ar['cname'].'('.$this->Im[$im]['A3'].') ';}
			if ($i%$N==0)$str.="<br>\n";
			$i++;
		}
		return "<div>".$str."</div>";
	}

	function myBoxV2($stu,$IM){
		if (!is_array($stu)) return ;
		$N=$this->Im[$IM]['C']['playera'];//每組人數
		$runNum=$this->Options['Oth']['runNum'];
		//BY如果是B，就輸出道次
		if ($this->Options['Oth']['BY']=='B'){
			$offset=floor(($runNum-$N)/2);//偏移量
		} else {$offset=0;}
		
		//if ($offset>0)echo $offset;//exit;}
		//sportorder
		//echo $G;exit;
		
		$Tol=count($stu);
		$Max=(ceil($Tol/$N))*$N;//最多人數
		// echo $Max;exit;
		$SS=array();
		foreach($stu as $ar){$K=$ar['sportorder'];$SS[$K]=$ar;}
		$str='';$i=1;$j=1;$GP=1;
		for($i=1;$i<=$Max;$i++){
			if ($i%$N==1){$str.="◎第".$GP."組：\n";$GP++;$j=1;}
			if (isset($SS[$i])){
				$j2=sprintf("%02d",$j+$offset);//加入偏移量
				//$j2=sprintf("%02d",$j);//不加入偏移量
				$str.=$j2.':'.$SS[$i]['sportnum'].$SS[$i]['cname'].' ';
			}
			if ($i%$N==0)$str.="<br>\n";
			$j++;
		}
		return "<div>".$str."</div>";
	}

/* 滿格函式v2 (陣列，一列幾筆)*/
function Full_TD($data,$num) {
	$all=count($data);
	$loop=ceil($all/$num);
	$flag=$num-1;
	$all_td=($loop*$num)-1;//最大值小1
	$show=array();$i=0;
	foreach ($data as $key=>$ary ){
		(($i%$num)==$flag && $i!=0 && $i!=$all_td ) ? $ary['next_line']='yes':$ary['next_line']='';
		$show[$key]=$ary;
		$i++;
		}
	for ($i;$i<=$all_td;$i++){
		$key='Add_Td_'.$i;
		(($i%$num)==$flag && $i!=0 && $i!=$all_td ) ? $show[$key]['next_line']='yes':$show[$key]['next_line']='';
		}

		//return $show;
		//PP($show);
		
}


}
//end class



