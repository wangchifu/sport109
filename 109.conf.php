<?php
/* 設定 session_name */
$session_name='Sport109';
session_name($session_name.date("d"));


// 1.最高管理者的學籍帳號


	$Admin['edu_key'] = null;
//$Admin['Pass']='abc1234';//暫用不到
	$school_code = null;
	$SiteData = '../../sport_data/';
// 2.最高管理者的學籍帳號
//$school_code='074628';//教育部學校代碼

// 3.雲端學籍系統內，學校的 API ID
$API_client_id = null;

// 4.雲端學籍系統內，學校的 API 密碼
$API_client_secret = null;




// 5.可寫入目錄(放置資料庫檔及暫存區用)，建議搬至網頁目錄外
if( !isset( $_SESSION ) )  session_start();
if(isset($_SESSION)){	
	$Admin['edu_key']=$_SESSION['Auth']['edu_key'];
	$school_code= $_SESSION['Auth']['code'];
	$SiteData = '../../sport_data/'.$_SESSION['Auth']['code'].'/';
	if(file_exists($SiteData.$_SESSION['Auth']['code'].'_api.csv')){		
		$fp = fopen($SiteData.$_SESSION['Auth']['code'].'_api.csv', "r");
		while (($line = fgetcsv($fp)) !== FALSE) {
			$API_client_id = $line[0];    		
			$API_client_secret = $line[1];
		}				  
		fclose($fp);
	}
}

// define('__SiteData', '/home/webadmin/sport_data/');
// define('__SiteData', '/home/stu/data/');



// -------  以下不用修改  -------------

//  程式目錄，不用修改
define('__SitePtah', dirname(__file__)."/");
//時區
date_default_timezone_set( "Asia/Taipei");
// ini_set('memory_limit', '256M');
// ini_set('session.gc_probability', 1);
ini_set('error_reprorting', "E_ALL & ~E_NOTICE");
ini_set('display_errors', 1);







$Cache=new chiCache;//建立快取物件





//----Smarty class 的位置
$Smarty_class_file    = dirname(__file__).'/Smarty3.1.34/Smarty.class.php';
//$Smarty_class_file    = '/home/stu/05html/smarty-3.1.33/Smarty/Smarty.class.php';
//$Smarty_class_file    = '/home/webadmin/html/smarty-3.1.33/libs/Smarty.class.php';

//----Smarty寫入目錄位置
$Smarty_Compile_DIR    = $SiteData.'templates_c/';//(最後有/,注意：這個目錄是可寫入的)

//Cache
//----Smarty物件----------//
require_once $Smarty_class_file;
$smarty = new Smarty();//建立物件
$smarty->error_reporting = E_ALL & ~E_NOTICE; 
$smarty->compile_dir = $Smarty_Compile_DIR;
$smarty->left_delimiter = '{{';//設定樣本檔的左邊標籤
$smarty->right_delimiter = '}}';//設定樣本檔的右邊標籤

$PassIP[]='163.23.89.101';
$PassIP[]='163.23.89.119';
$PassIP[]='163.23';
$PassIP[]='127.0.0.1';
$PassIP[]='192.168';
$PassIP[]='172.20';
$PassIP[]='114.35.94.80';//村仔


$PassDomain[]='localhost';
$PassDomain[]='.hinet.net';
$PassDomain[]='.chc.edu.tw';
//$PassDomain[]='localhost';


// if (allowDomain()=='N') backe("！不在授權使用範圍內！");
// if (ipAllow($PassIP)!='Y') backe("！非學術網路IP\n不在授權使用範圍內！");

/*禁止列表,禁止傳回Y*/
function allowDomain(){
	global $PassDomain;
	$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	//echo $hostname.':';
	$allow='N';
	foreach ($PassDomain as $one){
		//比對字串結尾
		if (preg_match("/".$one."$/", $hostname)) $allow='Y';
	}
	return $allow;
}


// if (ipAllow($PassIP)!='Y') backe("！非學術網路IP\n不在授權使用範圍內！");

