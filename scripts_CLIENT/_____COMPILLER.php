<?php
$A = Array (  
	'CLASS TRGUI.php',
	'CLASS rxnetcllw.php',
	'CLASS rxnetcl.php',
	'CLASS TRGWorldCL.php',
	'CLASS TRGWorld1.php',
	'CLASS TRProcEventsCL.php',
	'CLASS TRPlayerList.php',
	'CLASS TRGameClient.php',
	'CLASS TRCommanderCL.php',
	'CLASS TRChatTool.php',
	'CLASS TRImageButton.php',
	'CLASS TRLevelConstructor.php',
	'functions.php',
	'hichkok_brutal.php',
);
$inc='';

foreach($A as $v){
	$name=substr($v,0,-4).'.phb';
	Compile('./'.$v, '../files/scripts/'.$name);
	$inc.="\r\n require_once(getfilename(DOC_ROOT.'/files/scripts/{$name}'));";
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
<?php
{$inc}
?>
FUC;

file_put_contents('../scripts/include.php',$C);
?>