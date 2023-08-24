<?php
/* 6.SQLite 資料庫檔案名稱(可不用動) */
if(isset($_SESSION['Auth'])){    
    $DB=$_SESSION['Auth']['code'].'_db.sqlite3';
    define('__SiteDB',$SiteData.$DB);

    /* 啟動PDO-SQlite連接*/
    try {
        // for SQLite3
        $CONN = new PDO('sqlite:'.__SiteDB);
        // for MySQL
    //	$CONN =new PDO('mysql:host='.$MySQL['Host'].';dbname='.$MySQL['Db'], $MySQL['User'],$MySQL['Pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        //$CONN =new PDO('mysql:host=localhost;dbname=STAFFEMAIL','root','chcedu', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        }
    catch (Exception $e) 
        {
        backe("！！無法連結資料庫！！");
        //die("<center><h2>無法連結資料庫</h2></center>");
    }

    /* 如果能連線PDO，就建立資料表，要變更結構，請先修改下面 makeTB 函式 */
    // if ($CONN)	makeTB_A();//成績表
    // if ($CONN)	makeTB_B();//座位表
}else{
    $DB="sport109";
    header("Location:index.php");	
    exit();
}
