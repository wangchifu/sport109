<?php
    include_once('109.conf.php');
    if(isset($_POST)){
        if(isset($_POST['send_api'])){
            if($_POST['send_api']=="送出API"){
                if( !isset( $_SESSION ) )  session_start();
                $file_name=$_SESSION['Auth']['code'].'_api.csv';            
                $file = fopen($SiteData.$file_name, "w");
                $fileSize = fputs( $file, $_POST['API_client_id'].',');
                $fileSize = fputs( $file, $_POST['API_client_secret']);
                fclose($file);
            }        
        }        
    }
    if(file_exists($SiteData.$_SESSION['Auth']['code'].'_api.csv')){		
        header("Location:easy.php");		
    }

    if(isset($_GET['error'])){
		if($_GET['error']==403){
			echo "<body onload=\"alert('API 錯誤！無法拉回資料！');\">";
		}
	}

    $obj= new sport_main($smarty);
    $obj->process();
    class sport_main{
        var $smarty;//smarty物件        
        
    
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
            $this->display();
        }

        function display(){
            $tpl = __SitePtah."/install.php.htm";
            $this->pagehead= __SitePtah."/sport_head.htm";//表頭
            $this->pagefoot=__SitePtah."/sport_foot.htm";//表尾
            $this->smarty->assign("this",$this);
            $this->smarty->display($tpl);
        }
    }

?>