/*禁止列表,禁止傳回Y*/
function ipDeny($deny_ary){
	$now=$_SERVER['REMOTE_ADDR'];
	$IS_NoMan='';
	foreach ($deny_ary as $one){
		if (preg_match("/^".$one."/", $now)) $IS_NoMan='Y';
	}
	//if ($IS_NoMan=='Y') backe("！非學術網路IP\n不在授權使用範圍內！");
	return $IS_NoMan;
}


/*允許列表,允許傳回Y*/
function ipAllow($allow_ary){
	$now=$_SERVER['REMOTE_ADDR'];
	$IS_OKMan='';
	foreach ($allow_ary as $one){
        if (preg_match("/^".$one."/", $now)) $IS_OKMan='Y';
	}
	//if ($IS_OKMan=='N') backe("！非學術網路IP\n不在授權使用範圍內！");
	return $IS_OKMan;
}

##################回上頁函式1#####################
function backe($value= "BACK"){
	//echo head();
	echo  "<meta charset='UTF-8'><br><br><CENTER>";
	echo "<h4>--== 《系統訊息》 ==--</h4>";
	echo "<div align=center onclick='history.back();' style='font-size:12pt;color:#A52A2A;'><b>";
	echo $value;
	echo "</b><BR></div><h5 onclick='history.back();'>--==  《按下後返回》 ==--</h5>";
	exit;
}

/* 傳值處理,post優先*/
function gpVar($N){
 if (isset($_POST[$N]))	return strip_tags($_POST[$N]);
 if (isset($_GET[$N]))	return strip_tags($_GET[$N]);
}

/* 傳值處理,僅使用GET */
function gVar($N){
 if (isset($_GET[$N]))	return strip_tags($_GET[$N]);
}
/* 傳值處理,僅使用POST */
function pVar($N){
 if (isset($_POST[$N]))	return strip_tags($_POST[$N]);
}

/* 傳值處理,僅使用SESSION */
function sVar($N){
 if (!isset($_SESSION))	session_start();
 if (isset($_SESSION[$N]))	return strip_tags($_SESSION[$N]);
}


/*自動建立目錄*/
function autoDir($dir){
//echo $dir.'<br>';
	if (file_exists($dir) && is_dir($dir))	return ;
   	$rs = @mkdir($dir, 0755); 
	if (!$rs) backe($dir."<br>資料存放區不存在或無法建立！");
}


/* 傳入arp指令結果(僅一行)，傳出陣列*/
function gMac($arp){
	$lines=explode(" ", $arp);
	$find = array("(",")");
	$A['ip']=str_replace($find,'',$lines[1]);
	//$A['ip']=str_replace("(,)",'',$lines[1]);
	$A['mac']=$lines[3];
	return $A;
}



//		$URL=$_SERVER['SCRIPT_NAME'];
//		Header("Location:$URL");

/* 12.處理checkBox ckBox('名稱','選項陣列','每隔幾個換行','被選的內容') */
function ckBox($Name,$ary,$Num,$sel=''){
	$Str='';
	$ck=" checked=checked ";
	$SS='';
	$tmp=explode (',',$sel);
	if (count($tmp)>0 ) $SS=$tmp;
	$i=1;
	foreach ($ary as $K =>$val){
		$CC='';
		if (is_array($SS) && in_array($K,$SS)) $CC=$ck;
		$Str.="<label><input type='checkbox' name='".$Name."[]' value='$K' $CC >".$val."</label>\n";
		if ($i%$Num=='0') $Str.="<br>"; 
		$i++;
	}
	return $Str;
}

