<?php	// � roxblnfk 2011
class TRProcEventsCL{// ��������� �������
	var $GServer;	// ������ �� rxnetcl ������
	var $RGame;	// ������ �� TRGame ������
	// ������ ������� ����� �������� �������// ����������, ����� ��� ���������� bool
	function PlayerLeaveServer($con){	// array(id,name)
		return $this->RGame->RPlayerList->delPlayer(intval($con[0]),'');
		//return $this->RGame->log('��� ������� '.$con[1].'!', true);
	}
	function PlayerJoinedToServer($con){	// array(id,name,properties[,ip])
		return $this->RGame->RPlayerList->addPlayer(intval($con[0]),strval($con[1]),$status=null,$con[2]);
		//return $this->RGame->log('� ��� ������������� '.$con[1].'! ip:'.$con[2], true);
	}
	function PlayerChangedName($con){	// N/A
		return true;
	}
	function LevelLoad($con){	/*
		array(
			'title'=>
			'functionName'=> //������� ��� ��������� ������
			'functionPars'=> //��������� ��� �������
			'positionStart'=>array(x,y)
			'positionFinish'=>array(x,y)
		)
		//*/
		$this->RGame->RGWorld->LevelLoad($con);
	}
	function LevelStart($con){	// 
		$this->RGame->RGWorld->LevelStart($con);
		return true;
	}
	function GameMessage($con){	// � ������� ���������
		$this->RGame->RGWorld->GameMessage($con);
	}
}
?>