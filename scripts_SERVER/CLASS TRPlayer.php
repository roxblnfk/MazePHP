<?php	// © roxblnfk 2011
class TRPlayer{
	var $GServer;	// ссылка на rnetsv объект
	var $RGame;	// ссылка на TRGame объект - хозяина
	var $RCommander;	// ссылка на TRCommander объектa - хозяина

	var $GID;	// игровой ID
	var $CID;	// ID коннекта

	var $Online=false;
	var $Flags=array('Admin'=>false);
	var $Status=0;	// статусы: 0 дух пользователя, ожидается авторизация/идентификация
		//		1 - идентификация завершена
	var $Name=false;	// false, если не авторизован    иЛи   (string)
	// game
	var $Properties=array();	// color=>[R=0..255,G,B]
	function TRPlayer($GID,$CID,$ol=false){
		$this->GID=$GID;
		$this->CID=$CID;
		$this->Online=$ol;
		//
		$this->Properties['color']=array(rand(0,255),rand(0,255),rand(0,255));
	}
	function processMe(){		// самообработка... авторизация и прочее
		// возможно что-то можно делать и при оффлайне
		if($this->Online===false) return false;
		$this->sockRead();
		if($this->Status==0) return false;
		$this->sockRead();
		$this->GServer->sendStack($this->CID,50);		// отправить стэк
		return true;
	}
	function sockRead(){		// обработать входящие
		$read=$this->GServer->read($this->CID);
		if($read===false) return false; // тут обнаружилось, что клиент вышел... это событие обрабатывается в TRGame->onDisconnect()
		$this->GServer->fuckBuffer($this->CID);
		$rd=&$this->GServer->ReadData[$this->CID];
		//pre($this->GServer->ReadData);
		//var_dump($this->GServer->ReadData);
		$ks=array_keys($rd);
		for($j=sizeof($ks),$i=0;$i<$j;++$i){
			$this->command($rd[$ks[$i]][0],$rd[$ks[$i]][1]);	// выполняем команды
		}
		$this->GServer->ReadData[$this->CID]=array();	// очищаем буфер команд
	}
	function sockSend($command,$content,$stack=true){	// отправить данные
	//	echo $command; var_dump($content);
		return ($stack ? $this->GServer->addSend($this->CID,$command,$content) : $this->GServer->send($this->CID,$command,$content));
	}
	function command($com,&$con){
		$this->RCommander->processPlayer($this->CID,$this->GID,$com,$con);
	}
	function getIP(){
		if($this->Online===false) return false;
		return $this->GServer->Clients[$this->CID]['Address'];
	}
	function auth(&$con){	// авторизация обрабатывается тут
		if(!is_array($con)) return false;	// array(name)
		if(!isset($con['name'])) return false;
		//var_dump($con);
		$name=strval($con['name']);
		$j=strlen($name);
		if($j<3 || $j>20){ $this->sockSend(TRCommander::UserAuth,array(false,'Длина имени должна быть от 3 до 20'),false); return true; }
		 // вдруг игрок с таким ником уже есть?
		if(false!==$this->RGame->getClientGIDby('Name',$name)){ $this->sockSend(TRCommander::UserAuth,array(false,'Пользователь с таким именем уже есть!'),false); return true; }
		if($this->RGame->getOnlineCnt(1) > $this->RGame->Options['MaxPlayers']){	// сервер переполнился, пока этот клиент авторизовывался
			// можно добавить приоритеты пользователей
			$this->RGame->kickPlayer($this->GID,'Сервер переполнен!');
			return false;
		}
		// поиск по базе, если надо...
		// авторизация одобрена.
		$this->Name=$name;
		$this->Status=1;
		$this->sockSend(TRCommander::UserAuth,array(true,'Привет, '.$name.'!'),false);
		$pl=$this->RGame->getPlayersArray(true,1,true);
		for($p=array(),$i=sizeof($pl);--$i>=0;) $p[]=array($pl[$i],$this->RGame->Players[$pl[$i]]->Name,$this->RGame->Players[$pl[$i]]->Properties);
		$this->sockSend(TRCommander::UsersSend,$p,true);
		//$this->RGame->multicast(100500109,$c='К нам присоединился '.$name.'! ip:'.$this->getIP(),false);
		$this->RGame->sendEvent(1,'PlayerJoinedToServer',array($this->GID,$this->Name,$this->Properties,$this->getIP()));
		//
		$this->RGame->RGWorld->onPlayerCreate($this->GID);
		return true;
	}
}
?>