//組合陣列成字串
function myJoin($a){
	if (isset($_POST[$a])){
		if (is_array($_POST[$a])) return join(',',$_POST[$a]);
	}
}
/* 列印變數*/
function PP($a){
	echo "<pre>";print_r($a);
	echo "</pre>";exit;
}
class Chi_Page {
	var $tol ;//總筆數
	var $page ;//目前頁數
	var $size ;//每頁筆數
	var $url;//連結網址
	var $sy='?';
//	var $txt=array(1=>"第一頁",2=>"上頁",3=>"下頁",4=>"最未頁");
//	var $txt=array(1=>'|<',2=>'<<',3=>'>>',4=>'>|');
	var $txt=array(1=>' |< ',2=>' << ',3=>' >> ',4=>' >| ');

//	function Chi_Page($tol,$size,$page,$url='') {
	public function __construct($tol,$size,$page,$url=''){

		($page=='') ? $this->page=0:$this->page=$page;
		if 	($url=='') {
			$this->url=$_SERVER['SCRIPT_NAME'];
			$this->sy='?';
			}else{
			$this->url=$url;
			
			// (ereg ("[?]", $this->url) ) ? $this->sy='&': $this->sy='?';
			(preg_match("/\?/", $this->url) ) ? $this->sy='&': $this->sy='?';
			}

		$this->tol=$tol;
		$this->size=$size;

//		($size=='') ? die("無法操作Chi_Page class"):$this->size=$size ;
//		if ($this->tol==''|| $this->tol==0) die("沒有資料,無法操作 Chi_Page class");
//		$this->all_p=ceil($this->tol/$this->size);
		}
	
	#####################   跳頁函式  ###########################
	function show_page(){
		if ($this->tol=='' ||$this->tol==0 || $this->size==''|| $this->size==0 ){
			$tt=$this->button('目前無資料!').$this->button('第1頁').$this->button('上頁').$this->button('下頁').$this->button('最末頁');
			return $tt;
		}
		$tol=ceil($this->tol/$this->size);
		$now=$this->page;
		$URL=$this->url;
		($URL==$_SERVER['SCRIPT_NAME']) ? $ln='?':$ln='&';
		if ( $tol==1 ) return $this->button("共".$this->tol."筆資料").$this->button('第1頁').$this->button('上頁').$this->button('下頁').$this->button('最末頁');
		if ( $tol==2 ) {
			if ($now==0) $tt= $this->button('第1頁').$this->button('上頁').$this->button('下頁',$URL.$ln."page=1").$this->button('最末頁',$URL.$ln."page=1");
			if ($now==1) $tt= $this->button('第1頁',$URL.$ln."page=0").$this->button('上頁').$this->button('下頁').$this->button('最末頁');
			}
		if ( $tol>=3) {
			$tol2=$tol-1;
			if ($now==0) $tt=$this->button('第1頁').$this->button('上頁').$this->button('下頁',$URL.$ln."page=1").$this->button('最末頁',$URL.$ln."page=".$tol2);
			if ($now!=$tol2 && $now!=0) 
				$tt=$this->button('第1頁',$URL.$ln."page=0").
				$this->button('上頁',$URL.$ln."page=".($now-1)).$this->button('下頁',$URL.$ln."page=".($now+1)).$this->button('最末頁',$URL.$ln."page=".$tol2);
			if ($now==$tol2) 
				$tt= $this->button('第1頁',$URL.$ln.'page=0').
				$this->button('上頁',$URL.$ln."page=".($now-1)).$this->button('下頁').$this->button('最末頁');
		}
		$ss=$this->jump($URL,$ln,$tol,$now);
		Return $this->button("共 $this->tol 筆").$tt.$ss;
		
		}
	function jump($URL,$ln,$tol,$now){
		$ss="<select name='ch_page' size='1' class='small' onChange=\"location.href='".$URL.$ln."page='+this.options[this.selectedIndex].value;\"   style='border:2px; background-color:#E5E5E5; font-size:10pt;color:#A52A2A' >";
		for ($i=0;$i<$tol;$i++){
			($now==$i) ? $cc=" selected":$cc="";
			$ss.="<option value='$i' $cc>第".($i+1)."頁</option>\n";
		}
		$ss.="</select>";
		return $ss;
		}
		
		
	function button($word,$URL=''){
		$tt="<input type='button' value='$word'  ";
		($URL=='') ? $tt.=" disabled  style=' border:1px;font-size:10pt'>":$tt.="  style='color:#A52A2A;border:1px;font-size:10pt' onclick=\"location.href='$URL'\"  >";
		return $tt;	
	}

