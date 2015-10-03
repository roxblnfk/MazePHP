<?php	// © roxblnfk 2011
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
for($i=0,$j=sizeof($A);$i<$j;++$i)
	require $A[$i];

$S=new TRGame($TRConfig);
//$S->bind('0.0.0.0',91);
$S->start(true);

?>