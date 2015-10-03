<?php	// © roxblnfk 2011
///* © roxblnfk 2011 *///
if(!defined('SOL_TCP')) define('SOL_TCP',getprotobyname('tcp'));
if(!defined('RN')) define('RN',"\r\n");
class rxnetcl extends rxnetcllw {	// roxblnfk/network/setver/low
	var $Options=array(
		'TimeLimit'=>10.500,	// sec
		'Address'=>'127.0.0.1',	// назначается командой bind
		'Port'=>10105,			// назначается командой bind
		'Timer'=>50,
		'PingMaxTime'=>3.1,	// sec // ограничить флуд пинга
	);
	var $RGame;	// в нашем случае для DISCONNECT функции. Это ссылка на "хозяина" :)
	var $id=0;	// для возможного шифрования пакетов
	var $Client=array(
		'Started'=>false,
	);
	var $FB='bl';	// первые 2 байта каждого пакета
	var $Handler=false;
	var $Connect;
	var $Logs=array();
	var $Stats=array();
	var $ReadData=array();	// прочитанные данные РАСПАКОВАННЫЕ (массив)
	var $WriteData=array();	// данные на запись, НЕ ЗАПАКОВАННЫЕ (массив)
	var $Buffer='';	// прочитанные данные, НЕ РАСПАКОВАННЫЕ (в виде строки)
	
