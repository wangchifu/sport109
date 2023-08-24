<?php
//require_once "config.php";
include "109.conf.php";

// session_start();
if (!isset($_SESSION))	session_start();
$aa=new pass_img();//建立物件
//$aa->show_ttf();//傳入驗証數字碼
$aa->show_str();//傳入驗証數字碼

class pass_img {
	var $pass;
	var $font;//字形檔
	var $weight=70;
	var $height=24;
	
	function pass_img() {
		$t1=range('A', 'Z');
		$t2=range('a','z');
		mt_srand((double)microtime()*1000000);
		$this->pass=$t1[mt_rand(0,25)].$t2[mt_rand(0,25)].sprintf("%04d",mt_rand(1,9999));
		unset($_SESSION["Login_img"]);	
		//session_register("Login_img");
		$_SESSION["Login_img"]=$this->pass;
	}

	function show_str() {
		//s$this->BK_img = dirname(__file__)."/sky.png";//圖檔名稱 JPG & PNG 兩種格式
		//$font = dirname(__file__)."/FreeSansBold.ttf";
		$fs = "12"; // 字體大小
		$fx = "5"; //字開頭 x 座標
		$fy = "3"; //字開頭 y 座標
		$R = 255; //7B 字體顏色 R 碼
		$G = 15; //0F 字體顏色 G 碼
		$B = 15; //0F 字體顏色 B 碼

		//判斷大圖格式並且開啟圖檔
		//$gs = GetImageSize("$this->BK_img");
		/* GetImageSize傳回的陣列有四個元素，
		索引值 0 表示圖形的寬為多少像素(pixels)，
		索引值 1表示圖形的高，
		索引值 2則指出圖形為何種類型，1＝GIF，2＝JPG，3＝PNG，
		索引值 3是個 "height=xxx width=xxx"的字串，它能直接的用在 IMG標籤中。 */

		//if ($gs[2] == 1) $origImg = ImageCreateFromGIF("$this->BK_img");
		//if ($gs[2] == 2) $origImg = ImageCreateFromJPEG("$this->BK_img");
		//if ($gs[2] == 3) $origImg = ImageCreateFromPNG("$this->BK_img");
		$origImg = @imagecreate($this->weight,$this->height);
//		$white = ImageColorAllocate ($origImg ,$whitePR ,$whitePG ,$whitePB);
//		$backgroundcolor = 
		ImageColorAllocate($origImg,255,255,255);
		//加入干擾像素
		$ss=date("s") %4 ;

		switch ($ss){
			case 0 :		$R=5;$G=5;$B=5;
				break;
			case 1 :		$R=255;$G=15;$B=15;
				break;
			case 2 :		$R=0;$G=128;$B=0;
				break;
			case 3:		$R=15;$G=15;$B=255;
		}

		
		
		//影像處理
		$textcolor = ImageColorAllocate ($origImg ,$R ,$G ,$B);
		imagestring($origImg,$fs,$fx,$fy,$this->pass,$textcolor);
		//imagestring (int im, int font, int x, int y, string s, int col)
//		Imageline($origImg,0,9,70,9,$textcolor); //x1,y1,x2,y2,color
//		Imageline($origImg,0,0,80,25,$textcolor); //x1,y1,x2,y2,color
		//Imageline($origImg,0,14,70,14,$textcolor); //x1,y1,x2,y2,color
			for($i=0;$i<100;$i++)	{
				$randcolor = ImageColorallocate($origImg,rand(0,255),rand(0,255),rand(0,255));
				imagesetpixel($origImg,rand()%$this->weight,rand()%$this->height,$randcolor);
			}
		// 產生最終PNG圖片並且釋放記憶體
		ImagePNG($origImg);

		/*ImagePNG輸出一個PNG格式的GD圖形 im到標準輸出(通常是瀏灠器)，
		或者如果有給予一個檔案名稱 filename，則它會輸出圖形到指定的檔名。*/
		ImageDestroy($origImg);//釋放任何和圖形 im關聯的記憶體
	}

}


