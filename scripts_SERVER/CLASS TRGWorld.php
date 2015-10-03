<?php // © roxblnfk 2012
class TRGWorld{	// игровой 2D мир с кинематикой
	var $RGame;
	
	var $Objects=array();	// array(oid=>object TRGWObject)
	var $Groups=array();	// array of [group]=>array(oid=>&$obj[oid])
	var $maxID=0;
	
	var $ProcedID;		// номер фрейма, нужен для обозначения того, что объект просчитан в этом фрэйме
	
	var $CreatedObjects=array();	// array(oid)
	var $DestroyedObjects=array();	// array(oid)
	var $ChangedObjects=array();	// array of [oid]=>array(ch.params..)
	var $SendChangeables=array();	// array of [oid]=>array(GIDs)
	
	var $dt=1;
	
	const GS=1;	// статус профиля-игрока
	
	var $GameStarted=false;
	function __construct($RGame){
		$this->RGame=&$RGame;
		return true;
	}
	function __destruct(){
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	//
			$this->destroyObject($ks[$i]);
		}
	}
	function LevelStart(){
		if($this->GameStarted) return false;
		$this->GameStarted=microtime(true);
		$this->RGame->sendEvent(self::GS,'LevelStart','Погнали!',false);
		return $this->RGame->log('Уровень начат!');
	}
	function LevelStop(){
		$this->GameStarted=false;
		$this->RGame->sendEvent(self::GS,'LevelStop','Конец!',false);	// это сообщение прийти не должно в нашей игре
		return $this->RGame->log('Уровень закончен!');
	}
	function processingWorld($dt,$gt,$ProcedID){
		if($gt==0) return false;
		$this->ProcedID=$ProcedID;
		$this->dt=$gt;
		$this->procObjects();
		
		//$this->w1_collision();/////////////// custom
		
		$this->sendChanges();
		return true;
	}
	function createObject($type,$x=0,$y=0,$class='TRGWObject'){
		while(isset($this->Objects[++$this->maxID])){}
		$id=$this->maxID;
		$this->Objects[$id]=new $class($this,$id,$type,$x,$y);
		$this->CreatedObjects[$id]=array('ClassName'=>$class,'id'=>$id,'type'=>$type,'pX'=>$x,'pY'=>$y);
		return $id;
	}
	function sendObject($GIDs,$oid){	// отправить игрокам объект
		if(!is_array($GIDs)) $GIDs=array($GIDs);
		$pars=$this->Objects[$oid]->pars;
		for($i=sizeof($pars);--$i>=0;){
			$p=$pars[$i];
			$send[$p]=$this->Objects[$oid]->$p;
		}
		$send['ClassName']=get_class($this->Objects[$oid]);
		$this->RGame->multicast(TRCommander::ObjsFullI, $send,true,self::GS,$GIDs);
		return true;
	}
	function addObjectInGroup($id,$g=0){
		if(!isset($this->Objects[$id])) return false;
		if(isset($this->Groups[$g][$id])) if($this->Groups[$g][$id]===false) unset($this->Groups[$g][$id]);
		$this->Groups[$g][$id]=&$this->Objects[$id];
		return true;
	}
	function delObjectInGroup($id,$g=0){
		if(!isset($this->Groups[$g][$id])) return false;
		unset($this->Groups[$g][$id]);
		return true;
	}
	function destroyObject($id){
		if(!isset($this->Objects[$id])) return false;
		$this->Objects[$id]=false;
		// удаляем из групп
		$ks=array_keys($this->Groups);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	//
			$grid=$ks[$i];
			unset($this->Groups[$grid][$id]);
		}
		unset($this->Objects[$id]);
		$this->DestroyedObjects[]=$id;
		return true;
	}
	function procObjects(){
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($this->Objects);$i<$j;++$i){
			$id=$ks[$i];
			if(!isset($this->Objects[$id])) continue;
			$o=&$this->Objects[$id];
			if($o->proced==$this->ProcedID) return false;
			$o->proc();
			$o->proced=$this->ProcedID;
		}
		return true;
	}
	function collision($g1,$g2){	// группы
		if(!isset($this->Groups[$g1]) || !isset($this->Groups[$g2])) return false;
		$ks1=array_keys($this->Groups[$g1]);
		$ks2=array_keys($this->Groups[$g2]);
		for($i=sizeof($ks1);$i--;)
			if(is_object($this->Objects[$i]))
				for($j=sizeof($ks2);$j--;){
					if($i==$j) continue;
					if(!is_object($this->Objects[$j])) continue;
					// просчёт
					
				}
	}
	function objectChanged($id,$pars){	// параметры объекта изменены, это надо зафиксировать и потом передать клиентам
		if(!isset($this->Objects[$id])) return false;
		for($i=sizeof($pars);$i--;){
			$pn=$pars[$i];
			if(!isset($this->Objects[$id]->$pn)) continue;
			$this->ChangedObjects[$id][$pn]=$this->Objects[$id]->$pn;
		}
		return true;
	}
	// параметры объекта изменены, это надо зафиксировать и потом передать клиентам
	function objectChangedCL($gid,&$con){
		if(!is_array($con)) return false;
		if(sizeof($con)!==2) return false;
		$oid=reset($con);
		if(!isset($this->Objects[$oid])) return false;
		if(!isset($this->Objects[$oid]->byPlayer[$gid])) return false;
		$ps=next($con);
		if(!is_array($ps)) return false;
		$ks=array_keys($ps);
		for($i=sizeof($ks);$i--;){
			$p=$ks[$i];
			$v=$ps[$p];
			$r=$this->Objects[$oid]->setByPlayer($gid,$p,$v);
		//	echo 'set obj '.$oid.' '.$p.' => '.$v.($r ? ' - ok' : '- no').RN;
		}
		return true;
	}
	function sendChanges(){	// отправить клиентам изменения мира
		// созданные объекты
		error_reporting(E_ALL);
		$j=sizeof($this->CreatedObjects);
		if($j>0){
			//var_dump($this->CreatedObjects);
			$ks=array_keys($this->CreatedObjects);
			for($i=0;$i<$j;++$i){
				$id=$ks[$i];
				if(!isset($this->Objects[$id])) continue;
				if(isset($this->ChangedObjects[$id])){	// сливаем массивы, оптимизируем траффик
					$ks1=array_keys($this->ChangedObjects[$id]);
					for($i1=0, $j1=sizeof($this->ChangedObjects[$id]);$i1<$j1;++$i1){
						$par=$ks1[$i1];
						$this->CreatedObjects[$id][$par]=$this->ChangedObjects[$id][$par];
					}
					unset($this->ChangedObjects[$id]);
				}
				$this->RGame->multicast(TRCommander::ObjsCreat, $this->CreatedObjects[$id],false,self::GS, array_keys($this->Objects[$id]->visible));
			}
		}
		// уничтоженные объекты
		if(sizeof($this->DestroyedObjects)>0) $this->RGame->multicast(TRCommander::ObjsDestr, $this->DestroyedObjects,true,self::GS);
		// изменённые объекты
		$j=sizeof($this->ChangedObjects);
		if($j>0){
			//var_dump($this->ChangedObjects);
			$ks=array_keys($this->ChangedObjects);
			for($i=0;$i<$j;++$i){
				$id=$ks[$i];
				if(!isset($this->Objects[$id])) continue;
				$this->ChangedObjects[$id]['id']=$id;
				$this->RGame->multicast(TRCommander::ObjsChang, $this->ChangedObjects[$id],false,self::GS,array_keys($this->Objects[$id]->visible));
			}
		}
		if(($j=sizeof($this->SendChangeables))>0){
			$ks=array_keys($this->SendChangeables);
			for($i=0;$i<$j;++$i){
				$oid=$ks[$i];
				if(!isset($this->Objects[$oid])) continue;
				$gids=array_keys($this->SendChangeables[$oid]);
				for($i1=0,$j1=sizeof($gids);$i1<$j1;++$i1){
					$gid=$gids[$i1];
					$this->Objects[$oid]->sendChangeablesToPlayer($gid,false);
				}
			}
			$this->SendChangeables=array();
		}
		$this->CreatedObjects=array();
		$this->DestroyedObjects=array();
		$this->ChangedObjects=array();
		return true;
	}
	function onPlayerDestroy($gid){	// игрок уничтожается
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	//
			$id=$ks[$i];
			unset($this->Objects[$id]->visible[$gid]);	// удаляем видимость обекта несуществующим игроком
			unset($this->Objects[$id]->byPlayer[$gid]);	// 
		}
		return true;
	}
	function onPlayerCreate(){	// игрок создаётся/присоединяется
		return true;
	}
	function SendGameMessage($msg){
		$value=array(microtime(true), $msg);
		$this->RGame->sendEvent(self::GS,'GameMessage',$value,$stack=false);
	}
	function DestroyAllObjectsInGroup($grID){
		if(!isset($this->Groups[$grID])) return false;
		$ks=array_keys($this->Groups[$grID]);
		for($i=0,$j=sizeof($ks);$i<$j;++$i) $this->destroyObject($ks[$i]);
		return true;
	}
	
}
class TRGWObject{	// объект мира TRGWorld
	var $World;		// ссылка на объект мира
	var $proced;	// объект просчитан? сюда записывается номер кадра последнего просчёта
	var $id;
	var $pars=array('type','pX','pY','pD','sP','sX','sY','sD','aP','aX','aY','aD','id');	// параметры, о которых должны знать клиенты
// видимость для игроков: array(GID's)
	var $visible=array();
	var $byPlayer=array();	// array of GID=>array('pX/pY...'=>N/A)
// скорости и ускорения указываются только как проекции на оси X и Y
	var $kc;		// время изменения кинематических параметров
	var $pX=0;		// pos X
	var $pY=0;		// pos Y
	var $pD=0;		// направление объекта (куда смотрит), rad
	var $sP=true;// учитывать скорость в расчётах???? может быть указан в виде числа, как множитель
	var $sX=0;		// speed X
	var $sY=0;		// speed Y
	var $sD=0;		// направление скорости в радианах
	var $aP=true;// учитывать ускорение в расчётах????
	var $aX=0;		// acceleration Y
	var $aY=0;		// acceleration Y
	var $aD=0;		// направление ускорения в радианах
// описывается объект коллизий
	var $cT=0;		// тип физического объекта : 0 - точка, 1 - круг 2 - прямоугольник ортогоноальный
	var $cS=0;		// int/float/array размеры объекта соответственно его типу
	var $cD=0;		// id текстуры
	
