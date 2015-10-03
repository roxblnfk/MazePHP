<?
class TRGLobby{	// 
	var $RGame;
	var $RGWorld;	// TRGWorld объект - игровой мир
	
	var $Enabled=true;
	var $Inited=false;	// запрос в обработке
	var $LastTime=false;	// время последнего запроса
	var $Active=array(false,false,0,null,null);	// статусы и флаги текущего запроса
	var $Options=array(
			'url'=>'http://maze.roxblnfk.16mb.com/',
			'dns'=>'maze.roxblnfk.16mb.com',
			'timeout'=>30,
			'svkey'=>'',
		);
	var $lastUpdated=false;	// результат последнего обновления
	
	function __construct($addr='maze.roxblnfk.16mb.com'){
		$this->Options['dns']=$addr;
		$this->Options['url']="http://$addr/";
	}
	function process(){
		if(!$this->Enabled) return false;
		if($this->Inited) $this->curlContinue();
		elseif($this->LastTime+$this->Options['timeout']<microtime(true)){
			$this->Active=array(false,false,0,null,null);
			$this->Inited=true;
			$this->curlContinue();
		}
		return true;
	}
	function curlContinue(){
		$mrc=&$this->Active[0];
		$act=&$this->Active[1];
		$stp=&$this->Active[2];
		$mch=&$this->Active[3];
		$ch1=&$this->Active[4];
		if($stp==0){//начало
			//echo "curl init!\n";
			// создаём ресурс cURL
			$ch1=$this->curlCreateResource();
			//создаем набор дескрипторов cURL
			$mch = curl_multi_init();
			//добавляем дескриптор
			curl_multi_add_handle($mch,$ch1);
			++$stp;
		}
		//запускаем дескрипторы
		if($stp==1){
			//echo "curl stp 1!\n";
			$mrc=curl_multi_exec($mch, $act);
			if($mrc!=CURLM_CALL_MULTI_PERFORM) ++$stp;
		}
		if($stp==2){
			//echo "curl stp 2!\n";
			if($act){// and $mrc == CURLM_OK || $mrc == CURLM_CALL_MULTI_PERFORM){
				//if($mrc==CURLM_CALL_MULTI_PERFORM || curl_multi_select($mch, 0.001)>0){
					$mrc=curl_multi_exec($mch, $act);
				//}else echo $mrc;
			}else ++$stp;
			/* $info = curl_multi_info_read($mch);
			if (false !== $info) {
				var_dump($info);
			} */
		}
		//закрываем дескрипторы
		if($stp==3){
			//echo "curl stp 3!\n";
			if($this->lastUpdated XoR $this->lastUpdated=$this->curlMathContent($ch1))
				__log($this->lastUpdated ? 'Lobby обновлён' : 'Падение Lobby');
			curl_multi_remove_handle($mch, $ch1);
			curl_multi_close($mch);
			curl_close($ch1);
			$stp=0;
			$this->Inited=false;
			$this->LastTime=microtime(true);
		}
		return $stp;
	}
	function curlMathContent($ch){
		$return=false;
		$c=trim(curl_multi_getcontent($ch));
		//var_dump($c);
		$a=@unserialize($c);
		if(!is_array($a)) return false;
		if(isset($a['svkey']) && $a['svkey']) $this->Options['svkey']=strval($a['svkey']);
		if(isset($a['restart'])) $this->Options['svkey']='';
		if(isset($a['timeout']))
			$this->Options['timeout']=max(20,floatval($a['timeout'])*0.7);
		if(isset($a['okay'])) $return=(bool)$a['okay'];
		
		return $return;
	}
	function curlCreateResource(){
		$ch1=curl_init();
		$heads=array(
				'Connection: keep-alive',
				'Keep-Alive: 60',
				'Accept: text/xml,application/xhtml+xml,text/html;q=0.9',
				'Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3',
				'Cache-Control: max-age=0'
			);
		// устанавливаем URL и другие соответствующие опции
		curl_setopt($ch1, CURLOPT_URL, $this->Options['url']);
		curl_setopt($ch1, CURLOPT_USERAGENT, 'Maze PHP Dedicated Server v'.TRGame::VERSION); 
		curl_setopt($ch1, CURLOPT_ENCODING, 'gzip,deflate'); 
		curl_setopt($ch1, CURLOPT_HTTPHEADER, $heads); 
		curl_setopt($ch1, CURLOPT_HEADER, false);
		curl_setopt($ch1, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch1, CURLOPT_BINARYTRANSFER, false);
		curl_setopt($ch1, CURLOPT_POST, true);
		curl_setopt($ch1, CURLOPT_POSTFIELDS, $this->curlGeneratePost());
	//	curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch1, CURLOPT_TIMEOUT, 10);
		return $ch1;
	}
	function curlGeneratePost(){
		$rIPv4=gethostbyname($this->Options['dns']);//remote addr IPv4
		$PCN=gethostname();
		$WorldSize=$this->RGWorld->WorldSize;
		$online=$this->RGame->getPlayersArray(true, TRGWorld::GS, true);
		$A=array('remote_addr'=>$rIPv4,
				'hostname'=>$PCN,
				'svport'=>$this->RGame->Options['ServerPort'],
				'worldwidth'=>$WorldSize[0],
				'worldheight'=>$WorldSize[1],
				'svname'=>$this->RGame->Options['ServerName'],
				'psonline'=>sizeof($online),
				'maxplayers'=>$this->RGame->Options['MaxPlayers'],
				'sversion'=>TRGame::VERSION,
				'svkey'=>$this->Options['svkey'],
			);
		//var_dump($A);
		return $A;
	}
}	
?>