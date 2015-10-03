<?php	// � roxblnfk 2011
///* � roxblnfk 2011 *///
if(!defined('SOL_TCP')) define('SOL_TCP',getprotobyname('tcp'));
if(!defined('RN')) define('RN',"\r\n");
class rxnetcl extends rxnetcllw {	// roxblnfk/network/setver/low
	var $Options=array(
		'TimeLimit'=>10.500,	// sec
		'Address'=>'127.0.0.1',	// ����������� �������� bind
		'Port'=>10105,			// ����������� �������� bind
		'Timer'=>50,
		'PingMaxTime'=>3.1,	// sec // ���������� ���� �����
	);
	var $RGame;	// � ����� ������ ��� DISCONNECT �������. ��� ������ �� "�������" :)
	var $id=0;	// ��� ���������� ���������� �������
	var $Client=array(
		'Started'=>false,
	);
	var $FB='bl';	// ������ 2 ����� ������� ������
	var $Handler=false;
	var $Connect;
	var $Logs=array();
	var $Stats=array();
	var $ReadData=array();	// ����������� ������ ������������� (������)
	var $WriteData=array();	// ������ �� ������, �� ������������ (������)
	var $Buffer='';	// ����������� ������, �� ������������� (� ���� ������)
	
	function lw_log($t,$r=true){ 
		// $c=c('Log->memoLog');
		// $c->text.=$t.RN;
		// $c->perform(182, 0, 0xFFFFFF);
		// $c->perform(277, 0, 0);
		return $r;
	}	// ��� �������������� �������
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
			$this->Stats['PingReqTime']=microtime(true);	// ����� ���������� ������� �� �����. ����� �� ������� ������	// php5
			$this->Stats['PingResTime']=microtime(true);	// ����� ���������� ������ �� ������ �����. ����� �� ������� ������	// php5
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
	/* ������������ ����� */
	function pack(&$command,&$content){		// | 2B head | 4B command | 4B type | 4B content length / value | [0-2GB content :)] |		<= 14 ���� �������
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
	function unpack(&$str,$b14=false){	// $b14 - ������ ������ ������ 14 ���� ������, ������� ����� int - ����� ��������� ����. � ������� ������ ����� false, ����� array(�������,�������)
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
	function addSend($command,$content){	// �������� � ������� ��� ��������
		$this->WriteData[]=array($command,$content);
	}
	function send($command,$content){	// �������� ������
		$content=$this->pack($command,$content);
		if($content!==false) return $this->lw_send($content);
		else $this->log('����� �� �����������');
		return false;
	}
	function fuckBuffer(){	// ����������� �����, �������������� � ����� � $ReadData
		$j=strlen($this->Buffer);
		$p=0;
		$n=$this->Stats['Needle'];
		while($j>=14){
			if($n==0) $n=$this->unpack($str=substr($this->Buffer,$p,14),true);
			if($j<14+$n) break;
			if($n>0) $str=substr($this->Buffer,$p,14+$n);
			$a=$this->unpack($str,false);
			if(is_array($a)){
				if($a[0]<1024) $this->commander($a[0],$a[1]); // � ��� ����� ����� ������ ��������������� ��������� (����� ������)
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
	function read(){		// ������ � ������ � ���������� ���� ���� (���������� �� ������ ��������!)
		$r=$this->lw_selRead($this->Options['Timer']);
		if(!is_bool($r)) if($r!==''){
			$this->Buffer.=$r;
			//$this->fuckBuffer();
		}else return 0; else return false;
	}
	function disconnect($reason){
		if($this->Client['Started']===false) return false;
		$this->RGame->onDisconnect();	// ��� ��������������� ������� ����� � ���, ��� ���������� ��������
		$this->log('���������� �� �������... '.$reason);
		//$this->lw_stop();
	}
	function process(){	// ��������� �����, ��� ������
		if($this->Client['Started']===false) return false;
		$mt=microtime(true);
		if($mt-$this->Stats['LastTime'] > $this->Options['TimeLimit'])
			$this->disconnect('��������� ����� ��������!');
		elseif($mt-$this->Stats['LastTime'] > $this->Options['PingMaxTime'])
			$this->pingRequest();
	}
	private function pingRequest($con=null){
		if($this->Client['Started']===false) return false;
		$st=&$this->Stats;
		if(microtime(true) - $st['PingReqTime'] < $this->Options['PingMaxTime']) return false;	//  ����� �� ������� ������
		$st['PingReqTime']=microtime(true);	// ����� ���������� ������� �����.	// php5
		$this->send(1,is_int($con) ? $con : mktime());
		//$this->log('Send ping!');
	}
	private function commander($com,&$con){
		$mt=microtime(true);
		$st=&$this->Stats;
		switch($com){	// $com �� 0 �� 1023!
			case 0: break;	// ������������� ping
			case 1:	// ������ �� ping
				if(!is_int($con)) return false;
				if($mt - $st['PingResTime'] < $this->Options['PingMaxTime']) return false;	//  ����� �� ������� ������
				$st['PingResTime']=$mt;	// ����� ���������� ������ �� ������ �����.	// php5
				$this->send(0,$con);
				//$this->log('ping! '.$con);
			break;
			case 3:	// ������ �������, ����� PingMaxTime ������������
				if(!is_float($con)) return false;
				if($con>1 && $con<$this->Stats['LastTime']) $this->Stats['PingMaxTime']=$con;
				// ������ �������, ��� ������ ������ ������� ������� {208}TimeLimit
			break;
			case 4:	$this->id=$con; break;	// ������ ����� ID
			case 280:	// ������ �������, ����� TimeLimit ������������
				if(!is_float($con)) return false;
				if($con>1 && $con<120) $this->Stats['LastTime']=$con;
			break;
		}
	}
}
?>