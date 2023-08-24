<?php
include_once "109.conf.php";


if (sVar('main_id')=='') Header("Location:main.php");

if (isset($_SESSION['Auth'])){ 	
		include_once "db_connect.php";
		$obj= new sport_main($smarty);
		$obj->process();
	}else{
	Header("Location:login.php");  
}


class sport_main{
	var $smarty;//smarty物件
	function __construct($smarty){
		//$this->CONN=$CONN;
		$this->smarty=$smarty;
	}
	function init() { }



	/* 3.物件流程函式  */
	function process() {
		$this->display();
	}
	function display(){
		$tpl = __SitePtah."/index.php.htm";
		//$tpl = dirname(__file__)."/sport_main.htm";
		//$this->smarty->template_dir=dirname(__file__)."/templates/";
		$this->pagehead= __SitePtah."/sport_head.htm";//表頭
		$this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
		$this->user_name = $_SESSION['Auth']['name'];
		$this->smarty->assign("this",$this);
		$this->smarty->display($tpl);
	}
}