	#####################   跳頁函式  ###########################
	function show_p(){
		if ($this->tol=='' ||$this->tol==0 || $this->size==''|| $this->size==0 ){
			$tt=$this->Wd('目前無資料!').$this->Wd(1).$this->Wd(2).$this->Wd(3).$this->Wd(4);
			return $tt;
		}
		$tol=ceil($this->tol/$this->size);
		$now=$this->page;
		$URL=$this->url;
		($URL==$_SERVER['SCRIPT_NAME']) ? $ln='?':$ln='&';
		if ( $tol==1 ) return $this->Wd("共".$this->tol."筆資料").$this->Wd(1).$this->Wd(2).$this->Wd(3).$this->Wd(4);
		if ( $tol==2 ) {
			if ($now==0) $tt= $this->Wd(1).$this->Wd(2).$this->Wd(3,$URL.$ln."page=1").$this->Wd(4,$URL.$ln."page=1");
			if ($now==1) $tt= $this->Wd(1,$URL.$ln."page=0").$this->Wd(2,$URL.$ln."page=0").$this->Wd(3).$this->Wd(4);
			}
		if ( $tol>=3) {
			$tol2=$tol-1;
			if ($now==0) $tt=$this->Wd(1).$this->Wd(2).$this->Wd(3,$URL.$ln."page=1").$this->Wd(4,$URL.$ln."page=".$tol2);
			if ($now!=$tol2 && $now!=0) 
				$tt=$this->Wd(1,$URL.$ln."page=0").
				$this->Wd(2,$URL.$ln."page=".($now-1)).$this->Wd(3,$URL.$ln."page=".($now+1)).$this->Wd(4,$URL.$ln."page=".$tol2);
			if ($now==$tol2) 
				$tt= $this->Wd(1,$URL.$ln.'page=0').$this->Wd(2,$URL.$ln."page=".($now-1)).$this->Wd(3).$this->Wd(4);
		}
		$ss=$this->jump($URL,$ln,$tol,$now);
		Return $this->Wd("共 $this->tol 筆").$tt.$ss;
		
		}
	function Wd($key,$URL=''){
		if(array_key_exists($key,$this->txt)) {
			($URL=='') ? $tt=$this->txt[$key]:$tt="<A HREF='$URL'>".$this->txt[$key]."</A>";
		} else {
			($URL=='') ? $tt=$key:$tt="<A HREF='$URL'>".$key."</A>";
		}
		return $tt;
	}

}

/* Token 函式 
//產生Token $_SESSION['token']
$this->token=Token::generate();
if (isset($_POST['token']) && Token::check($_POST['token'])){}
//Generate a random string.
$token = openssl_random_pseudo_bytes(16);

//Convert the binary data into hexadecimal representation.
$token = bin2hex($token);
 
//Print it out for example purposes.
echo $token;

* 
*/
class myToken{
	public $name='token'; 
	 function make(){
		if( !isset( $_SESSION ) )  session_start();
		$name=$this->name;
		//$_SESSION["{$this->name}"]= md5(uniqid(rand(), true));
		$_SESSION[$name]= base64_encode(openssl_random_pseudo_bytes(32));		
		return $_SESSION[$name];
		}
	 function check(){
		$name=$this->name;
		if( !isset($_POST[$name]) ) return 'N';
		$token=strip_tags($_POST[$name]);
		if( !isset( $_SESSION ) )  session_start();
		if(isset($_SESSION[$name])){
			if ($token === $_SESSION[$name]){
				unset($_SESSION[$name]);//unset($_SESSION);
				return 'Y';
				}
            }
        return 'N';
    }
}



/* 彰化GSuite的認證 */
function gsuiteAuth($username,$password){
		//$username帳號   $password密碼
		//彰化GSuite的認證設計，帳號輸入 xxx 或是  xxx@yahoo.com.tw 或是 xxx@chc.edu.tw 都會成功登入
/*
		$n = explode('@',$username);
		if ($n[1]!='chc.edu.tw') return '登入失敗';
		$data = array("email"=>$n[0],"password"=>$password);
*/
        $data = array("email"=>$username,"password"=>$password);
        $data_string = json_encode($data);
        $ch = curl_init('https://school.chc.edu.tw/api/auth');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        
        //$obj即是返回各openid的項目
        $obj = json_decode($result,true);
        if($obj['success']) return $obj;
        else return 'N';//登入失敗
        /*
		//登入成功
        if($obj['success']) return {
			if($obj['kind']=="學生"){   }
		//登入失敗
		}else{}
		*/ 

}		