	function lw_log($t,$r=true){ 
		// $c=c('Log->memoLog');
		// $c->text.=$t.RN;
		// $c->perform(182, 0, 0xFFFFFF);
		// $c->perform(277, 0, 0);
		return $r;
	}	// нах низкоуровневый логгинг
	function log($t,$r=true){
		$c=c('Log->memoLog');
		$c->text.=date('[H:i:s] ').$t.RN;
		$c->perform(182, 0, 0xFFFFFF);
		return $r;
	}
	function start($ip='127.0.0.1',$port=10105){
		//$this->FB='bl';
		$this->Options['Address']=$ip;
		$this->Options['Port']=(int)$port;
		if($this->lw_start()){
			$this->log('Connected');
			$this->Stats['PingReqTime']=microtime(true);	// время последнего запроса на ответ. Чтобы не флудить пингом	// php5
			$this->Stats['PingResTime']=microtime(true);	// время последнего ответа на запрос пинга. Чтобы не флудить пингом	// php5
			return true;
		}else return false;
	}
	function stop($send=''){
		if($this->lw_stop($send)){
			$this->ReadData=array();
			$this->WriteData=array();
			$this->Buffer='';
			$this->log('Disconnected');
			return true;
		}
		return false;
	}
	/* запаковываем пакет */
	function pack(&$command,&$content){		// | 2B head | 4B command | 4B type | 4B content length / value | [0-2GB content :)] |		<= 14 байт минимум
		$id=$this->id;
		$str=$this->FB.pack('L',$command);
		do{
			$ser='';
			switch(true){
				case is_object($content) :
					$t=10;
				break;
				case is_int($content) :
					$t=2;
					$l=pack('L',$content);
				break 2;
				case is_float($content) :
					$t=4;
				break;
				case is_array($content) :
					$t=6;
				break;
				case is_string($content) :
					$t=8;
					$ser=$content;
				break 2;
				case is_null($content) :
					$t=0;
				break 2;
				case is_bool($content) :
					$t=1;
					$l=$content ? pack('L',1) : pack('L',0);
				break 2;
			}
			if(!isset($t)) return false;
			$ser=igbinary_serialize($content);
		}while(false);
		$str.=pack('L',$t).(isset($l) ? $l : pack('L',strlen($ser))).$ser;
		return $str;
	}
	function unpack(&$str,$b14=false){	// $b14 - даются только первые 14 байт пакета, функция вернёт int - число требуемых байт. В слуучае ошибки вернёт false, иначе array(команда,контент)
		if(strlen($str)<14) return false;
		$id=$this->id;
		if(substr($str,0,2)!==$this->FB) return false;
		$c=unpack('L3bl',substr($str,2,12));
		//pre($c);
		do{
			//$l=unpack('La',$c['bl3']);
			//$l=$l['a'];
			$l=$c['bl3'];
			switch($c['bl2']){
				case 10 :	// object
					if($b14) return $l;
					$con=igbinary_unserialize(substr($str,14,$l));
				break;
				case 2 :	// int
					if($b14) return 0;
					$con=$l;
				break;
				case 4 :	// float
					if($b14) return $l;
					$con=igbinary_unserialize(substr($str,14,$l));
				break;
				case 6 :	// array
					if($b14) return $l;
					$con=igbinary_unserialize(substr($str,14,$l));
				break;
				case 8 :	// string
					if($b14) return $l;
					$con=substr($str,14,$l);
				break;
				case 0 :	// null
					if($b14) return 0;
					$con=null;
				break 2;
				case 0 :	// bool
					if($b14) return 0;
					$con=(bool)$l;
				break 2;
			}
			if(!isset($con)) return false;
			if($con===false) return false;
		}while(false);
		return array($c['bl1'],$con);
	}
	function sendStack($lim=0){
		$j=sizeof($this->WriteData);
		if($j==0) return false;
		if($lim>0) $j=min($lim,$j);
		for($i=0;$i<$j;++$i){
			$this->send($this->WriteData[$i][0],$this->WriteData[$i][1]);
			unset($this->WriteData[$i]);
		}
		$this->WriteData=array_values($this->WriteData);
	}
	function addSend($command,$content){	// доабвить в очередь для отправки
		$this->WriteData[]=array($command,$content);
	}
	function send($command,$content){	// отпраить сейчас
		$content=$this->pack($command,$content);
		if($content!==false) return $this->lw_send($content);
		else $this->log('Пакет не сформирован');
		return false;
	}
	function fuckBuffer(){	// анализируем буфер, расшифровываем и пишем в $ReadData
		$j=strlen($this->Buffer);
		$p=0;
		$n=$this->Stats['Needle'];
		while($j>=14){
			if($n==0) $n=$this->unpack($str=substr($this->Buffer,$p,14),true);
			if($j<14+$n) break;
			if($n>0) $str=substr($this->Buffer,$p,14+$n);
			$a=$this->unpack($str,false);
			if(is_array($a)){
				if($a[0]<1024) $this->commander($a[0],$a[1]); // а тут будет отсев команд непосредственно протокола (этого класса)
				else $this->ReadData[]=&$a;
				unset($a);
			}
			$p+=14+$n;
			$j-=$n+14;
			$n=0;
		}
		if($p>0)
			if($n==0) $this->Buffer='';
			else $this->Buffer=substr($this->Buffer,$p);
		$this->Stats['Needle']=$n;
	}
	function read(){		// читаем с сокета и записываем куда надо (передавать по одному элементу!)
		$r=$this->lw_selRead($this->Options['Timer']);
		if(!is_bool($r)) if($r!==''){
			$this->Buffer.=$r;
			//$this->fuckBuffer();
		}else return 0; else return false;
	}
	function disconnect($reason){
		if($this->Client['Started']===false) return false;
		$this->RGame->onDisconnect();	// даём исполнительному скрипту знать о том, что соединение прервано
		$this->log('Отключение от сервера... '.$reason);
		//$this->lw_stop();
	}
	function process(){	// проверяет пинги, ещё чёнить
		if($this->Client['Started']===false) return false;
		$mt=microtime(true);
		if($mt-$this->Stats['LastTime'] > $this->Options['TimeLimit'])
			$this->disconnect('Превышено время ожидания!');
		elseif($mt-$this->Stats['LastTime'] > $this->Options['PingMaxTime'])
			$this->pingRequest();
	}
	private function pingRequest($con=null){
		if($this->Client['Started']===false) return false;
		$st=&$this->Stats;
		if(microtime(true) - $st['PingReqTime'] < $this->Options['PingMaxTime']) return false;	//  Чтобы не флудить пингом
		$st['PingReqTime']=microtime(true);	// время последнего запроса пинга.	// php5
		$this->send(1,is_int($con) ? $con : mktime());
		//$this->log('Send ping!');
	}
	private function commander($com,&$con){
		$mt=microtime(true);
		$st=&$this->Stats;
		switch($com){	// $com от 0 до 1023!
			case 0: break;	// безвозвратный ping
			case 1:	// запрос на ping
				if(!is_int($con)) return false;
				if($mt - $st['PingResTime'] < $this->Options['PingMaxTime']) return false;	//  Чтобы не флудить пингом
				$st['PingResTime']=$mt;	// время последнего ответа на запрос пинга.	// php5
				$this->send(0,$con);
				//$this->log('ping! '.$con);
			break;
			case 3:	// сервер говорит, какой PingMaxTime использовать
				if(!is_float($con)) return false;
				if($con>1 && $con<$this->Stats['LastTime']) $this->Stats['PingMaxTime']=$con;
				// отсюда следует, что сервер должен сначала послать {208}TimeLimit
			break;
			case 4:	$this->id=$con; break;	// сервер задаёт ID
			case 280:	// сервер говорит, какой TimeLimit использовать
				if(!is_float($con)) return false;
				if($con>1 && $con<120) $this->Stats['LastTime']=$con;
			break;
		}
	}
}
?>