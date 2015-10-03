<?php	// � roxblnfk 2011
class TRCommander{	// ������� � ������� ���� �� ���������
	var $GServer;	// ������ �� rnetsv ������
	var $RGame;	// ������ �� TRGame ������
	var $RGWorld;	// ������ �� TRGWorld1 ������
	
	const EventCode=1234567890;	// ������������� �������� �������
	const ChatMess =100500447;	// ������ ���-��������� //str
	const StrToLog =100500109;	// � ������ � ��� ������� (log)(109)	// ����� ��� �������
	const ObjsControl=5000005;	// ������� ����� � ����������� ��������� �� ������� �������
	const ObjsFullI=5000004;	// ����������� ������ ���������� �� �������
	const ObjsHide =5000003;	// ������ �����
	const ObjsChang=5000002;	// �������� ������	// array
	const ObjsDestr=5000001;	// ���������� ������� // array(oid...)
	const ObjsCreat=5000000;	// ������� ������ // array('id'=>id,'type'=>type,'pX'=>pX,'pY'=>pY)
	const UserAuth =101001;		// ������������ // array(name)
	const UsersSend=101003;		// ������������ ������ �������������
	// game
	/* const TankControl=74543;	// ���� ��������� ������ */
	const LevelParams=9913831;	// ������� ��������� ��� ��������� ������
	const LevelFinished=9913832;// ������ �������� ������, ��� �� ������ �������!
	
	function processPlayer($cid,$gid,$com,&$con){	// ��������� ������� �� ������
		$Players=&$this->RGame->Players;	// ��� ��������
		$Player=&$this->RGame->Players[$gid];	// ��� ��������
		//echo $com; var_dump($con);
		if($Player->Status==0){	// ���������������� ������������
			switch($com){	// $com ������������� ���������� �� 0 �� 1023!
				case self::UserAuth : return $Player->auth($con);	// �����������
				default : $def=true; break;
			}
			return (isset($def) ? false : true);
		}elseif($Player->Status==1){	// �������������� ������������, ��, ��������, ������ � ����� (���� ��������������)
			switch($com){	// $com ������������� ���������� �� 0 �� 1023!
				/* case 99956:	// ���������� �������
					$this->RGWorld->w1_destroyObjs(); break; */
				/* case 99957:	// ����� �������
					$this->RGWorld->w1_mixObjs(); break; */
				/* case 99958:	// create ball...)
					$this->RGWorld->w1_createBall($gid,$con); break; */
				/* case 99961:	// hide...
					$this->RGWorld->w1_hideObjs($con); break; */
				/* case 99962:	// show...
					$this->RGWorld->w1_showObjs($con); break; */
				case TRCommander::ObjsChang :
					$this->RGWorld->objectChangedCL($gid,$con); break;
				/* case TRCommander::TankControl :
					$this->RGWorld->w1_TankControl($gid,$con); break; */
				default : $def=true; break;
			}
			if(!isset($def)) return true; // ����� �� ����������� ����� ��� ������ ���� - ����� ����� )
		}
		// ��� ����, � ���� ������ ������ 0
		switch($com){	// $com ������������� ���������� �� 0 �� 1023!
			/* case 1025 : 	// �������� �������� ����
				if(is_numeric($con)) return false;
				//if($con<0) return false;
				if($Players[$gid]->Flags['Admin']){
					$this->RGame->ts=$con;
					$this->RGame->multicast($com,$con,false);	// �������� ���� ��������
				}
			break; */
			case self::StrToLog:	// � ������ � ��� ������� (log)(109)	// ����� ��� �������
				if(!is_string($con))return false;
				$this->RGame->log($con); // ������� � ��� ������
				$this->RGame->multicast($com,$c=date('LOG: [H:i:s]').$Players[$gid]->getIP().' : '.$con.RN,false);	// �������� ���� ��������
			break;
			case self::ChatMess:	// ���
				if(is_string($con)){
					$str=trim(strval($con));
					$this->RGame->log('> Chat message from '.$gid.': '.$str); // ������� � ��� ������
					$this->RGame->multicast($com,$c=array(2,microtime(true),$Player->GID/* $Players[$gid]->getIP() */,$str),false);	// �������� ���� ��������
				}
			break;
		}
	}
	/* function processServer($com,&$con,$cid=null,$gid=null){	// ��������� ���������� �������
		switch($com){
			case 0:	// ������������� ping
			break;
			case 1:	// ������ �� ping
				if(is_int($con)) $this->GServer->send($cid,0,$con); 
			break;
			case 100500109:	// � ������ � ��� �������� (log)(109)	// ����� ��� ������� 
				
			break;
		}
	} */
}
?>