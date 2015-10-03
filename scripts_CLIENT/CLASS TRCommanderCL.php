<?php	// © roxblnfk 2011
class TRCommanderCL{	// команды с сокетов сюда на обработку
	var $GServer;	// ссылка на rxnetcl объект
	var $RGame;	// ссылка на TRGame объект
	var $RProcEvents;	// ссылка на TRProcEventsCL объект
	var $RGWorld;	// ссылка на TRGWorld1CL объект - игровой мир
	var $UI=array();// объекты формы
	
	const EventCode=1234567890;
	const ChatMess =100500447;	// чат TRChatTool array(type,time,user,message)
	const StrToLog =100500109;	// в запись в лог от сервера (log)(109)	// чисто дл€ отладки
	const ObjsControl=5000005;	// говорит юзеру о возможности изменени€ им свойств объекта
	const ObjsFullI=5000004;	// присылаетс€ полна€ информаци€ об объекте, если объекта нет то его надо создать
	const ObjsHide =5000003;	// объект скрыт
	const ObjsChang=5000002;	// изменить объект	// array
	const ObjsDestr=5000001;	// уничтожить объекты // array(oid...)
	const ObjsCreat=5000000;	// создать объект // array('id'=>id,'type'=>type,'pX'=>pX,'pY'=>pY)
	const UserAuth =101001;		// авторризаци€ // array(bool ok?, str reason)
	const UsersSend=101003;		// приходит список пользователей
	/// game
	//const TankControl=74543;	// управление юзерским танком
	const LevelParams=9913831;	// приход€т параметры дл€ генерации уровн€
	const LevelFinished=9913832;// сервер сообщает игроку, что он прошЄл уровень!
	
	function TRCommanderCL(){
	}
	function processServer($com,&$con){	// обработка команд от сервера
		// $com в диапазоне от 0 до 1023 резервируетс€ сетевым протоколом!

		// Ќ≈«ј¬»—»ћџ≈ ќ“ —“ј“”—ј —ќЅџ“»я
		switch($com){
			case self::StrToLog:	// в запись в лог от сервера (log)(109)	// чисто дл€ отладки
				if(is_string($con)) $this->RGame->log($con); 
			break;
			case self::EventCode:	// событие на сервере... может быть чем угодно :)	// array(event, value)
				if(!is_array($con)) return false;
				if(sizeof($con)<2) return false;
				$con=array_values($con);
				if(!method_exists($this->RProcEvents, $mthd=strval($con[0]))) return false;
				return $this->RProcEvents->$mthd($con[1]);
			break;
			case self::UsersSend : return $this->RGame->RPlayerList->sendetPlayers($con);	// приходит список пользователей
			default : $def=true; break;
		}
		if(!isset($def)) return true; else unset($def);
		
		// ____________ «ј¬»—»ћџ≈ ќ“ —“ј“”—ј —ќЅџ“»я
		if($this->RGame->Status==0){	// я Ќ≈ј¬“ќ–»«ќ¬јЌЌџ… ѕќЋ№«ќ¬ј“≈Ћ№
			$this->RGame->log($com); 
			switch($com){	// $com резервируетс€ протоколом от 0 до 1023!
				case self::UserAuth:	// авторизаци€
					if(!is_array($con)) return false;	// array(bool ok?, str reason)
					if(sizeof($con)<2) return false;
					if($con[0]){	// авторизаци€ прокатила!
						$this->RGame->log('¬ы зашли на сервер как '.$this->RGame->Name);
						$this->RGame->Status=1;
					}else{			// авторизаци€ не прокатила!
						$this->RGame->log('¬ы не прошли авторизацию!'.RN.$con[1]);
						$this->RGame->stop();
						alert('¬ы не прошли авторизацию!'.RN.$con[1]);
					}
				break;
				default : $def=true; break;
			}
			return (isset($def) ? false : true);
		}elseif($this->RGame->Status==1){	// я ј¬“ќ–»«ќ¬јЌЌџ… ѕќЋ№«ќ¬ј“≈Ћ№
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
			if(!isset($def)) return true; // чтобы не перегружать особо или сделал дело - гул€й смело )
		}
		switch($com){
			case 1025 : 	// изменить скорость игры
				if(is_numeric($con)) return false;
				//if($con<0) return false;
				$this->RGame->ts=$con;
			break;
			case self::ChatMess: return $this->RGame->RChatTool->addMessage($con);// чат TRChatTool array(type,time,user,message)
		}
	}
	function processMe($com,&$con){	// обработка внутренних комманд
		// switch($com){
		// }
	}
}
?>