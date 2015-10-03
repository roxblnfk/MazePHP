<?php	// © roxblnfk 2011
if(!defined('SOL_TCP')) define('SOL_TCP',getprotobyname('tcp'));
if(!defined('RN')) define('RN',"\r\n");
class rxnetsvlw {	// roxblnfk/network/server/low
	var $Options=array(
	//	'TimeLimit'=>5000,	// msec
		'Address'=>'127.0.0.1',
		'Port'=>90,
		'ConnectLimit'=>90,
	//	'Timer'=>50,
	);
	var $Server=array(
		'Started'=>false,
		'Connects'=>0,
	);
	var $Handler=false;
	var $Connects;
	var $lw_Logs=array();
	var $Clients=array();
	
	function err_no(){ err_no(); }
	function err_yes(){ err_yes(); }
	function disconnect($id,$reason){	// клиент дисконнектит, что делать?
		$this->lw_sockDestroy($id,null,$reason);
	}
	function lw_log($t,$r=true){
		$this->lw_Logs[]=$t;
		return $r;
	}
	function lw_start(){
		if($this->Server['Started']!==false) return false;
		if (($this->Handler = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) === false) {
			$this->lw_log('socket_create() failed: reason: ' . socket_strerror(socket_last_error($this->Handler)));
			return false;
		}else $this->lw_log('Socket created');
		socket_set_nonblock($this->Handler);
		if (($ret=socket_bind($this->Handler, $this->Options['Address'], $this->Options['Port'])) !== true) {
			$this->lw_log('socket_bind() failed: reason: ' . socket_strerror(socket_last_error($this->Handler)));
			return false;
		}else $this->lw_log('Socket bindet');
		if (socket_listen($this->Handler, isset($this->Options['ConnectLimit'])  ? $this->Options['ConnectLimit'] : 5) !== true) {
			$this->lw_log('socket_listen() failed: reason: ' . socket_strerror(socket_last_error($this->Handler)));
			return false;
		}
		//socket_set_option($this->Handler,SOL_SOCKET,SO_KEEPALIVE,2);
		//echo 'SO_KEEPALIVE	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_KEEPALIVE).RN;
		//echo 'SO_REUSEADDR	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_REUSEADDR).RN;
		//echo 'SO_RCVBUF	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_RCVBUF).RN;
		//echo 'SO_SNDBUF	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_SNDBUF).RN;
		//echo 'SO_DONTROUTE	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_DONTROUTE).RN;
		//echo 'SOCKET_EHOSTUNREACH	: '.SOCKET_EHOSTUNREACH.RN;
		//echo 'SO_BROADCAST : '.socket_get_option($this->Handler,SOL_SOCKET,SO_BROADCAST).RN;
		$this->Server['Started']=microtime(true);	/// php 5
	//	wb_create_timer ($_CONTROL['window'], $_SERVER['timer'][0], CL_SOCKET_TIMER);
		return true;
	}
	function lw_stop($send=''){
		$this->Server['Started']=false;
	//	wb_destroy_timer($_CONTROL['window'], $_SERVER['timer'][0]);
		if(count($this->Connects)>0){
			$ks=array_keys($this->Connects);
			for($i=0, $j=sizeof($ks); $i<$j; ++$i)
				$this->lw_sockDestroy($ks[$i],$send,'Остановка сервера');
		}
		socket_close($this->Handler);
		$this->Handler=false;
		$this->Server['Connects']=0;
		return true;
	}
	function lw_sockDestroy($id,$send='',$reason=null){	// отключить клиента
		if($this->Server['Started']===false) return false;
		if(!isset($this->Connects[$id])) return false;
		if(strlen($send)>0) $this->lw_send($id,$send);	// пока это бессмысленная строчка кода, т.к. сообщение не успевает отправиться а сокет уже закрывается :)
		if(false===socket_close($this->Connects[$id])) echo '>>>>>>>DEBUG SOCK CLOSE = FALSE!!!'.RN;	// возможно вернёт false и не закроет коннект... надо проверить и сделать очередь
		--$this->Server['Connects'];
		unset($this->Connects[$id],$this->Clients[$id]);
		$this->lw_log("Connection $id destroyed".($reason!=null ? ': '.$reason.'.' : '.'));
		return true;
	}
	function lw_sockCreate($id=0){
		if($this->Server['Started']===false) return false;
		$this->err_no();	// DS
		if(($sci = socket_accept($this->Handler))===false) {
			$this->lw_log("socket_accept($id) failed: reason: " . socket_strerror(socket_last_error($this->Handler)));
			return false;
		}else
			if(false===socket_select($Sread=array($sci), $Swrite=null, $Sexcept=null, 0, 1000)) return false;
		$this->err_yes();	// DS
		if(isset($this->Connects[$id])) $this->lw_sockDestroy($id,null,'Замещён');
		socket_getsockname($sci,$ip);
		//echo 'SO_KEEPALIVE	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_KEEPALIVE).RN;
		//echo 'SO_REUSEADDR	: '.socket_get_option($this->Handler,SOL_SOCKET,SO_REUSEADDR).RN;
		echo 'SO_RCVBUF	: '.socket_get_option($sci,SOL_SOCKET,SO_RCVBUF).RN;
		echo 'SO_SNDBUF	: '.socket_get_option($sci,SOL_SOCKET,SO_SNDBUF).RN;
		echo 'SO_DONTROUTE	: '.socket_get_option($sci,SOL_SOCKET,SO_DONTROUTE).RN;
		////////
		$this->lw_log("Connection created! id: $id Addr: $ip");
		$this->Clients[$id]['Address']=$ip;
		$this->Clients[$id]['LastTime']=
		$this->Clients[$id]['StartTime']=microtime(true);
		$this->Clients[$id]['In']=
		$this->Clients[$id]['Out']=0;
		$this->Connects[$id]=$sci;
		++$this->Server['Connects'];
	//	wb_create_timer ($_CONTROL['window'], $_SERVER['timer'][$id], CL_SOCKET_TIMER);
		return true;
	}
	function lw_send($id,$content){
		if($this->Server['Started']===false) return false;
		if(!isset($this->Connects[$id])) return false;
		$hndl=$this->Connects[$id];
		$this->err_no();
		if(false!==socket_select($Sread=null, $Swrite=array($hndl), $Sexcept=null, 2000) && count($Swrite)>0){
			$i=socket_send($hndl,$content,strlen($content),0);
			$this->err_yes();
			if(false===$i){
				$err=socket_last_error($this->Connects[$id]);
				if($err===105){		/* Нет места в буфере */
					echo '>>>>>>>DEBUG SOCK SEND = 105!!!'.RN;
				}elseif($err===90){/* Сообщение слишком длинное */
					echo '>>>>>>>DEBUG SOCK SEND = 90!!!'.RN;
				}else $this->disconnect($id,socket_strerror($err));
				return false;
			}
		}else{
			$this->err_yes();
			return false;
		}
		if($i>0){
			$this->lw_log('socket_send '.$i);
			$this->Clients[$id]['Out']+=$i;
			$this->lw_log('Отправлено '.$i.' байт');
		}
		return $i;
	}
	function lw_recv($id,$len=null){
		if($this->Server['Started']===false) return false;
		if(!isset($this->Connects[$id])) return false;
		$hndl=$this->Connects[$id];
		$str='';$ok=true;
		$this->err_no();	// DS
		if($len!=null || $len>0) $i=@socket_recv($hndl, &$str,$len,0);
		else do{
			$l = socket_recv($hndl, &$out,256,0);
			$str.=$out;
			if(($l!=false && $l!=null && $l!='' && $out!='')) $this->lw_log('	_socket_recv: '.$l.' байт прочитанно;');	// ? Oo
			else $ok=false;
		} while ($ok);
		$this->err_yes();	// DS
		if($str!=''){
			//_console('socket_recv '.strlen($str),true,false);
			$this->Clients[$id]['In']+=strlen($str);
			$this->Clients[$id]['LastTime']=microtime(true);
		}
		return $str;
	}
	function lw_selSend($id,$content){
		// запись с селектом, надо как нить сделать :)
		$this->lw_send($id,$content);
	}
	function lw_selRead($id,$time=100){		// проверяет, пришло ли чего сюда, если пришло, то читает и возыращает строку. В случаях ошибок вернёт false
		if($this->Server['Started']===false) return false;
		if(!isset($this->Connects[$id])) return false;
		$Sread[$id]=$this->Connects[$id];
		$this->err_no();
		//$n=socket_select($Sread2=null, $Swrite2=null, $Sexcept=$Sread, 0, $time);
		// var_dump($n);
		// var_dump($Sexcept);
		//echo RN;
		if(false===($numsocks=socket_select($Sread, $Swrite=null, $Sexcept=null, 0, $time))){
			$this->disconnect($id,'Чтение невозможно оО');
			$this->err_yes();
			echo 'УРА! ОН ЗДОХ!!!!!!!';
			return false;
		}elseif(sizeof($Sread)>0){
			$str=$this->lw_recv($id);
			$this->lw_log($str);
			$this->err_yes();
			//var_dump($Sexcept);
			return $str;
		}
		//echo 'FUUUUUUUUUU1111';
		$this->err_yes();
		return '';
	}
}
?>