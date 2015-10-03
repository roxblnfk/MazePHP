<?php	// © roxblnfk 2011
class TRGame{
	const VERSION='1.1.0.0';
	var $Players=array();		// array of TRPlayer
	var $Options=array(
		'MinPlayers'=>1,
		'MaxPlayers'=>8,
		'MaxConnects'=>16,
		'ServerPort'=>7931,
		'ServerAddr'=>'0.0.0.0',
		'ServerName'=>'',
	);
	var $RCommander;// TRCommander объект
	var $GServer;	// rnetsv объект
	var $RGWorld;	// TRGWorld объект - игровой мир
	var $RTaskMamager;	// TRGWorld объект - игровой мир
	var $RGLobby;	// TRGLobby объект - лобби на внешнем сайте
	// cadencer
	var $GStatus=0;		// статусы сервера: 0 - нихрена
	var $play=false;	// false - цикл frame остановится
	var $ts=1;			// timeSpeed - множитель времени или скорость игры
	
	function TRGame($conf){
		$this->GServer=new rxnetsv();
		$this->GServer->FB='LR';
		$this->RCommander=new TRCommander();
		$this->RGWorld=new TRGWorld1($this);
		$this->RGLobby=new TRGLobby('maze.roxblnfk.16mb.com');
		$this->RGLobby->RGame=&$this;
		$this->RGLobby->RGWorld=&$this->RGWorld;
		$this->GServer->RGame=&$this;
		$this->RCommander->RGame=&$this;
		$this->RCommander->GServer=&$this->GServer;
		$this->RCommander->RGWorld=&$this->RGWorld;
		$this->RTaskMamager=new TRTaskMamager();
		// config
		$this->RGLobby->Enabled=(bool)$conf['globallobby'];
		$this->bind($conf['addr'],$conf['port']);
		$this->RGWorld->WorldSize=array($conf['wdth'],$conf['hght']);
		$this->Options['MaxPlayers']=$conf['maxplayers'];
		$this->Options['MaxConnects']=$conf['maxconnections'];
		$this->Options['ServerName']=$conf['name'];
	}
	function bind($ip,$port){
		$this->Options['ServerAddr']=$ip;
		$this->Options['ServerPort']=(int)$port;
	}
	function start($play=true){
		if(true!==$this->GServer->start($this->Options['ServerAddr'],$this->Options['ServerPort'],$this->Options['MaxConnects']-1)) return false;
		$this->play=$play;
		$this->frame();
	}
	function stop(){
		$this->play=false;
		$this->GServer->stop();
	}
	function onDisconnect($cid){
		$gid=$this->getClientGIDby('CID',$cid);
		if(false===$gid) return $this->GServer->kick($cid,'','Багоюзер!');
		$this->log('Клиент gid:'.$gid.' отсоединился от сервера...');
		// если надо, то все манипуляции с инфой о подключении из класса rxnetsv протокола производить тут^! на след. строке инфа уничтожится
		$this->GServer->kick($cid,'','Сам вышел!');
		$Player=&$this->Players[$gid];
		$Player->Online=false;
		//if($Player->Status > 2) return true;	// этот игрок будет существовать некоторое время..
		unset($Player);
		$this->kickPlayer($gid);
		return true;
	}
	function frame(){
		$ft=microtime(true);
		$fn=0;	// номер текущего фрейма
		do{
			$dt=microtime(true)-$ft;// реальный DeltaTime
			$gt=$dt*$this->ts;		// игровой DeltaTime
			$ft=microtime(true);
			//if($this->GServer->Server['Connects'])
			//$this->GServer
			$this->pickPlayers();	// подобрать пользователя
			$this->procPlayers();	// обработать пользователей
			$this->RGWorld->processingWorld($dt,$gt,$fn);
			$this->GServer->process();	// обработка сетевым протоколом своих нужд
			$this->RTaskMamager->process();	// TaskManages
			$this->RGLobby->process();	// TRGLobby
			
			++$fn;
			//$this->log(RN.'------frame '.$fn.' dt='.round($dt,3).'s'.' players:'.sizeof($this->getPlayersArray($online=true)));
			//usleep(500000);
			usleep(100);
		}while($this->play);
	}
	function getPlayersArray($online=null, $status=0, $norm=false){	// IF $NORM return array(GIDs) ELSE return array of []=>array(gid,cid)
		$a=array();
		if(($j=sizeof($this->Players))>0){
			$ks=array_keys($this->Players);
			for($i=0;$i<$j;++$i){
				$k=$ks[$i];
				$v=&$this->Players[$k];
				if($v->Status<$status) continue;
				if(is_bool($online) && $online!==$v->Online) continue;
					/*0=>GameID,1=>ConnectionID*/
				if($norm) $a[]=$k;
				else $a[]=array($k,$v->CID);
			}
		}
		return $a;
	}
	function getOnlineCnt($status=0){	// сколько игроков онлайн?
		$ks=array_keys($this->Players);
		$re=0;
		for($i=sizeof($ks);--$i>=0;){
			$Player=&$this->Players[$ks[$i]];
			if($Player->Online && $Player->Status>=$status) ++$re;
		}
		return $re;
	}
	function getClientGIDby($nam,$val){	// узнать игровой ID клиента по его параметру $nam зная величину этого параметра $val
		$ks=array_keys($this->Players);
		for($i=sizeof($ks);--$i>=0;)
			if($this->Players[$ks[$i]]->$nam===$val) return $ks[$i];
		return false;
	}
	// function command($com,$con){	// это внутренние команды сервера в зависимости от изменений...
	// }
	// function gameProcessing($dt,$gt){	// просчёт фрейма
	// }
	function sendEvent($status,$event,$value,$stack=false,$list=null){	/// отправить ВСЕМ (или тем кто в $list) со статусом >=$status событие $event со значением $val
		if(is_null($list)) $a=$this->getPlayersArray($online=true);
		else $a=$list;
		for($i=0,$j=sizeof($a);$i<$j;++$i){
			$k=$a[$i][0];
			if(!isset($this->Players[$k])) continue;	// от группового суицида
			$v=&$this->Players[$k];
			if($v->Status >= $status)
				$v->sockSend(TRCommander::EventCode,$c=array($event,$value),$stack);
		}
		return true;
	}
	function multicast($command,&$content,$stack=true,$status=0,$plist=null){	// отправить всем или тем кто в $plist=array(GIDs)
		if(is_array($plist)){
			$a=array();
			for($i=sizeof($plist);--$i>=0;) $a[]=array($plist[$i]);
		}else $a=$this->getPlayersArray($online=true);
		for($i=0,$j=sizeof($a);$i<$j;++$i){
			$k=$a[$i][0];
			if(!isset($this->Players[$k])) continue;	// от массового суицида
			if($this->Players[$k]->Status < $status) continue;
			$this->Players[$k]->sockSend($command,$content,$stack);
			//echo '<send>'.$command.'</send>';
		}
		return true;
	}
	function kickPlayer($gid,$reason=''){	// выкинуть клиента
		if(!isset($this->Players[$gid])) return false;
		$Player=&$this->Players[$gid];
		if($Player->Online) $this->GServer->kick($Player->CID,$reason,$reason);
		$msg=array($gid,$Player->Name); $status=$Player->Status;
		$this->Players[$gid]=false;
		unset($this->Players[$gid],$Player);
		$this->log('Клиент уничтожен: '.$gid);
		if($status>=TRGWorld::GS){
			$this->RGWorld->onPlayerDestroy($gid);
		}
		if($status>0){
			$this->sendEvent(1,'PlayerLeaveServer',$msg,false);
		}
		return true;
	}
	function pickPlayers(){		// присоединить юзеров, если надо и есть возможность
		$cid=$this->GServer->pick();
		if(is_bool($cid)) return false;
		if(count($this->Players)>0) $gid=max(array_keys($this->Players))+1;
		else $gid=0;
		$this->Players[$gid]=new TRPlayer($gid,$cid,true);
		$this->Players[$gid]->GServer=&$this->GServer;
		$this->Players[$gid]->RGame=&$this;
		$this->Players[$gid]->RCommander=&$this->RCommander;
		$this->log('Присоединился товарищ! connection gid: '.$gid.' cid:'.$cid);
		if($this->getOnlineCnt(1) >= $this->Options['MaxPlayers']){
			//echo $this->getOnlineCnt(1);
			//echo $this->getOnlineCnt(0);
			//echo 'kick momentaly1!'.RN;
			$this->kickPlayer($gid,'Сервер переполнен!');
			//echo 'kick momentaly2!'.RN;
			return false;
		}
		//$this->multicast(100500109,$c='Кто-то присоединился к нам! '.$this->Players[$gid]->getIP(),false);
		return true;
	}
	function procPlayers(){		// обрабатывать пользователей
		$ks=array_keys($this->Players);
		for($i=sizeof($ks);$i--;){
			if(!isset($this->Players[$ks[$i]])) continue; // от множественного суицида
			$this->Players[$ks[$i]]->processMe();
		}
	}
	function log($t,$r=true){
		echo mb_convert_encoding($t.RN, 'CP866', 'CP1251');
		return $r;
		// $f=fopen(DOC_ROOT.'/server_log.txt','a');
		// flock($f,LOCK_EX);
		// fputs($f,date('[d.m.Y H:i:s] ').$t.RN);
		// flock($f,LOCK_UN);
		// fclose($f);
	}
	
	
}
?>