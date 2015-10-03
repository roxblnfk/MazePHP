<?php	// � roxblnfk 2011

if(!defined('SOL_TCP')) define('SOL_TCP',getprotobyname('tcp'));
if(!defined('RN')) define('RN',"\r\n");
class rxnetsv extends rxnetsvlw {	// roxblnfk/network/setver/low
	var $Options=array(
		'Address'=>'127.0.0.1',
		'Port'=>90,
		'ConnectLimit'=>90,
		'Timer'=>50,
		'PingMaxTime'=>3.1,	// sec	// ���������� ���� �����
		'TimeLimit'=>8.6, // sec	// ����� �������� ��������� �� �������
	);
	var $Server=array(
		'Started'=>false,
		'Connects'=>0,
	);
	var $RGame;	// � ����� ������ ��� DISCONNECT �������. ��� ������ �� "�������" :)
	var $FB='bl';	// ������ 2 ����� ������� ������
	var $Handler=false;
	var $Connects;
	var $Logs=array();
	var $Clients=array();
	var $ReadData=array();	// ����������� ������ ������������� (������)
	var $WriteData=array();	// ������ �� ������, �� ������������ (������)
	var $Buffer=array();	// ����������� ������, �� ������������� (� ���� ������)
	
	function err_no(){ error_reporting(0); }
	function err_yes(){ error_reporting(E_ALL); }
	function lw_log($t){return true;}	// ��� �������������� �������
	function log($t,$r=true){
		echo mb_convert_encoding($t.RN, 'CP866', 'CP1251');
		//$this->Logs[]=date('H:i:s').$t;
		//c('Form1->memoLog')->text.=$t.RN;
		return $r;
	}
	function start($ip='0.0.0.0',$port=90,$limit=16){
		//$this->FB='bl';
		$this->Options['Address']=$ip;
		$this->Options['Port']=(int)$port;
		$this->Options['ConnectLimit']=(int)$limit;
		if($t=$this->lw_start()){
			$this->log('Server started '.$ip.':'.$port);
			return true;
		}else return false;
	}
	function stop($send=''){
		if($this->lw_stop($send)){
			$this->ReadData=array();
			$this->WriteData=array();
			$this->Buffer=array();
			$this->log('Server stopped');
			return true;
		}
		return false;
	}
	function kick($id,$send='',$reason=null){	// ��������� �������
		if($this->lw_sockDestroy($id,$send,$reason)){
			unset($this->Buffer[$id], $this->WriteData[$id], $this->ReadData[$id]);
			return true;
		}
		return false;
	}
	function pick($id=null){
		if(is_null($id)){ // ���� ��������� id
			$li=(int)$this->Options['ConnectLimit'];
			$id=0;
			do{++$id;}while(isset($this->Connects[$id]));
			//if($id>$li) return false;	// ����� ����������� ��������
		}
		if($this->lw_sockCreate($id)){
			$this->Buffer[$id]='';
			$this->WriteData[$id]=array();
			$this->ReadData[$id]=array();
			$this->Clients[$id]['Needle']=0;	// ��������� ��������� ��� ������� ���� ��� ������������ �������� ������
			$this->Clients[$id]['PingReqTime']=microtime(true);	// ����� ���������� ������� �� �����. ����� �� ������� ������	// php5
			$this->Clients[$id]['PingResTime']=microtime(true);	// ����� ���������� ������ �� ������ �����. ����� �� ������� ������	// php5
			return $id;
		}
		return false;
	}
	/* ������������ ����� */
	function pack(&$id,&$command,&$content){		// | 2B head | 4B command | 4B type | 4B content length / value | [0-2GB content :)] |		<= 14 ���� �������
		$str=$this->FB.pack('L',$command);
		do{
			$ser='';
			//var_dump($content);
			switch(true){
				case is_object($content) :
					$t=10;
				break;
				case is_int($content) :
					$t=2;
					$l=pack('L',$content);
					//echo '>>>CONTENT IS INT<<';
				break 2;
				case is_float($content) :
					$t=4;
				break;
				case is_array($content) :
					$t=6;
					//echo '>>>CONTENT IS ARRAY<<';
				break;
				case is_string($content) :
					$t=8;
					$ser=$content;
				break 2;
				case is_null($content) :
					$t=0;
					//echo '>>>CONTENT IS NULL<<';
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
	function unpack(&$id,&$str,$b14=false){	// $b14 - ������ ������ ������ 14 ���� ������, ������� ����� int - ����� ��������� ����. � ������� ������ ����� false, ����� array(�������,�������)
		if(strlen($str)<14) return false;
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
					@$con=igbinary_unserialize(substr($str,14,$l));
				break;
				case 2 :	// int
					if($b14) return 0;
					$con=$l;
				break;
				case 4 :	// float
					if($b14) return $l;
					@$con=igbinary_unserialize(substr($str,14,$l));
				break;
				case 6 :	// array
					if($b14) return $l;
					@$con=igbinary_unserialize(substr($str,14,$l));
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
	function sendStack($id,$lim=0){
		if(!isset($this->Connects[$id])) return false;
		$j=sizeof($this->WriteData[$id]);
		if($j==0) return false;
		if($lim>0) $j=min($lim,$j);
		for($i=0;$i<$j;++$i){
			if(false===$this->send($id,$this->WriteData[$id][$i][0],$this->WriteData[$id][$i][1])) return false;
			unset($this->WriteData[$id][$i]);
		}
		$this->WriteData[$id] = array_values($this->WriteData[$id]);
	}
	function addSend($id,$command,$content){	// �������� � ������� ��� ��������
		if(!isset($this->WriteData[$id])) return false;
		$this->WriteData[$id][]=array($command,$content);
	}
	function send($id,$command,$content){	// �������� ������
		$content=$this->pack($id,$command,$content);
		if($content!==false){
			return $this->lw_send($id,$content);
		}else{
			$this->log('����� �� �����������');
			return null;
		}
	}
	function fuckBuffer($id){	// ����������� �����, �������������� � ����� � $ReadData
		if(!isset($this->Connects[$id])) return false;
		$j=strlen($this->Buffer[$id]);
		$p=0;
		$n=$this->Clients[$id]['Needle'];
		while($j>=14){
			if($n==0) $n=$this->unpack($id,$str=substr($this->Buffer[$id],$p,14),true);
			if($j<14+$n) break;
			if($n>0) $str=substr($this->Buffer[$id],$p,14+$n);
			$a=$this->unpack($id,$str,false);
			if(is_array($a)){
				if($a[0]<1024) $this->commander($id,$a[0],$a[1]); // � ��� ����� ����� ������ ��������������� ��������� (����� ������)
				else $this->ReadData[$id][]=&$a;
				unset($a);
			}
			$p+=14+$n;
			$j-=$n+14;
			$n=0;
		}
		if($p>0)
			if($n==0) $this->Buffer[$id]='';
			else $this->Buffer[$id]=substr($this->Buffer[$id],$p);
		$this->Clients[$id]['Needle']=$n;
	}
	function disconnect($id,$reason){
		//$this->log($id.' ������� ���... '.$reason);
		$this->RGame->onDisconnect($id);
		//$this->lw_sockDestroy($id,null,$reason);
	}
	function process(){	// ��������� �����, �������, ��� ������� �� ����������
		$mt=microtime(true);
		$ks=array_keys($this->Clients);
		for($i=0, $j=sizeof($ks); $i<$j; ++$i){
			$id=$ks[$i]; $cl=&$this->Clients[$id];
			if($mt-$cl['LastTime'] > $this->Options['TimeLimit'])
				$this->disconnect($id,'��������� ����� ��������: '.($mt-$cl['LastTime']));
			elseif($mt-$cl['LastTime'] > $this->Options['PingMaxTime'])
				$this->pingRequest($id);
		}
	}
	private function pingRequest($id, $con=null){
		if(microtime(true) - $this->Clients[$id]['PingReqTime'] < $this->Options['PingMaxTime']) return false;	//  ����� �� ������� ������
		$this->Clients[$id]['PingReqTime']=microtime(true);	// ����� ���������� ������� �����.	// php5
		$this->send($id,1,is_int($con) ? $con : mktime());
		//$this->log('send ping '.$id.': '.$con);
	}
	private function commander($id,$com,&$con){
		switch($com){	// $com �� 0 �� 1023!
			case 0: break;	// ������������� ping
			case 1:	// ������ �� ping
				if(!is_int($con)) return false;
				if(microtime(true) - $this->Clients[$id]['PingResTime'] < $this->Options['PingMaxTime']) return false;	//  ����� �� ������� ������
				$this->Clients[$id]['PingResTime']=microtime(true);	// ����� ���������� ������ �� ������ �����.	// php5
				$this->send($id,0,$con);
			break;
		}
	}
	function read($id){		// ������ � ������ � ���������� ���� ���� (���������� �� ������ ��������!)
		if(!is_array($id)) $id=array($id);
		$j=sizeof($id);
		if($j==0) return false;
		for($i=0;$i<$j;++$i){
			$r=$this->lw_selRead($id[$i],$this->Options['Timer']);
			if(!is_bool($r)) if($r!==''){
				$this->Buffer[$id[$i]].=$r;
				//$this->fuckBuffer($id[$i]);
			}else return 0; else return false;	// ������ �� ���� ���
		}
	}
}
?>