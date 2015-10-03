
Loading config . . .
<?php
$TRConfig=array(
	'name'=>'First Maze Server',
	'port'=>7931,
	'addr'=>'0.0.0.0',
	'wdth'=>20,
	'hght'=>20,
	'finishtime'=>10.0,
	'loadingtime'=>3.0,
	'maxplayers'=>2,
	'maxconnections'=>200,
	'globallobby'=>1
);
$dir=getcwd();//dirname(__FILE__);
$f=realpath($dir.'/files/options.txt');
echo "$f\r\n";
if(is_file($f)) $c=file_get_contents($f); else $c=false;
if(is_string($c)){
	$l=explode("\n",$c);
	for($i=0,$j=sizeof($l);$i<$j;++$i){
		echo "\n  ";
		$kv=explode('=',$l[$i],2);
		$k=strtolower(trim($kv[0]));
		if(!$k) continue;
		$v=isset($kv[1]) ? trim($kv[1]) : true;
		echo "$k = $v . . . ";
		if(isset($TRConfig[$k])){
			switch($k){
				case 'globallobby' : $TRConfig[$k]=(int)$v; break;
				case 'name' : $TRConfig[$k]=substr($v,0,32); break;
				case 'port' : $TRConfig[$k]=min(65535,max(intval($v),1)); break;
				case 'addr' : $TRConfig[$k]=substr($v,0,256); break;
				case 'wdth' :
				case 'hght' : $TRConfig[$k]=min(500,max(intval($v),5)); break;
				case 'loadingtime' :
				case 'finishtime' : $TRConfig[$k]=min(500,max(floatval($v),0)); break;
				case 'maxconnections' :
				case 'maxplayers' : $TRConfig[$k]=min(1024,max(intval($v),1)); break;
				default : $TRConfig[$k]=$v;
			}
			echo 'set to '.$TRConfig[$k];
		}else echo 'unregistered parameter!';
		
	}
} else echo 'Bad config file!';

//set_error_handler("exception_error_handler");
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    //$t='Вывалилась ошибка №'.$errno.' : '.$errstr.' В файле : '.$errfile.' в строке '.$errline;
	//__log($t);
	//throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
function __log($t,$r=true){
	echo mb_convert_encoding("$t\n", 'CP866', 'CP1251');
	return $r;
}
?>


PHP Server (C) roxblnfk 2011-2012
Game : Maze v<?php echo TRGame::VERSION; ?>

All files included . . .

