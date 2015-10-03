<?php
$A=array(
'CLASS rxnetsvlw.php',
'CLASS rxnetsv.php',
'CLASS TRGame.php',
'CLASS TRPlayer.php',
'CLASS TRCommander.php',
'CLASS TRGWorld.php',
'CLASS TRGWorld1.php',
'CLASS TRTaskManager.php',
'hichkok_brutal.php',
'CLASS TRGLobby.php',
'CLASS testing.php',
);
$inc='$dir=dirname(__FILE__);';

foreach($A as $v){
	$name=substr($v,0,-4).'.phb';
	Compile('./'.$v, './scripts/'.$name);
	$inc.="\r\n require_once(realpath(\$dir.'/{$name}'));";
}
function compile($in, $out){
	if(is_file($out)){
		unlink($out);
	}
	$fh = fopen($out, 'w');
	bcompiler_write_header($fh);
	bcompiler_write_file($fh, $in);
	bcompiler_write_footer($fh);
	fclose($fh);
}

$C=<<<FUC
<?php // © roxblnfk 2012
{$inc}

\$S=new TRGame(\$TRConfig);
\$S->start(true);
?>
FUC;

file_put_contents('./scripts/include.php',$C);
?>