	var $type=0;
//
	function __construct(&$World,$id,$type,$x=0,$y=0){
		$this->World=&$World;
		$this->id=$id;
		$this->type=$type;
		$this->pX=$x;
		$this->pY=$y;
	}
	function proc(){	// обработать объект
		$dt=$this->World->dt;
		$dax=$this->aP * $this->aX * $dt;
		$day=$this->aP * $this->aY * $dt;
		$this->pX+=$this->sP * $this->sX * $dt + $dax*$dt;
		$this->pY+=$this->sP * $this->sY * $dt + $day*$dt;
		$this->sX+=$dax;
		$this->sY+=$day;
		//echo $this->pY.RN;
	}
	function setPos($x=null,$y=null,$au=false){
		$dt=$this->World->dt;
		$sx=$sy=null;
		if(is_numeric($x)){
			if($au)
				if($this->pX==$x) $sx=0;
				else $sx=($x-$this->pX)/$dt;
			$this->pX=$x;
			$this->World->objectChanged($this->id,array('pX'));
		}
		if(is_numeric($y)){
			if($au)
				if($this->pY==$y) $sy=0;
				else $sy=($y-$this->pY)/$dt;
			$this->pY=$y;
			$this->World->objectChanged($this->id,array('pY'));
		}
		$this->setSpeed($sx,$sy);
	}
	function setSpeed($sx=null,$sy=null,$au=false){
		$dt=$this->World->dt;
		if(is_numeric($sx)){
			if($au)
				if($this->sX==$sx) $this->aX=0;
				else $this->aX=($sx-$this->sX)/$dt;
			$this->sX=$sx;
			$this->World->objectChanged($this->id,array('sX','pX','pY'));
			
		}
		if(is_numeric($sy)){
			if($au)
				if($this->sY==$sy) $this->aY=0;
				else $this->aY=($sy-$this->sY)/$dt;
			$this->sY=$sy;
			$this->World->objectChanged($this->id,array('sY','pX','pY'));
		}
		return true;
	}
	function setAcc($ax=null,$ay=null){	// установить ускорение
		if(is_numeric($ax)){
			$this->aX=$ax;
			$this->World->objectChanged($this->id,array('aX','pX','pY'));
		}
		if(is_numeric($ay)){
			$this->aY=$ay;
			$this->World->objectChanged($this->id,array('aY','pX','pY'));
		}
		return true;
	}
	function setVisible($GIDs=array(), $val=true){
		if(!is_array($GIDs)) $GIDs=array($GIDs);
		$v=(bool)$val;
		$send=array();
		for($i=sizeof($GIDs);--$i>=0;){
			$gid=$GIDs[$i];
			if(isset($this->visible[$gid])==$v) continue;
			if($v) $this->visible[$gid]=$val;
			else unset($this->visible[$gid]);
			$send[]=$gid;
		}
		if(isset($this->World->CreatedObjects[$this->id])) // объект стоит на очереди, убираем
			unset($this->World->CreatedObjects[$this->id]);
		if(sizeof($send)>0)
			if($v) $this->World->sendObject($send,$this->id);
			else $this->World->RGame->multicast(TRCommander::ObjsHide, $this->id,true,TRGWorld::GS,$send);
		return true;
	}
	function setByPlayer($gid,$p,$v){		// игрок пытается изменить параметр объекта
		//echo RN.'setByPlayer: $gid='.$gid.', $p='.$p.', $v='.$v.RN;
		if(!isset($this->byPlayer[$gid][$p])) return false;
		if(!$this->byPlayer[$gid][$p]) return false;
		if(method_exists($this,$p) && is_array($v)){
			//echo $p;
			call_user_func_array(array(&$this, $p),$v);
			return true;
		}
		if(!isset($this->$p)) return false;
		$this->$p=$v;
		if(in_array($p, $this->pars)) $this->World->objectChanged($this->id,array($p));
		return true;
	}
	function setChangeable($gid,$p,$v){	// pазpешить игроку менять параметры
		if(!isset($this->byPlayer[$gid])) $this->byPlayer[$gid]=array();
		$this->byPlayer[$gid][$p]=$v;
		return true;
	}
	function sendChangeablesToPlayer($gid,$stack=true){
		if(!isset($this->byPlayer[$gid])) return false;
		if($stack) return $this->World->SendChangeables[$this->id][$gid]=true;
		$send=array();
		if(isset($this->byPlayer[$gid])) $send=$this->byPlayer[$gid];
		$send['id']=$this->id;
		$this->World->RGame->multicast(TRCommander::ObjsControl,$send,false,TRGWorld::GS,array($gid));
		//$command,&$content,$stack=true,$status=0,$plist=null)
	}
	
}

?>