//改變班級代碼方式 6_2 => 602
function change_class($cla){
	//unset($_SESSION['Login']);
	$A=explode('_',$cla);
	return $A[0].sprintf("%02d",$A[1]);
}


//登入函式
function login_chk($prem=''){
	//unset($_SESSION['Login']);
	if ( !isset( $_SESSION ) )  session_start();
	if ( !isset($_SESSION['Auth'])){
		$URL=$_SERVER['PHP_SELF'];
		Header("Location:login.php?SRC=".$URL);
		}
	if ($prem!=''){
		$pattern = '/'.$prem.'/';
		$OK=0;
		if (!isset($_SESSION['Auth']['prem'])) backe($prem.'權限不足！');
		if (preg_match("/All/i",$_SESSION['Auth']['prem'])) $OK=1;
		if (preg_match($pattern,$_SESSION['Auth']['prem'])) $OK=1;
		if ($OK==0) backe($prem.'權限不足！');
	}
}

function login_out(){
	//unset($_SESSION['Login']);
	if ( !isset( $_SESSION ) )  session_start();
	//unset($_SESSION['Auth']);
	session_unset();
	$URL=$_SERVER['PHP_SELF'];
	Header("Location:".$URL);
}


//----- 簡易認証函式 -----//
/**
function Auth() {
  global $Admin;
if ($_SERVER['PHP_AUTH_USER']!=$Admin['User'] || $_SERVER['PHP_AUTH_PW']!=$Admin['Pass'] || $_SERVER['PHP_AUTH_USER']=='' || $_SERVER['PHP_AUTH_PW']=='') {
    Header("WWW-Authenticate: Basic realm=\"SOGO Super Member\"");
    Header("HTTP/1.0 401 Unauthorized");
	backe('不要隨意進入！');
//    echo "<div align='center'><h2>不要隨意進入！</h2></div>";
    exit;} 
}
 */

