<?php

include_once "109.conf.php";
include_once "db_connect.php";
// if (allowDomain()=='N' && ipAllow($PassIP)!='Y') backe("！不在授權使用範圍內！");

//如果整個程式都須認證,請拿開下行註解
//Auth();//簡易認證
// session_start();
login_chk('C');//A報名操作,B檢錄工作,C成績輸入,D項目管理,E帳號管理,F系統設定,All全部權限

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
			if($form_act=='addNext') $this->add();
			if($form_act=='update') $this->update();
			
			if($form_act=='delAll') $this->del();
		}
		//隨機驗證碼
		$this->token=$myToken->make();

		//所有項目名稱
		$this->Im_ALL=ItemName();
		$this->Im=$this->Im_ALL[$this->item];
		//擷取資料
		$this->all();

		//顯示畫面
		$this->display();
	}
	/* 4.顯示畫面處理函式*/
	function display(){
		$tpl = __SitePtah."/sp_writeA.htm";
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


		//PP($this->Im);
		
		//先處理搜尋條件
		$Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='0' ";//$Search_syntax=$this->Search();
		if ($this->Im['C']['sportkind']=='5') $Search_syntax=" where mid='{$this->Mid}'  and itemid='{$this->item}' and kmaster='2' ";
		

		//先算總筆數
		$SQL="select  count(id) from `sport_res` $Search_syntax ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		// $this->tol=$rs->rowCount();
		$this->tol=$rs->fetchColumn(); 

		//處理排序依據
		$Order_syntax=' order by sportorder,idclass ';// $Order_syntax=$this->myOrder();
		//if ($this->Im['C']['sportkind']=='2') $Order_syntax=' order by sportorder,idclass ';

		//取分頁資料
		$SQL="select * from `sport_res` $Search_syntax  $Order_syntax  ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		$tmp=array();$tmp2=array();$tmpE=array();
		foreach($arr as $ar){
			$K=$ar['id'];
			//如果田賽要逆向排列
			$Kb=$ar['num'];			
			if ($this->Im['C']['sportkind']=='2') $Kb=99-$ar['num'];
			$ar['k2']=$ar['results'].'_'.$Kb.'_'.sprintf("%02d",$ar['sportorder']).'_'.sprintf("%08d",$ar['id']);//num
			$tmp[$K]=$ar;
			$tmp2[$K]=$ar['k2'];
			// if ($ar['results']==''){$tmpE[$K]=$ar['k2'];}else{$tmp2[$K]=$ar['k2'];}
			
		}
		$this->all=$tmp;//return $arr;
		
		//echo '<pre>';print_r($tmp2);
		/*依成績由小到大的排序，或依大而小 */
		if ($this->Im['C']['sportkind']=='2') { arsort($tmp2);}
		else {asort($tmp2);}
		
		//if (count($tmpE)>0) $tmp2=array_merge($tmp2,$tmpE);
		$this->P1=$tmp2;
		//$this->P1=array_merge($tmp2,$tmpE);
		//echo '<pre>';print_r($tmp2);

		//所有項目名稱
//		$tmp=ItemName();
//		$this->Im=$tmp[$this->item];
		
		if ($this->Im['Next']=='') return;
		$m2=$this->Im['Next'];
		$this->Im2=$this->Im_ALL[$m2];
		$this->Next=$this->getNext();
		//}
		//產生連結鈕
//		$URL=$_SERVER['SCRIPT_NAME'];//不含page的網址
//		$this->links=new Chi_Page($this->tol,$this->size,$this->page,$URL);
	}
