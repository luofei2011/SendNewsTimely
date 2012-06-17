<?php
header("Content-Type:text/html;charset=utf-8");
$Get_url=$_GET["url"];/*get the url*/
$to = $_GET["email"];
$keywords = $_GET["keywords"];

//获取网站源代码
function GetHtmlCode($url){
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
	$HtmlCode = curl_exec($ch);
	return $HtmlCode;
}
$dxycontent = GetHtmlCode($Get_url);

//获取网页内链接
function GetAllLink($string) { 
	  $string = str_replace("\r","",$string); 
	  $string = str_replace("\n","",$string); 
	  $regex[url] = "((http|https|ftp|telnet|news):\/\/)?([a-z0-9_\-\/\.]+\.[][a-z0-9:;&#@=_~%\?\/\.\,\+\-]+)";  
      $regex[email] = "([a-z0-9_\-]+)@([a-z0-9_\-]+\.[a-z0-9\-\._\-]+)";   

	    
	  //去掉网页中的[]
	  $string = eregi_replace("\[|\]","",$string);

     //去掉JAVASCRIPT代码 
     $string = eregi_replace("<!--.*//-->","", $string); 
           
    //去掉非<a>的HTML标签   
	 $string = eregi_replace("<[^a][^<>]*>","", $string);  
	                 
	 //去掉EMAIL链接        
	 $string = eregi_replace("<a([ ]+)href=([\"']*)mailto:($regex[email])([\"']*)[^>]*>","",$string);  

	 $key=$_GET["keywords"];
	 $output = split('</a>', $string);
	 for($i=0; $i<count($output); $i++){
		$output_1 = split("<a", $output[$i]);
	 }
 	 return $output_1; 
} 
$test=GetAllLink($dxycontent);
//print_r($test);

//得到当前页面的所有链接
function GetAllHref($test) {
	$index=0;
	for($i=0; $i<count($test); $i++){
		$a_all=(explode(" ",$test[$i]));
		$j=0;
		for($j=0; $j<count($a_all); $j++){
			if($a_all && ereg("href",$a_all[$j])){
				$a_all[$j] = eregi_replace("'|(href=|#)","",$a_all[$j]);  //去掉'#'这种无效链接
				if($a_all[$j]){
					$a_all[$j] = $Get_url.$a_all[$j];
					$out[++$index] = $a_all[$j];
				}
			}
		}
	}
	$out=array_unique($out);
	return $out;
}
$out = GetAllLink($test);

//获取用户关心的链接
function GetUserCareNews ($test,$keywords,$url) {
	$messTxt = "";
	$k=0;
	$key = explode(";",$keywords);

	//自动为网站加载上http，避免网易邮箱链接错误
	if(!ereg("http",$url)){
		$url = "http://".$url;
	}

	for($i=0; $i<count($test); $i++){
		$test[$i] = eval('return'.iconv('gbk','utf-8',var_export($test[$i],true)).';');
		if(ereg("href=", $test[$i])  && !ereg("href='#'",$test[$i])){
			for($j=0; $j<count($key); $j++){
				if(strpos($test[$i],$key[$j])!==false){
					$mess[$k++]=ereg_replace($key[$j],"<font color=red>".$key[$j]."</font>", $test[$i]);
			}
			}
		}
	}
	$mess = array_unique($mess);		//数组去重

	//处理好发送链接，为链接加上网站头文件
	for($l=0; $l<count($mess); $l++){
		$mess[$l] = eregi_replace("href=[\"']","",$mess[$l]);
		if(!ereg("http",$mess[$l]) && (strlen($mess[$l]) != 0)){
				$mess[$l] = $url.$mess[$l];
				$mess[$l] = eregi_replace(" /","/",$mess[$l]);
				$mess[$l]="<a href='".$mess[$l]."</a>";
				$messTxt .= $mess[$l]; 
				$messTxt .= "<BR>";
		}
	}
	return $messTxt;
}
$message = GetUserCareNews($test,$keywords,$Get_url);
echo $message;


//循环抓取
function repeat($num) {
	for($i=0; $i<count($out); $i++){
		$out_html = GetHtmlCode($out[$i]);
		$out_a = GetAllLink($out_html);
		GetMessage($out_html);
	}
}


//搜索当前页面得到的关键词
/*function GetMessage($dxycontent) {
	$messTxt = "";
	$j=0;
	$s=strip_tags($dxycontent);
	$s=eval('return'.iconv('gbk','utf-8',var_export($s,true)).';');//转换数组编码
	$temp0=explode(" ",$s);//对内容进行分割
	$temp0=array_unique($temp0);//去掉数组中重复的元素
	$keywords=$_GET["keywords"];
	$keyword=explode(";",$keywords);
	foreach($temp0 as $str){
		for ($i=0; $i<count($keyword); $i++){
			if(strpos($str,$keyword[$i])!==false){
				$mess[++$j]=ereg_replace($keyword[$i],"<font color=red>".$keyword[$i]."</font>", $str);
			//	echo $mess;
			//	echo "<p>";
			}
		}
	}
	for($i=0; $i<count($mess); $i++){
		if(strlen($mess[$i])>10){
			$mess[$i] = $mess[$i]."<BR>";
			$messTxt .= $mess[$i];
		}
	}
	return $messTxt;
}

//$message=GetMessage($dxycontent);*/
//echo $message;

//推送邮件
function SendEmail($to, $content) {
	//Author:luofei
	//$to 表示收件人地址,$content表示邮件正文内容
	
	error_reporting(E_STRICT);						//错误报告
	date_default_timezone_set("Asia/Shanghai");		//设定时区

	require_once("class.phpmailer.php");
	require_once("class.smtp.php");

	$mail = new PHPMailer();						//新建一个对象
	$mail->CharSet = "UTF-8";						//设置编码，中文不会出现乱码的情况
	$mail->IsSMTP();								//设定使用SMTP服务
	$mail->SMTPDebug = 1;							//启用SMTP调试功能i
													//1 = errors and messages
													//2	= messages only

	$mail->SMTPSecure = "tls";						//安全协议
	$mail->Host = "smtp.googlemail.com";			//SMTP服务器        
	$mail->SMTPAuth = true;							//启用SMTP验证功能	
	$mail->Username = "vipspiderservice@gmail.com";    //SMTP服务器用户名      
	$mail->Password = "lf19920805";					//SMTP服务器用户密码
        
	$mail->From = "vipspiderservice@gmail.com";        //发件人                            
	$mail->FromName = "Spider Service";						//发件人姓名（邮件上显示）
        
	$mail->AddAddress($to);							//收件人地址
	$mail->WordWrap   = 50;							//设置邮件正文每行的字符数
	$mail->IsHTML(true);							//设置邮件正文内容是否为html类型
        
	$mail->Subject = "来自spider.html的邮件";		//邮件主题
	$mail->Body = "<p>您好！<BR> <p>这是您感兴趣的内容</p> <BR>".$content." ";
													//邮件正文
	if(!$mail->Send())								//邮件发送报告
	{
	   echo "发送邮件错误!";
	} 
	else
	{
	   echo "邮件发送成功！";
	}
}
SendEmail($to, $message);
?> 
