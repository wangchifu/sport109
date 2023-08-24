<?php
include_once('109.conf.php');

if (gVar('login')=='out') login_out();

$obj= new sport_main($smarty);
$obj->process();

class sport_main{
	var $smarty;//smarty物件
	var $token;//編碼
	var $code;//學校代碼
	//資料表欄位

	/* 1.建構函式 */
	function __construct($smarty){
		//$this->CONN=$CONN;
		$this->smarty=$smarty;
	}

	/* 2.初始化一些數值處理函式  */
	function init() { }



	/* 3.物件流程函式  */
	function process() {		
		//初始化一些數值
		$this->init();

		//建立Token
		$myToken=new myToken();
		if ($myToken->check()=='Y'){
			$form_act=pVar('form_act');
			$kind=pVar('kind');
			if ($form_act=='login'&& $kind=='G') $this->checkG();
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
		$tpl = __SitePtah."/login.php.htm";
		//$tpl = dirname(__file__)."/sport_main.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}

	/* 5.擷取資料給網頁呈現處理函式*/
	function all(){


	}

	/* 登入處理 */
	function checkG(){		
		global $SiteData;
		//echo '<pre>';print_r($_POST);die();
		
		 $chk=pVar('CHK');
		 if ($_SESSION['Login_img']!=$chk) backe('錯誤的驗證碼！');
		 $user=pVar('user');
		 $pass=pVar('pass');
		if ($user=='' ||$pass=='' ) backe('請填妥帳號密碼！');
		$Ans=gsuiteAuth($user,$pass);
		if ($Ans=='N') backe('登入失敗，請確認帳號密碼無誤！');

		
		//print_r($Ans);
		//die();
		/* --自動建立目錄-- */
		//----Smarty寫入目錄位置
		if( !isset( $_SESSION ) )  session_start();
		$_SESSION['Auth'] = $Ans;				
		
		$SiteData='../../sport_data/'.$_SESSION['Auth']['code'].'/';
		
		$file_name=$SiteData.$_SESSION['Auth']['code'].'_ok.text';  
		if(file_exists($file_name)){
			include_once "db_connect.php";
			$SQL="select * from `teach` where edukey='{$_SESSION['Auth']['edu_key']}' ";
			$rs=$CONN->query($SQL) or die($SQL);	
			$arr=$rs->fetchAll();			
			$_SESSION['Auth']['prem'] = $arr[0]['prem'];
			$_SESSION['Auth']['cla'] = $arr[0]['cla'];
			header("Location:main.php");				
			exit();
		}else{
			autoDir($SiteData);					
			$Smarty_Compile_DIR    = $SiteData.'templates_c/';//(最後有/,注意：這個目錄是可寫入的)
			autoDir($SiteData.'Cache/');
			autoDir($SiteData.'odftmp/');
			autoDir($Smarty_Compile_DIR);
	
			if(preg_match("/資訊/i",$_SESSION['Auth']['title']) or preg_match("/體育/i",$_SESSION['Auth']['title']) or preg_match("/體衛/i",$_SESSION['Auth']['title'])){
				//print_r($_SESSION);
				//die();
				$file_name=$SiteData.$_SESSION['Auth']['code'].'_ok.text';  
				if(file_exists($file_name)){
					header("Location:main.php");				
					exit();
				}else{
					header("Location:install.php");				
					exit();
				}								
			}else{
				backe("！！職稱有「資訊」「體育」「體衛」兩字，方能進行學校初次設定！！");
			}
		}
		die();					
	}

}
//end class