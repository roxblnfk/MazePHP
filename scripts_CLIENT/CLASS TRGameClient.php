<?php	// � roxblnfk 2011
class TRGameClient{
	const VERSION='1.1.0.0';
	var $Players=array();	// ������ ������ �������� ���� � �������... � ��������� �
	var $ServerOptions=array(	// ����� �������
		'MinPlayers'=>0,
		'MaxPlayers'=>0,
		'ServerPort'=>7931,
		'ServerAddr'=>'127.0.0.1',
	);
	var $RCommander;// TRCommanderCL ������
	var $GServer;	// rxnetcl ������
	var $RProcEvents;// TRProcEventsCL ������
	// ����������/�����������
	var $RGWorld;	// TRGWorld1CL ������
	var $RChatTool;	// TRChatTool ������
	var $RPlayerList;	// TRPlayerList ������
	
	// cadencer
	var $GServerStatus=0;		// ������� �������: 0 - �������
	var $play=false;	// false - ���� frame �����������
	var $ts=1;			// timeSpeed - ��������� ������� ��� �������� ����
	var $ft=1;			// ��� ������� DeltaTime
	var $fn=0;			// ����� ������
	
	// ��� ������ ������������ �������. �.�. ��������� ���� ��� �������
	var $Status=0;	// � �� �����������
	var $Name='';	// � ���� ��� �����
	
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
		$this->Options['ServerPort']=min(65535,max(1,(int)$port));	// ��������� ����
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
	function onDisconnect(){	// ������������ ������� ������������ �� �������
		$this->stop();
	}
	function frame(){
		if(!isset($this->ft)) $this->ft=microtime(true);
		do{
			$dt=is_null($DeltaTime) ? microtime(true)-$this->ft : $DeltaTime;// �������� DeltaTime
			$gt=$dt*$this->ts;		// ������� DeltaTime
			$this->ft=microtime(true);
			$this->procConnection();
			$this->RGWorld->processingWorld($dt,$gt,$this->fn);
			$this->windowProcessing($dt,$gt);
			
			$this->GServer->process();
			//END FRAME
			$this->RChatTool->endFrameActions();
			
			TRGUI::process();	// ��������� ���� �����
			++$this->fn;
		} while ($this->play);
	}
	function windowProcessing($dt,$gt){
		global $APPLICATION;
		$APPLICATION->processMessages();
	}
	// function gameProcessing($dt,$gt){	// ������� ������ � ������� ������
	// }
	function getOnlineCnt(){	// ������� ������� ������?
		$ks=array_keys($this->Players);
		for($re=0,$i=sizeof($ks);--$i>=0;) if($this->Players[$ks[$i]]->Online) ++$re;
		return $re;
	}
	function procConnection(){		// ������������ �������������
		$this->sockRead();
		$this->GServer->sendStack($this->CID,50);		// ��������� ����
		return true;
	}
	function sockRead(){
		$this->GServer->read();
		$this->GServer->fuckBuffer();
		// ��������� ����� �� ������
		$rd=&$this->GServer->ReadData;
		$ks=array_keys($rd);
		for($j=sizeof($ks),$i=0;$i<$j;++$i){
			$this->command($rd[$ks[$i]][0],$rd[$ks[$i]][1]);	// ��������� ������� � �������
		}
		$this->GServer->ReadData=array();	// ������� ����� ������
		return true;
	}
	function sockSend($command,$content,$stack=true){	// ��������� ������
		return ($stack ? $this->GServer->addSend($command,$content) : $this->GServer->send($command,$content));
	}
	function command($com,&$con){	// ���� ��������� ������ � �������
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