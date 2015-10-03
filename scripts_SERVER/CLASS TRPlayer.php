<?php	// � roxblnfk 2011
class TRPlayer{
	var $GServer;	// ������ �� rnetsv ������
	var $RGame;	// ������ �� TRGame ������ - �������
	var $RCommander;	// ������ �� TRCommander ������a - �������

	var $GID;	// ������� ID
	var $CID;	// ID ��������

	var $Online=false;
	var $Flags=array('Admin'=>false);
	var $Status=0;	// �������: 0 ��� ������������, ��������� �����������/�������������
		//		1 - ������������� ���������
	var $Name=false;	// false, ���� �� �����������    ���   (string)
	// game
	var $Properties=array();	// color=>[R=0..255,G,B]
	function TRPlayer($GID,$CID,$ol=false){
		$this->GID=$GID;
		$this->CID=$CID;
		$this->Online=$ol;
		//
		$this->Properties['color']=array(rand(0,255),rand(0,255),rand(0,255));
	}
	function processMe(){		// �������������... ����������� � ������
		// �������� ���-�� ����� ������ � ��� ��������
		if($this->Online===false) return false;
		$this->sockRead();
		if($this->Status==0) return false;
		$this->sockRead();
		$this->GServer->sendStack($this->CID,50);		// ��������� ����
		return true;
	}
	function sockRead(){		// ���������� ��������
		$read=$this->GServer->read($this->CID);
		if($read===false) return false; // ��� ������������, ��� ������ �����... ��� ������� �������������� � TRGame->onDisconnect()
		$this->GServer->fuckBuffer($this->CID);
		$rd=&$this->GServer->ReadData[$this->CID];
		//pre($this->GServer->ReadData);
		//var_dump($this->GServer->ReadData);
		$ks=array_keys($rd);
		for($j=sizeof($ks),$i=0;$i<$j;++$i){
			$this->command($rd[$ks[$i]][0],$rd[$ks[$i]][1]);	// ��������� �������
		}
		$this->GServer->ReadData[$this->CID]=array();	// ������� ����� ������
	}
	function sockSend($command,$content,$stack=true){	// ��������� ������
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
	function auth(&$con){	// ����������� �������������� ���
		if(!is_array($con)) return false;	// array(name)
		if(!isset($con['name'])) return false;
		//var_dump($con);
		$name=strval($con['name']);
		$j=strlen($name);
		if($j<3 || $j>20){ $this->sockSend(TRCommander::UserAuth,array(false,'����� ����� ������ ���� �� 3 �� 20'),false); return true; }
		 // ����� ����� � ����� ����� ��� ����?
		if(false!==$this->RGame->getClientGIDby('Name',$name)){ $this->sockSend(TRCommander::UserAuth,array(false,'������������ � ����� ������ ��� ����!'),false); return true; }
		if($this->RGame->getOnlineCnt(1) > $this->RGame->Options['MaxPlayers']){	// ������ ������������, ���� ���� ������ ���������������
			// ����� �������� ���������� �������������
			$this->RGame->kickPlayer($this->GID,'������ ����������!');
			return false;
		}
		// ����� �� ����, ���� ����...
		// ����������� ��������.
		$this->Name=$name;
		$this->Status=1;
		$this->sockSend(TRCommander::UserAuth,array(true,'������, '.$name.'!'),false);
		$pl=$this->RGame->getPlayersArray(true,1,true);
		for($p=array(),$i=sizeof($pl);--$i>=0;) $p[]=array($pl[$i],$this->RGame->Players[$pl[$i]]->Name,$this->RGame->Players[$pl[$i]]->Properties);
		$this->sockSend(TRCommander::UsersSend,$p,true);
		//$this->RGame->multicast(100500109,$c='� ��� ������������� '.$name.'! ip:'.$this->getIP(),false);
		$this->RGame->sendEvent(1,'PlayerJoinedToServer',array($this->GID,$this->Name,$this->Properties,$this->getIP()));
		//
		$this->RGame->RGWorld->onPlayerCreate($this->GID);
		return true;
	}
}
?>