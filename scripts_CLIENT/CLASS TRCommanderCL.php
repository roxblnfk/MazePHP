<?php	// � roxblnfk 2011
class TRCommanderCL{	// ������� � ������� ���� �� ���������
	var $GServer;	// ������ �� rxnetcl ������
	var $RGame;	// ������ �� TRGame ������
	var $RProcEvents;	// ������ �� TRProcEventsCL ������
	var $RGWorld;	// ������ �� TRGWorld1CL ������ - ������� ���
	var $UI=array();// ������� �����
	
	const EventCode=1234567890;
	const ChatMess =100500447;	// ��� TRChatTool array(type,time,user,message)
	const StrToLog =100500109;	// � ������ � ��� �� ������� (log)(109)	// ����� ��� �������
	const ObjsControl=5000005;	// ������� ����� � ����������� ��������� �� ������� �������
	const ObjsFullI=5000004;	// ����������� ������ ���������� �� �������, ���� ������� ��� �� ��� ���� �������
	const ObjsHide =5000003;	// ������ �����
	const ObjsChang=5000002;	// �������� ������	// array
	const ObjsDestr=5000001;	// ���������� ������� // array(oid...)
	const ObjsCreat=5000000;	// ������� ������ // array('id'=>id,'type'=>type,'pX'=>pX,'pY'=>pY)
	const UserAuth =101001;		// ������������ // array(bool ok?, str reason)
	const UsersSend=101003;		// �������� ������ �������������
	/// game
	//const TankControl=74543;	// ���������� �������� ������
	const LevelParams=9913831;	// �������� ��������� ��� ��������� ������
	const LevelFinished=9913832;// ������ �������� ������, ��� �� ������ �������!
	
	function TRCommanderCL(){
	}
	function processServer($com,&$con){	// ��������� ������ �� �������
		// $com � ��������� �� 0 �� 1023 ������������� ������� ����������!

		// ����������� �� ������� �������
		switch($com){
			case self::StrToLog:	// � ������ � ��� �� ������� (log)(109)	// ����� ��� �������
				if(is_string($con)) $this->RGame->log($con); 
			break;
			case self::EventCode:	// ������� �� �������... ����� ���� ��� ������ :)	// array(event, value)
				if(!is_array($con)) return false;
				if(sizeof($con)<2) return false;
				$con=array_values($con);
				if(!method_exists($this->RProcEvents, $mthd=strval($con[0]))) return false;
				return $this->RProcEvents->$mthd($con[1]);
			break;
			case self::UsersSend : return $this->RGame->RPlayerList->sendetPlayers($con);	// �������� ������ �������������
			default : $def=true; break;
		}
		if(!isset($def)) return true; else unset($def);
		
		// ____________ ��������� �� ������� �������
		if($this->RGame->Status==0){	// � ���������������� ������������
			$this->RGame->log($com); 
			switch($com){	// $com ������������� ���������� �� 0 �� 1023!
				case self::UserAuth:	// �����������
					if(!is_array($con)) return false;	// array(bool ok?, str reason)
					if(sizeof($con)<2) return false;
					if($con[0]){	// ����������� ���������!
						$this->RGame->log('�� ����� �� ������ ��� '.$this->RGame->Name);
						$this->RGame->Status=1;
					}else{			// ����������� �� ���������!
						$this->RGame->log('�� �� ������ �����������!'.RN.$con[1]);
						$this->RGame->stop();
						alert('�� �� ������ �����������!'.RN.$con[1]);
					}
				break;
				default : $def=true; break;
			}
			return (isset($def) ? false : true);
		}elseif($this->RGame->Status==1){	// � �������������� ������������
			switch($com){
				case self::ObjsCreat : $this->RGWorld->createObject($con); break;
				case self::ObjsChang : $this->RGWorld->changeObjectSV($con); break;
				case self::ObjsDestr : $this->RGWorld->destroyObjects($con); break;
				case self::ObjsFullI : $this->RGWorld->sendetObject($con); break;
				case self::ObjsHide  : $this->RGWorld->hideObject($con); break;
				case self::ObjsControl:$this->RGWorld->setControl($con); break;
				case self::LevelParams: $this->RGWorld->LevelLoad($con); break;
				case self::LevelFinished: $this->RGWorld->LevelFinished($con); break;
				default : $def=true; break;
			}
			if(!isset($def)) return true; // ����� �� ����������� ����� ��� ������ ���� - ����� ����� )
		}
		switch($com){
			case 1025 : 	// �������� �������� ����
				if(is_numeric($con)) return false;
				//if($con<0) return false;
				$this->RGame->ts=$con;
			break;
			case self::ChatMess: return $this->RGame->RChatTool->addMessage($con);// ��� TRChatTool array(type,time,user,message)
		}
	}
	function processMe($com,&$con){	// ��������� ���������� �������
		// switch($com){
		// }
	}
}
?>