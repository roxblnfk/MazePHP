<?php	// © roxblnfk 2011
class TRCommander{	// команды с сокетов сюда на обработку
	var $GServer;	// ссылка на rnetsv объект
	var $RGame;	// ссылка на TRGame объект
	var $RGWorld;	// ссылка на TRGWorld1 объект
	
	const EventCode=1234567890;	// резервировано системой событий
	const ChatMess =100500447;	// пришло чат-сообщение //str
	const StrToLog =100500109;	// в запись в лог сервера (log)(109)	// чисто для отладки
	const ObjsControl=5000005;	// говорит юзеру о возможности изменения им свойств объекта
	const ObjsFullI=5000004;	// присылается полная информация об объекте
	const ObjsHide =5000003;	// объект скрыт
	const ObjsChang=5000002;	// изменить объект	// array
	const ObjsDestr=5000001;	// уничтожить объекты // array(oid...)
	const ObjsCreat=5000000;	// создать объект // array('id'=>id,'type'=>type,'pX'=>pX,'pY'=>pY)
	const UserAuth =101001;		// авторризация // array(name)
	const UsersSend=101003;		// отправляется список пользователей
	// game
	/* const TankControl=74543;	// юзер управляет танком */
	const LevelParams=9913831;	// послать параметры для генерации уровня
	const LevelFinished=9913832;// сервер сообщает игроку, что он прошёл уровень!
	
	function processPlayer($cid,$gid,$com,&$con){	// обработка комманд от игрока
		$Players=&$this->RGame->Players;	// для удобства
		$Player=&$this->RGame->Players[$gid];	// для удобства
		//echo $com; var_dump($con);
		if($Player->Status==0){	// НЕАВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ
			switch($com){	// $com резервируется протоколом от 0 до 1023!
				case self::UserAuth : return $Player->auth($con);	// авторизация
				default : $def=true; break;
			}
			return (isset($def) ? false : true);
		}elseif($Player->Status==1){	// АВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ, НО, НАПРИМЕР, ТОЛЬКО В ЛОББИ (ЕСЛИ ПОДДЕРЖИВАЕТСЯ)
			switch($com){	// $com резервируется протоколом от 0 до 1023!
				/* case 99956:	// уничтожить объекты
					$this->RGWorld->w1_destroyObjs(); break; */
				/* case 99957:	// пнуть объекты
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
			if(!isset($def)) return true; // чтобы не перегружать особо или сделал дело - гуляй смело )
		}
		// ДЛЯ ВСЕХ, У КОГО СТАТУС БОЛЬШЕ 0
		switch($com){	// $com резервируется протоколом от 0 до 1023!
			/* case 1025 : 	// изменить скорость игры
				if(is_numeric($con)) return false;
				//if($con<0) return false;
				if($Players[$gid]->Flags['Admin']){
					$this->RGame->ts=$con;
					$this->RGame->multicast($com,$con,false);	// отправим всем клиентам
				}
			break; */
			case self::StrToLog:	// в запись в лог сервера (log)(109)	// чисто для отладки
				if(!is_string($con))return false;
				$this->RGame->log($con); // запишем в лог серера
				$this->RGame->multicast($com,$c=date('LOG: [H:i:s]').$Players[$gid]->getIP().' : '.$con.RN,false);	// отправим всем клиентам
			break;
			case self::ChatMess:	// чат
				if(is_string($con)){
					$str=trim(strval($con));
					$this->RGame->log('> Chat message from '.$gid.': '.$str); // запишем в лог серера
					$this->RGame->multicast($com,$c=array(2,microtime(true),$Player->GID/* $Players[$gid]->getIP() */,$str),false);	// отправим всем клиентам
				}
			break;
		}
	}
	/* function processServer($com,&$con,$cid=null,$gid=null){	// обработка внутренних комманд
		switch($com){
			case 0:	// безвозвратный ping
			break;
			case 1:	// запрос на ping
				if(is_int($con)) $this->GServer->send($cid,0,$con); 
			break;
			case 100500109:	// в запись в лог клиентам (log)(109)	// чисто для отладки 
				
			break;
		}
	} */
}
?>