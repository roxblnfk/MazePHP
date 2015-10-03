<?php	// © roxblnfk 2011
///* © roxblnfk 2011 *///
if(!defined('SOL_TCP')) define('SOL_TCP',getprotobyname('tcp'));
if(!defined('RN')) define('RN',"\r\n");
class rxnetcllw {	// roxblnfk/network/client/low
	var $Options=array(
	//	'TimeLimit'=>5000,	// msec
		'Address'=>'127.0.0.1',
		'Port'=>8465,
		'LocalAddress'=>'127.0.0.1',
		'LocalPort'=>90,
	//	'ConnectLimit'=>90,
	//	'Timer'=>50,
	);
	var $Client=array(
		'Started'=>false,
	);
	var $Handler=false;
	var $Connect;
	var $lw_Logs=array();
	var $Stats=array();
	
	function err_no(){ err_no(); }
	function err_yes(){ err_yes(); }
	function disconnect($reason){	// клиент дисконнектит, что делать?
		$this->lw_stop(null);
	}
	function lw_log($t){
		$this->lw_Logs[]=$t;
		return true;
	}
	function lw_start(){
		if($this->Client['Started']!==false) return false;
		$this->err_no();	// DS
		if (($this->Handler = socket_create(AF_INET,SOCK_STREAM,SOL_TCP))===false){
			$this->lw_log('socket_create() failed: reason: ' . socket_strerror(socket_last_error($this->Handler)));
			$this->err_yes();	// DS
			return false;
		}else $this->lw_log('Socket created');
		// if (socket_bind($this->Handler, $this->Options['LocalAddress'], $this->Options['LocalPort'])===false) {
			// $this->lw_log('socket_bind() failed: reason: ' . socket_strerror(socket_last_error($this->Handler)));
			// $this->err_yes();	// DS
			// return false;
		// }else $this->lw_log('Socket bindet');
		if (socket_connect($this->Handler, $this->Options['Address'], $this->Options['Port'])===false) {
			$this->lw_log('socket_connect('.strval($this->Options['Address']).', '.strval($this->Options['Port']).') failed: reason: '
						. socket_strerror(socket_last_error($this->Handler)));
			$this->err_yes();	// DS
			return false;
		}else $this->lw_log('Connected!');
		socket_set_nonblock($this->Handler);		//?
		$this->err_yes();	// DS
		$this->Client['Started']=microtime(true);	/// php 5
		$this->Stats['LastTime']=microtime(true);
		return true;
	}
	function lw_stop(){
		if($this->Client['Started']===false) return false;
		$this->Client['Started']=false;
		$this->err_no();	// DS
		socket_close($this->Handler);
		$this->err_yes();	// DS
		$this->Handler=false;
		return true;
	}
	function lw_send($content){
		if($this->Client['Started']===false) return false;
		$hndl=$this->Handler;
		$this->err_no();
		if(false!==socket_select($Sread=null, $Swrite=array($hndl), $Sexcept=null, 1000) && count($Swrite)>0){
			$i=socket_send($hndl,$content,strlen($content),0);
			$this->err_yes();
		}else{
			$this->err_yes();
			return false;
		}
		if(false===$i){
			$err=socket_last_error($hndl);
			if($err===105){		/* Нет места в буфере */
				
			}elseif($err===90){/* Сообщение слишком длинное */
				
			}else $this->disconnect(socket_strerror($err));
			return false;
		}
		if($i>0){
		//	$this->lw_log('socket_send '.$i);		// debug
			$this->Stats['Out']+=$i;
		//	$this->lw_log('Отправлено '.$i.' байт');// debug
		}
		return $i;
	}
	function lw_recv($len=null){
		if($this->Client['Started']===false) return false;
		$hndl=$this->Handler;
		$str='';$ok=true;
		$this->err_no();	// DS
		if($len!=null || $len>0) $i=@socket_recv($hndl, &$str,$len,0);
		else do{
			$l = socket_recv($hndl, &$out,256,0);
			$str.=$out;
			if(($l!=false && $l!=null && $l!='' && $out!='')){
				//$this->lw_log('	_socket_recv: '.$l.' байт прочитано;');	// ? Oo		// debug
			}else $ok=false;
		} while ($ok);
		$this->err_yes();	// DS
		if($str!=''){
			//_console('socket_recv '.strlen($str),true,false);
			$this->Stats['In']+=strlen($str);
			$this->Stats['LastTime']=microtime(true);
		}
		return $str;
	}
	function lw_selSend($content){
		// запись с селектом, надо как нить сделать :)
		$this->lw_send($content);
	}
	function lw_selRead($time=100){		// проверяет, пришло ли чего сюда, если пришло, то читает и возыращает строку. В случаях ошибок вернёт false
		// чтение с селектом, надо как нить сделать :)
	//	$this->lw_recv($len);
		if($this->Client['Started']===false) return false;
		$Sread[]=$this->Handler;
		$this->err_no();
		if(false===($numsocks=socket_select($Sread, $Swrite=null, $Sexcept=null, 0, $time))){
			$this->lw_stop('crash connection');
			$this->err_yes();
			return false;
		}elseif(sizeof($Sread)>0){
			$str=$this->lw_recv();
			//$this->lw_log(urlencode($str));		// debug
			$this->err_yes();
			return $str;
		}
		$this->err_yes();
		return '';
	}
}

?>