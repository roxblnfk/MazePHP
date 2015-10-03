<?php	// © roxblnfk 2011
class TRGameClient{
	const VERSION='1.1.0.0';
	var $Players=array();	// сервер должен прислать инфу о игроках... и обновлять её
	var $ServerOptions=array(	// опции сервера
		'MinPlayers'=>0,
		'MaxPlayers'=>0,
		'ServerPort'=>7931,
		'ServerAddr'=>'127.0.0.1',
	);
	var $RCommander;// TRCommanderCL объект
	var $GServer;	// rxnetcl объект
	var $RProcEvents;// TRProcEventsCL объект
	// расширения/инструменты
	var $RGWorld;	// TRGWorld1CL объект
	var $RChatTool;	// TRChatTool объект
	var $RPlayerList;	// TRPlayerList объект
	
	// cadencer
	var $GServerStatus=0;		// статусы сервера: 0 - нихрена
	var $play=false;	// false - цикл frame остановится
	var $ts=1;			// timeSpeed - множитель времени или скорость игры
	var $ft=1;			// для расчёта DeltaTime
	var $fn=0;			// номер фрэйма
	
	// мой статус относительно сервара. Т.е. параметры меня как клиента
	var $Status=0;	// я не авторизоавн
	var $Name='';	// у меня нет имени
	
	function TRGameClient(){
		$this->GServer=new rxnetcl();
		$this->GServer->FB='LR';
		$this->RCommander=new TRCommanderCL();
		$this->RProcEvents=new TRProcEventsCL();
		$this->RChatTool=new TRChatTool();
		$this->RPlayerList=new TRPlayerList();
		$this->RGWorld=new TRGWorld1CL();
		$this->GServer->RGame=&$this;
		$this->RCommander->RGame=&$this;
		$this->RCommander->GServer=&$this->GServer;
		$this->RCommander->RProcEvents=&$this->RProcEvents;
		$this->RCommander->RGWorld=&$this->RGWorld;
		$this->RProcEvents->RGame=&$this;
		$this->RProcEvents->GServer=&$this->GServer;
		$this->RChatTool->RGame=&$this;
		$this->RPlayerList->RGame=&$this;
		$this->RGWorld->RGame=&$this;
		$this->RGWorld->RPlayerList=&$this->RPlayerList;
	}
	function bind($ip,$port){
		$this->Options['ServerAddr']=$ip;
		$this->Options['ServerPort']=min(65535,max(1,(int)$port));	// фильтруем порт
	}
	function start($play=true){
		if(true!==$this->GServer->start($this->Options['ServerAddr'],$this->Options['ServerPort'],$this->Options['MaxPlayers'])) return false;
		$this->sockSend(TRCommanderCL::UserAuth, $c=array(
			'name'=>$this->Name,// my name
			// 'pass'=>'123',		// my password
			// 'lip'=>$this->Name,	// local IP
			// 'rip'=>$this->Name,	// remote IP
			// 'raddr'=>$this->Name,	// remote address
		));
		$this->play=$play;
		$this->frame();
		return true;
	}
	function stop(){
		$this->play=false;
		$this->GServer->stop();
		$this->RGWorld->__destruct();
	}
	function onDisconnect(){	// обрабатываем событие отсоединения от сервера
		$this->stop();
	}
	function frame(){
		if(!isset($this->ft)) $this->ft=microtime(true);
		do{
			$dt=is_null($DeltaTime) ? microtime(true)-$this->ft : $DeltaTime;// реальный DeltaTime
			$gt=$dt*$this->ts;		// игровой DeltaTime
			$this->ft=microtime(true);
			$this->procConnection();
			$this->RGWorld->processingWorld($dt,$gt,$this->fn);
			$this->windowProcessing($dt,$gt);
			
			$this->GServer->process();
			//END FRAME
			$this->RChatTool->endFrameActions();
			
			TRGUI::process();	// интерфейс тоже хочет
			++$this->fn;
		} while ($this->play);
	}
	function windowProcessing($dt,$gt){
		global $APPLICATION;
		$APPLICATION->processMessages();
	}
	// function gameProcessing($dt,$gt){	// просчёт фрейма в игровом движке
	// }
	function getOnlineCnt(){	// сколько игроков онлайн?
		$ks=array_keys($this->Players);
		for($re=0,$i=sizeof($ks);--$i>=0;) if($this->Players[$ks[$i]]->Online) ++$re;
		return $re;
	}
	function procConnection(){		// обрабатывать пользователей
		$this->sockRead();
		$this->GServer->sendStack($this->CID,50);		// отправить стэк
		return true;
	}
	function sockRead(){
		$this->GServer->read();
		$this->GServer->fuckBuffer();
		// разбиваем буфер на пакеты
		$rd=&$this->GServer->ReadData;
		$ks=array_keys($rd);
		for($j=sizeof($ks),$i=0;$i<$j;++$i){
			$this->command($rd[$ks[$i]][0],$rd[$ks[$i]][1]);	// выполняем команды с сервера
		}
		$this->GServer->ReadData=array();	// очищаем буфер команд
		return true;
	}
	function sockSend($command,$content,$stack=true){	// отправить данные
		return ($stack ? $this->GServer->addSend($command,$content) : $this->GServer->send($command,$content));
	}
	function command($com,&$con){	// сюда поступают пакеты с сервера
		return $this->RCommander->processServer($com,$con);
	}
	function log($t,$r=true){
		$c=c('Log->memoLog');
		$c->text.=date('[H:i:s] ').$t.RN;
		$c->perform(182, 0, 0xFFFFFF);
		return $r;
	}
}
?>