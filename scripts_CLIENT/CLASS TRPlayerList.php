<?php	// © roxblnfk 2011
class TRPlayerList{	// список игроков
	var $RGame;	// ссылка на TRGameClient объект - хозяина
	var $Players=array();	// array of array('name'=>name,'status'=>status)
	
	
	
	function addPlayer($id,$name,$status=null,$properties=null){
		$this->delPlayer($id,$reason='при странных обстоятельствах.');	// вдруг такой уже есть?
		$this->Players[$id]=array('name'=>(string)$name);
		if(is_int($status)) $this->Players[$id]['status']=$status;
		$msg='К нам присоединился '.$name.'!';
		///// game
		//color
		if(isset($properties['color'])) if(is_array($properties['color'])) 
			if(sizeof($properties['color'])>=3)
				$this->Players[$id]['color']=array_values($properties['color']);
		if(!isset($this->Players[$id]['color'])) $this->Players[$id]['color']=array(
				rand(0,255),rand(0,255),rand(0,255) );
		
		$this->RGame->RChatTool->addMessage(array(0,mktime(),$con[1],$msg),true);
		return true;
	}
	function delPlayer($id,$reason=''){
		if(!isset($this->Players[$id])) return false;
		$msg='Нас покинул '.$this->Players[$id]['name'].(strlen($reason)>0 ? ': '.$reason : '!');
		$this->RGame->RChatTool->addMessage(array(0,mktime(),$this->Players[$id]['name'],$msg),true);
		unset($this->Players[$id]);
		return true;
	}
	function changeName($id,$name){
		
	}
	function sendetPlayers(&$con){	// сервер прислал список юзеров
		if(!is_array($con)) return false;
		$con=array_values($con);
		for($i=sizeof($con);--$i>=0;){
			$con[$i]=array_values($con[$i]);
			if(sizeof($con[$i])<2) continue;
			$id=intval($con[$i][0]);
			if(isset($this->Players[$id])) continue;
			$this->Players[$id]=array('name'=>(string)$con[$i][1]);
			//color
			if(isset($con[$i]['color'])) if(is_array($con[$i]['color'])) 
				if(sizeof($con[$i]['color'])>=3)
					$this->Players[$id]['color']=array_values($con[$i]['color']);
			if(!isset($this->Players[$id]['color'])) $this->Players[$id]['color']=array(
					rand(0,255),rand(0,255),rand(0,255) );
			//
			if(isset($con[$i][2])) $this->Players[$id]['status']=(int)$con[$i][2];
			$r=true;
		}
		if(isset($r)) $this->RGame->RChatTool->addMessage(null,true);	// обновляем список игроков в чате
		else return false;	// ни кто не добавлен в список
		return true;
	}
}
?>