function getNext(){
		$im=$this->Im['Next'];
		$SQL="select * from `sport_res`  where mid='{$this->Mid}'  and itemid='{$im}' order by sportorder,idclass ";
		$rs=$this->CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		if (count($arr)==0) return ;//backe('找不到資料');
		$tmp=array();
		foreach($arr as $ar){$K=$ar['id'];$tmp[$K]=$ar;}
		return $tmp;
		
}
	/* 只有複賽---的新增(算是決賽報名)函式 */
	function add(){
		//$all_Item=ItemName();//項目名稱
		
		$item=pVar('item');
		$Nitem=pVar('Nitem');
		$sportkind=pVar('sportkind');
		if ($item=='' ||$item=='0') backe('資料錯誤！');
		if ($Nitem=='' ||$Nitem=='0') backe('資料錯誤！');		
		if ($sportkind=='' ||$sportkind=='0') backe('資料錯誤！');

		//取得系統各種選項
		$Options=getOptions();//PP($Options);
		$runNumAry=get_num_out($Options['Oth']['runNum']);
		//PP($runNumAry);
		
		//$Im=$all_Item[$item];
		//$Next_id=$Im['Next'];
		// PP($_POST);
		//echo "<pre>";print_r($_POST);die();
		//Auth();//簡易認證
		//對POST的資料額外處理,請自行修訂符合自己須求
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		if (count($_POST['addStu'])==0) backe('未選學生！');
		$i=0;
		foreach ($_POST['addStu'] as $K =>$V){
			$Tm=explode('_',$V);
			$idclass=$Tm[1];
			$cname=$Tm[2];
			$sportnum=$Tm[3];
			$sportorder=$runNumAry[$i];//道次
			if ($cname=='' ||$idclass=='' ) continue;
			$in_sql="INSERT INTO sport_res(mid , itemid ,sportkind , cname , idclass,sportnum,sportorder ) VALUES ('{$this->Mid}','{$Nitem}','{$sportkind}', '{$cname}','{$idclass}','{$sportnum}' ,'{$sportorder}')";
			$up_sql="update sport_res set sportorder='{$sportorder}' ,sportnum='{$sportnum}'  where mid='{$this->Mid}' and itemid='{$Nitem}' and idclass='{$idclass}'   ";
			$rs=$this->CONN->query($in_sql);//or backe('不可以重複報名！');//die($in_sql);
			if (!$rs) $this->CONN->query($up_sql);
			$i++;
		}
		$this->Cache->del('sport_item_all_A');
		/* 取出新增語法； 可於這裡進行其他安全性或額外處理*/
		//$SQL="INSERT INTO `{$this->TB}`(mid , itemid , kmaster , kgp , kend , stud_id , sportkind , cname , idclass , sportnum , num , results , sportorder , memo , passOK)values ('{$mid}' ,'{$itemid}' ,'{$kmaster}' ,'{$kgp}' ,'{$kend}' ,'{$stud_id}' ,'{$sportkind}' ,'{$cname}' ,'{$idclass}' ,'{$sportnum}' ,'{$num}' ,'{$results}' ,'{$sportorder}' ,'{$memo}' ,'{$passOK}' )";
		//最後一筆新增的編號
		//$Insert_ID= $this->CONN->lastInsertId();
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$item;
		Header("Location:$URL");
	}

	/* 7.更新處理函式  */
	function update(){
		//echo "<pre>";print_r($_POST);die();
		/* 取出更新語法； 可於這裡進行其他安全性或額外處理*/
		//$fields=array('id','mid','itemid','kmaster','kgp','kend','stud_id','sportkind','cname','idclass','sportnum','num','results','sportorder','memo','passOK');
		$itemid=pVar('item');
		$tol=pVar('tol');
		if (count($_POST['upStu'])==0) backe('無任何更新資料！');
		foreach ($_POST['upStu'] as $id=>$Num){
			$Ord=strip_tags($_POST['upStu_num'][$id]);
			$SQL="update `sport_res` set results='{$Num}',num='{$Ord}' where id='{$id}' and itemid='{$itemid}' ";  
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
		$SQL="update `sport_res` set results='' where itemid='{$itemid}' ";  
		$rs=$this->CONN->query($SQL) or die($SQL);
		$URL=$_SERVER['SCRIPT_NAME']."?item=".$itemid;
		Header("Location:$URL");
	}

	function myBox3($name,$stu,$N){
		if ($stu=='') return ;
		$i=1;
		$str='';
		//PP($stu);
		foreach($stu as $key=> $ar){
			$K=$ar['idclass'];
			//echo $K;
			if ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			$Val="<font color=".$color.">".substr($ar['idclass'],3,2)."</font>.".$ar['cname'];
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
		//$Val=$ar['idclass'].$ar['cname'];
		$str.=$Val."<input class=bt2 type='text' size=1 name='".$name."[".$ar['id']."]' value='".$ar['sportorder']."'><br>\n";
		//if ($i%$N==0)$str.='<br>';
		$i++;
		}
		return $str;
	}

	function myBoxT($name,$stu,$N){
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
			//if ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			$Val=substr($ar['idclass'],0,3)."-<font color=blue>".substr($ar['idclass'],3,2)."</font>.".$ar['cname'];
			
			//$arif ($this->Stu[$K]['sex']=='男') {$color='blue';}else{$color='red';}
			//$Val="道次".$j.': '.$ar['idclass'].$ar['cname'];
			$str.=$Val."<input class=bt2 type='text' name='".$name."[".$ar['id']."]' value='".$ar['results']."' size=1>";
			if ($ar['num']==0) {$X='';}else{$X=$ar['num'];}
			$str.=":<input class=bt2 type='text' name='".$name."_num[".$ar['id']."]' value='".$X."' size=1>";
			$str.="(".$ar['sportorder'].")<br>\n";
			$i++;$j++;
		}
		return $str;
	}

	/*各組依成績排序*/
	function myBoxAdd($name,$N){
		//PP($this->all);
		$str='';
		foreach ($this->P1 as $K=>$V){
			$G=ceil($this->all[$K]['sportorder']/$N);
			$num=$this->all[$K]['sportorder']%$N;
			if ($num==0)$num=$N;
			//$K=$ar['id'];
			//$ar['sk']=$ar['results'].'_'.$ar['sportorder'];
			//$tmp[$K]=$ar;
			$Val=$this->all[$K]['results'].'_'.$this->all[$K]['idclass'].'_'.$this->all[$K]['cname'].'_'.$this->all[$K]['sportnum'];
			$show="(<B style='color:blue;'>".$G.'-<font color=red>'.$num.'</font></B>),';
			$show.=$this->all[$K]['idclass'].','.$this->all[$K]['cname'].','.$this->all[$K]['results'];
			$str.="<label><input type='checkbox' name='".$name."[".$K."]' value='".$Val."' >".$show."</label><br>\n";
			
		}
		
		return $str;
	}



	function ItemLink(){
		$Link='';
		if ($this->Im['C']['skind']=='0' && $this->Im['Next']==''){
		$Link="<a href='sp_orderA.php?item=".$this->item."'>□決賽檢錄</a>
		■<a href='sp_writeA.php?item=".$this->item."'>決賽成績</a>";
		}
		if ($this->Im['C']['skind']=='0' && $this->Im['Next']!=''){
		$Link="<a href='sp_orderA.php?item=".$this->item."'>□初賽檢錄</a>
		■<a href='sp_writeA.php?item=".$this->item."'>初賽成績</a>
		<a href='sp_orderA.php?item=".$this->Im['Next']."'>□決賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->Im['Next']."'>□決賽成績</a>";
		}
		if ($this->Im['C']['skind']!='0' && $this->Im['Next']==''){
		$Link="<a href='sp_orderA.php?item=".$this->Im['C']['skind']."'>□初賽檢錄</a>
		<a href='sp_writeA.php?item=".$this->Im['C']['skind']."'>□初賽成績</a>
		<a href='sp_orderA.php?item=".$this->item."'>□決賽檢錄</a>
		■<a href='sp_writeA.php?item=".$this->item."'>決賽成績</a>";
		}
		return $Link;
	}



}
//end class



