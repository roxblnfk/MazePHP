<?php

function is_keys(&$ar,$ks){
	if(!is_array($ks)) $ks=array($ks);
	else $ks=array_values($ks);
	for($i=sizeof($ks);--$i>=0;) if(!isset($ar[$ks[$i]])) if(!array_key_exists($ks[$i],$ar)) return false;
	return true;
}
function _mkdir($path){
	$path=str_replace("\\",'/',$path);
	$p=explode('/',$path);
	if(count($p)>0){
		$ok=true;$str='';
		foreach($p as $d)
			if($ok && $d!=''){
				$str.=($str=='' ? '' : '/').$d;
				if(!is_dir($str)) $ok=mkdir($str);
			}
	}else return false;
	return $ok;
}

?>