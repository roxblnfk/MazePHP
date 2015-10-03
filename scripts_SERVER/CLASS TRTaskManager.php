<?
class TRTaskMamager{	// 
	var $Timing=array();	// id=>time
	var $Functions=array();	// id=>array(0=>FUNCTION(name, array),1=>PARAMETRS(array()))
	var $id=0;
	
	function process(){
		$ks=array_keys($this->Timing);
		$time=microtime(true);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$id=$ks[$i];
			if($this->Timing[$id]>$time) continue;
			call_user_func_array($this->Functions[$id][0],$this->Functions[$id][1]);
			unset($this->Functions[$id],$this->Timing[$id]);
			$time=microtime(true);
		}
	}
	function addTask($time,$function,$params){
		if($time<=microtime(true)) $time+=microtime(true);
		$id=$this->id++;
		$this->Timing[$id]=$time;
		$this->Functions[$id]=array( $function, $params );
		return $id;
	}
	
}	
?>