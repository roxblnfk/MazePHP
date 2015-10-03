<?php	// � roxblnfk 2011
class TRChatTool{	// ���������� ���
	var $RGame;	// ������ �� TRGameClient ������ - �������
	var $UI;	// ������ �� TRGameClient ������ - �������
	var $ChatData=array();// ������� ���� ��� �����������..
	var $Style;
	var $ChatsOpt=array();	// ����� ������� ����	Array of [id]=>Array(bool styled{RTF=true|false,MEMO=false}, int maxMessages)
	var $EndFrame=array();	// to repaint array of [id]=>array(0=>ChatBox,1=>PlayerList)
	
	function TRChatTool(){
		$this->Style='{\\rtf1\\ansi\\ansicpg1251\\deff0\\deflang1049\\fcharset204'	// {\\fonttbl{\\f0\\fnil\\fcharset0 Tahoma;}} - � ��������� ���
			.'{\\colortbl ;'	// ������� ������
				// 0 system message
				.'\\red127\\green0\\blue127;'	// 1 time
				.'\\red195\\green0\\blue195;'	// 2 text
				// 1 server message
				.'\\red0\\green0\\blue0;'		// 3 time
				.'\\red195\\green0\\blue0;'		// 4 user (server)
				.'\\red0\\green0\\blue0;'		// 5 text
				// 2 public message
				.'\\red127\\green0\\blue0;'		// 6 time
				.'\\red0\\green0\\blue127;'		// 7 user (server)
				.'\\red0\\green127\\blue0;'		// 8 text
				// 3 private message
				.'\\red2\\green195\\blue0;'		// 9 time
				.'\\red0\\green0\\blue195;'		//10 user (server)
				.'\\red0\\green195\\blue0;'		//11 text
				// 1 server message
				.'\\red31\\green31\\blue31;'	// 12 time
				.'\\red95\\green95\\blue95;'	// 13 user (server)
				.'\\red127\\green127\\blue127;'	// 14 text
			.'}\\viewkind4\\uc1\\pard\\lang1033\\f0\\fs16';
		$this->UI[0][0]=c('GeneralChat->richGeneralChat');	// �������� ��� - ���������
		$this->UI[0][1]=c('GeneralChat->listBoxGeneralChat');	// �������� ��� - �����
		$this->ChatsOpt[0]=array(true,100);
	}
	function sendMessage($msg){	// ��������� ��������� �� ������
		if(strlen($msg)==0) return false;
		$this->RGame->sockSend(100500447,$msg,true);
		return true;
	}
	function addMessage($con,$refreshPlayers=false){	// ��������� ��������� � "���������" // array(type,time,userID,message)	type: 0=system, 1=server, 2=publc user, 3=private user
		if(is_array($con)){
			$dat=array_values($con);
			$this->ChatData[]=array(
				(int)$dat[0],
				(float)$dat[1],
				(isset($this->RGame->RPlayerList->Players[$dat[2]]) ? $this->RGame->RPlayerList->Players[$dat[2]]['name'] : '~'),
				(string)$dat[3]
			);
			//$this->paintChat(0);
			$this->EndFrame[$id=0][0]=true;
		}
		$this->EndFrame[$id=0][1]=(bool)$refreshPlayers;
	}
	function endFrameActions(){	// ��� �����������. ������� ��������� �����, ���� �� ����������, � ����� ������. ����� �� �������������� � 1 ����� ��� ����� ���, ���� ������ ��������� ���������
		$j=sizeof($this->EndFrame);
		if($j==0) return false;
		$ks=array_keys($this->EndFrame);
		for($i=0;$i<$j;++$i){
			$k=$ks[$i];
			$o=&$this->EndFrame[$k];
			if($o[0])
				$this->paintChat($k);
			if($o[1])
				$this->paintPList($k);
		}
		$this->EndFrame=array();
	}
	function paintPList($id){	// ���������� ������ �������������
		$c=&$this->UI[$id][1];
		if($c===false) return false;
		$c->items->clear();
		$pls=&$this->RGame->RPlayerList->Players;
		$ks=array_keys($pls);
		for($i=0,$j=sizeof($pls);$i<$j;++$i){
			$k=$ks[$i];
			//$c->items->add('['.$k.'] '.$pls[$k]['name']);
			$c->items->add($pls[$k]['name']);
		}
	}
	function paintChat($id,$styled=true){	// ���������� ���
		$c=&$this->UI[$id][0];
		if($c===false) return false;
		$len=$c->selLength;
		if($len>0) $sp=$c->selStart;	// select start pos
		$d=&$this->ChatData;	// array of array(type,time,user,message)
		$sz=sizeof($d);
		$j=min($this->ChatsOpt[$id][1],$sz);
		if($this->ChatsOpt[$id][0]){
			$i=$sz-$j;
			$txt=$this->Style.'\\cf0\\b '.($i==0 ? '���' : '������ ���������: '.$i);
			for(;$i<$sz;++$i){
				if($d[$i][0]==2)	// public
					$txt.='\\par\\cf6\\b ['.date('H:i:s',$d[$i][1]).'] \\cf7\\ul '.$this->strtoRTF($d[$i][2]).'\\ul0\\cf0 : \\b0\\cf8 '.$this->strtoRTF($d[$i][3]);
				elseif($d[$i][0]==1)	// server
					$txt.='\\par\\cf3\\b ** ['.date('H:i:s',$d[$i][1]).'] \\cf4\\ul ������\\ul0\\cf0 : \\b0\\cf5 '.$this->strtoRTF($d[$i][3]).' **';
				elseif($d[$i][0]==0)	// system
					$txt.='\\par\\cf1\\b ['.date('H:i:s',$d[$i][1]).'] \\cf2 '.$this->strtoRTF($d[$i][3]);
				elseif($d[$i][0]==3)	// private
					$txt.='\\par\\cf9\\b ['.date('H:i:s',$d[$i][1]).'] ������ ��������� �� \\cf10\\ul '.$this->strtoRTF($d[$i][2]).'\\ul0\\cf0 : \\cf11 '.$this->strtoRTF($d[$i][3]);
				elseif($d[$i][0]==4)	// gameMessage
					$txt.='\\par\\cf12\\b > ['.date('H:i:s',$d[$i][1]).'] \\cf14 '.$this->strtoRTF($d[$i][3]).' \\cf12 < \\b0';
			}
			$c->text=$txt.' }';
		}else{
			$i=$sz-$j;
			$txt=($i==0 ? '���' : '������ ���������: '.$i);
			for(;$i<$sz;++$i){
				if($d[$i][0]==2)	// public
					$txt.=RN.'['.date('H:i:s',$d[$i][1]).'] '.$this->strtoRTF($d[$i][2]).': '.$this->strtoRTF($d[$i][3]);
				elseif($d[$i][0]==1)	// server
					$txt.=RN.'** ['.date('H:i:s',$d[$i][1]).'] ������ ��������: '.$this->strtoRTF($d[$i][3]).' **';
				elseif($d[$i][0]==0)	// system
					$txt.=RN.'~~['.date('H:i:s',$d[$i][1]).'] ��������� ���������: '.$this->strtoRTF($d[$i][3]).' ~~';
				elseif($d[$i][0]==3)	// private
					$txt.=RN.'['.date('H:i:s',$d[$i][1]).'] >> ������ ��������� �� '.$this->strtoRTF($d[$i][2]).': '.$this->strtoRTF($d[$i][3]);
			}
			$c->text=$txt;
		}
		if(isset($sp)){
			$c->selStart=$sp;
			$c->selLength=$len;
		}
		$c->perform(182, 0, 0xFFFFFF);
		$c->perform(277, 0, 0);
	}
    function strtoRTF($s){	// ����������� ������ � RTF, ��������� ��� �������
		$r=''; for($i=0,$j=strlen($s);$i<$j;++$i){ if(($d=ord($s{$i}))<32) continue; $r.='\\\''.dechex($d); } return $r;
    }
}
?>