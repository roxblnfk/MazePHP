<?php // © roxblnfk 2012
$dir=dirname(__FILE__);
 require_once(realpath($dir.'/CLASS rxnetsvlw.phb'));
 require_once(realpath($dir.'/CLASS rxnetsv.phb'));
 require_once(realpath($dir.'/CLASS TRGame.phb'));
 require_once(realpath($dir.'/CLASS TRPlayer.phb'));
 require_once(realpath($dir.'/CLASS TRCommander.phb'));
 require_once(realpath($dir.'/CLASS TRGWorld.phb'));
 require_once(realpath($dir.'/CLASS TRGWorld1.phb'));
 require_once(realpath($dir.'/CLASS TRTaskManager.phb'));
 require_once(realpath($dir.'/hichkok_brutal.phb'));
 require_once(realpath($dir.'/CLASS TRGLobby.phb'));
 require_once(realpath($dir.'/CLASS testing.phb'));

$S=new TRGame($TRConfig);
$S->start(true);
?>