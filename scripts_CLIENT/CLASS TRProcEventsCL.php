<?php	// © roxblnfk 2011
class TRProcEventsCL{// ОБРАБОТКА СОБЫТИЙ
	var $GServer;	// ссылка на rxnetcl объект
	var $RGame;	// ссылка на TRGame объект
	// каждое событие равно названию функции// желательно, чтобы они возвращали bool
	function PlayerLeaveServer($con){	// array(id,name)
		return $this->RGame->RPlayerList->delPlayer(intval($con[0]),'');
		//return $this->RGame->log('Нас покинул '.$con[1].'!', true);
	}
	function PlayerJoinedToServer($con){	// array(id,name,properties[,ip])
		return $this->RGame->RPlayerList->addPlayer(intval($con[0]),strval($con[1]),$status=null,$con[2]);
		//return $this->RGame->log('К нам присоединился '.$con[1].'! ip:'.$con[2], true);
	}
	function PlayerChangedName($con){	// N/A
		return true;
	}
	function LevelLoad($con){	/*
		array(
			'title'=>
			'functionName'=> //функция для генерации уровня
			'functionPars'=> //параметры для функции
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
	function GameMessage($con){	// с игровое сообщение
		$this->RGame->RGWorld->GameMessage($con);
	}
}
?>