//----- 檢查session id 是否正確 -----//
function sessionVerify() {
	if( !isset( $_SESSION ) )  session_start();
	if ( !empty($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
		$temp_ip = split(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
		$user_ip = $temp_ip[0];
	} else {
	$user_ip = $_SERVER["REMOTE_ADDR"];
	}

		if(!isset($_SESSION['user_agent'])){
		$_SESSION['user_agent'] = MD5($user_ip.$_SERVER['HTTP_USER_AGENT']);
		}
/* 如果用session ID是偽造,則重新分配session ID */
	elseif ($_SESSION['user_agent'] != MD5($user_ip.$_SERVER['HTTP_USER_AGENT'])) {
		//$old_sessionid = session_id();
		session_regenerate_id(TRUE);
		// $new_sessionid = session_id();
	}
}

/*取得系統選項設定*/
function getOptions(){
	global $CONN,$Cache;
	$Options=$Cache->get('Options');//依名稱取出快取資料
if ($Options==false){
	$SQL="select * from `sport_var` ";
	$rs=$CONN->query($SQL) or die($SQL);
	$arr=$rs->fetchAll();
	$Options=array();
	foreach ($arr as $ary){
		//$Ka=$ary['gp'];$Kb=$ary['kkey'];$Kc=$ary['na'];
		//$Options[$Ka][$Kb]=$Kc;			
		$Ka=$ary['iKey'];
		$Options[$Ka]=tranAry($ary['data']);
		}
		$Cache->save('Options',$Options);//存入快取 
	}
	return $Options;
}

/*取得各種項目名稱*/
function ItemName(){
	global $CONN,$Cache;
	$All_item=$Cache->get('Sport_All_item');//依名稱取出快取資料
	if ($All_item==false){
		$Kind=array(1=>'初賽',2=>'決賽');
		$Options=getOptions();//取得變數名稱	
		$SQL="select * from `sport_item` ";
		$rs=$CONN->query($SQL) or die($SQL);
		$arr=$rs->fetchAll(PDO::FETCH_ASSOC);
		$Item=array();
		foreach($arr as $ary){
		$I=$ary['id'];$im=$ary['item'];$ent=$ary['enterclass'];
		$k=$ary['kind'];
//		$Item['A'][$I]=$Options['sportclass'][$ent].$Options['sportname'][$im].$Kind[$k];
//		$Item['B'][$I]=$I.'_'.$ent.'_'.$im.'_'.$k;
//		$Item['C'][$I]=$ary;
		$Item[$I]['A']=$Options['sportclass'][$ent].$Options['sportname'][$im].$Kind[$k];
		if ($ary['sportkind']=='5') $Item[$I]['A']=$Options['sportclass'][$ent].$Options['sportname'][$im].'接力'.$Kind[$k];
	//	$Item[$I]['A']=$Options['sportclass'][$ent].$Options['sportname'][$im].$Kind[$k];
		$Item[$I]['A2']='每組'.$ary['playera'].'人，錄取'.$ary['passera'].'名。';
		$Item[$I]['A3']=$Options['sportname'][$im];
		$Item[$I]['B']=$I.'_'.$ent.'_'.$im.'_'.$k;
		$Item[$I]['C']=$ary;
		$Item[$I]['Next']='';
		}
		foreach($arr as $ary){
		$I=$ary['id'];
		if ($ary['skind']!=0) {
			$A=$ary['skind'];
			$Item[$A]['Next']=$I;
			}
		}
		$All_item=$Item;
		$Cache->save('Sport_All_item',$Item);//存入快取 
	}
	return $All_item;
	//foreach(){}
//echo '<pre>';print_r($Item);
}


/*登入記錄 */
function Login_log($title){
	global $CONN;
	$iday=date("Y-m-d H:i:s");
	$addr=GetIP();
	$info=serialize($_POST);
	$SQL="INSERT INTO `sport_login`(title,iday,addr,info)values ('{$title}' ,'{$iday}' ,'{$addr}','{$info}' )";
	$rs=$CONN->query($SQL) or die($SQL);
}

//轉換為陣列
function tranAry($str){
		$tmp=explode("\n",$str);
		if (count($tmp)==1) return ;
		foreach($tmp as $str){
			$str=trim($str);
			if (strstr($str, ':')):
				$k=explode(":",$str);
				$A[$k[0]]=$k[1];
			endif;
		} 
		//print_r($A);
		return $A; 

}

/*取得真實IP*/
function GetIP(){
	if (getenv("HTTP_CLIENT_IP") &&strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
	$ip = getenv("HTTP_CLIENT_IP");
	else if (getenv("HTTP_X_FORWARDED_FOR")&&strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
	$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if (getenv("REMOTE_ADDR") &&strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
	$ip = getenv("REMOTE_ADDR");
	else if (isset($_SERVER['REMOTE_ADDR'])&& $_SERVER['REMOTE_ADDR']&&strcasecmp($_SERVER['REMOTE_ADDR'],"unknown"))
	$ip = $_SERVER['REMOTE_ADDR'];
	else
	$ip = "unknown";
	return($ip);
}

/* 檢查並新增K5班級報名單--主項,項目,可報組數,班級*/
function AddKind5($mid,$item,$gp,$cla){
		global $CONN;
//kmaster,隊長1,大隊接力2
//	$Kind=array(1=>'初賽',2=>'決賽');
//	$Options=getOptions();//取得變數名稱
	if ($mid=='') backe('資料不足，無法報名！');
	if ($item=='') backe('item資料不足，無法報名！');
	if ($gp=='') backe('gp組別資料不足，無法報名！');
	if ($cla=='') backe('cla班級資料不足，無法報名！');

	$SQL="select * from `sport_res` where itemid='{$item}' and idclass='{$cla}' and mid='{$mid}' ";
	$rs=$CONN->query($SQL) or die($SQL);
	$arr=$rs->fetchAll();
	if (count($arr)==0){
		for ($i=0;$i<$gp;$i++){
			$G=$i+1;$cname=$cla.'#'.$G;
		$SQL="INSERT INTO sport_res(mid,itemid,kmaster,kgp,sportkind,cname,idclass) VALUES (
		'{$mid}','{$item}','2','{$G}','5','{$cname}','{$cla}')";
		$rs=$CONN->query($SQL) or die($SQL);
		}
	}
	//foreach(){}
//echo '<pre>';print_r($Item);
}


/*

Example:設定快取
$a=new chiCache;
$a->dir='/home/ramdisk/';//後有斜線,指定快取路徑
$ary=$a->get('100');//依名稱取出快取資料
if ($ary==false){
	// 到資料庫或其他地方取資料
	$data=array(stud_id,move_kind,move_year_seme,move_date,move_c_unit,move_c_date,move_c_num);
	// 將資料放入快取	
	$a->save('100',$data);//前為名稱後為資料,若有資料預設會先刪除
}
*/
class chiCache{	
	private $ext='.cache';//附檔名
	//public $dir=$SiteData.'Cache/';//快取檔案路徑


	//建構函式,起始
	public function __construct() {
		global $SiteData;
		$this->dir = $SiteData.'Cache/';//快取檔案路徑
	}

	//存入快取
	public function save($id,$data) {
		$fn=$this->dir.$id.$this->ext;
		//先判斷檔案存在否
		if (file_exists($fn)) {$this->del($id);}//存在時如何處理
		if ($this->makeFile($fn)){
			$str=serialize($data);
			$fp=fopen($fn,"w");
			fwrite ($fp,$str);
			fclose($fp);
			//return true;
		}
	}
	
	//取出快取
	public function get($id) {
		$fn=$this->dir.$id.$this->ext;
		//先判斷檔案存在否
		if (!file_exists($fn)) return false;//不存在時如何處理
		$str = file_get_contents($fn);
		if (!empty($str)):
			$data=unserialize($str);
			return $data;
		endif;

	}

	//刪除快取
	public function del($id) {
		$fn=$this->dir.$id.$this->ext;
		if (file_exists($fn)) unlink($fn);//存在時如何處理
		}

	//建立檔案
	private function makeFile($file) {
		$fp=fopen($file,"a");
		if ($fp){fclose($fp);return true;}
		else {return false;}
	}

}
//end class


function elps_API(){
	global $API_client_id,$API_client_secret;

	// =================================================
	//    學生榮譽榜 (url: https://api.chc.edu.tw)
	//    校務佈告欄 (url: https://api.chc.edu.tw/school-news)
	//    同步學期資料 (url: https://api.chc.edu.tw/semester-data)
	//    更改師生密碼 (url: https://api.chc.edu.tw/change-password)

	// API NAME
	$api_name = '/semester-data';
	//$api_name = '/school-news';
	// 更改師生密碼 (url: https://api.chc.edu.tw/change-password)

	// API URL
	$api_url = 'https://api.chc.edu.tw';
	//: https://api.chc.edu.tw/school-news
	// 建立 CURL 連線
	$ch = curl_init();
	// 取 access token
	curl_setopt($ch, CURLOPT_URL, $api_url."/oauth?authorize");
	// 設定擷取的URL網址
	curl_setopt($ch, CURLOPT_POST, TRUE);
	// the variable
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	'client_id' => $API_client_id,
	'client_secret' => $API_client_secret,
	'grant_type' => 'client_credentials'
	));

	$data = curl_exec($ch);
	$data = json_decode($data);

	$access_token = $data->access_token;
	$authorization = "Authorization: Bearer ".$access_token;

	curl_setopt($ch, CURLOPT_URL, $api_url.$api_name);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // **Inject Token into Header**
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$result = curl_exec($ch);
	return $result;
}

	/* 7.更新處理函式  */
function API_data(){
	global $SiteData;
		$file = $SiteData.date("Y").'_school_data.txt';
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

function get_num_out($Num){
	$a[8]=array(4,5,3,6,2,7,1,8);
	$a[7]=array(4,5,3,6,2,7,1,8);
	$a[6]=array(3,4,2,5,1,6);
	$a[5]=array(3,4,2,5,1,6);
	return $a[$Num];
	}
