<?php // © roxblnfk 2012
class TRGWorldCL{	// игровой 2D мир с кинематикой
	var $RGame;
	var $RPlayerList;
	
	var $Objects=array();	// array(oid=>object TRGWObject)
	var $ObjectsCL=array();	// array(oid=>object TRGWObject)	// объекты только на стороне клиента
	var $Groups=array();	// array of [group]=>array(oid=>&$obj[oid])
	var $maxID=0;
	
	var $ProcedID;		// номер фрейма, нужен для обозначения того, что объект просчитан в этом фрэйме
	
//	var $DestroyedObjects=array();	// array(oid)
//	var $ChangedObjects=array();	// array of [oid]=>array(ch.params..)
	var $dt=1;
	var $myControls=array();	// подконтрольные объекты игрока// array of $oid=>&object
	
	var $Form;	// форма дл яотрисовки
	function __construct(){
	}
	function __destruct(){
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	//
			$this->destroyObjects(array($ks[$i]));
		}
	}
	function LevelStart(&$con){ /* alert($con); */ }
	function processingWorld($dt,$gt,$ProcedID){
		if($gt==0) return false;
		$this->ProcedID=$ProcedID;
		$this->dt=$gt;
		$this->procObjects();
		//$this->sendChanges();
		return true;
	}
	function setControl(&$con){	// array(id=>id, [aX aY sX...])	// сервер присылает инфу о контролируемом объекте
		if(!is_array($con)) return false;
		if(!isset($con['id'])) return false;
		$id=$con['id']; unset($con['id']);
		if(!isset($this->Objects[$id])) return false;
		$o=&$this->Objects[$id];
		$o->byPlayer=array();
		$this->myControls[$id]=&$this->Objects[$id];
		$ks=array_keys($con);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$pn=$ks[$i];// имя параметра
			if(!$con[$pn]) continue;
			if(!method_exists($o,$pn)) if(!isset($o->$pn)) continue;
			$o->byPlayer[$pn]=$con[$pn];
		}
		//pre($o->byPlayer);
		return true;
	}
	function changeObjectSV(&$con){	// изменить объект по велению сервера
		if(!is_array($con)) return false;
		if(!isset($con['id'])) return false;
		$id=$con['id'];
		if(!isset($this->Objects[$id])) return false;
		$o=&$this->Objects[$id];
		unset($con['id']);
		$ks=array_keys($con);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$pn=$ks[$i];// имя параметра или функции
			if(method_exists($o,$pn)){
				call_user_func(array(&$o, $pn),$con[$pn]);
				continue;
			}
			if(!isset($o->$pn)) continue;
			$o->$pn=$con[$pn];
		}
		$o->repaint();
		//pre($con);
		return true;
	}
	function changeObject($oid,$pv){	// послать серверу запрос на изменение объекта	// pv=array('pX'=>112,'pY'=>123)
		if(!is_array($pv)) return false;
		if(sizeof($pv)==0) return false;
		$content=array($oid,$pv);
		$this->RGame->sockSend(TRCommanderCL::ObjsChang,$content,$stack=false);
	}
	function createObject(&$con){	// создать объект по велению сервера
		if(!is_array($con)) return false;
		//pre($con);
		$need=array(1=>'type','pX','pY','ClassName');
		for($i=sizeof($need)+1;--$i;)
			if(!isset($con[$need[$i]])) 
				return false;
			else{
				${$need[$i]}=$con[$need[$i]];
				unset($con[$need[$i]]);
			}
		if(isset($con['id'])) $id=$con['id']; else return false;	//  так надо
		if(!class_exists($ClassName)) return false;
		if(isset($this->Objects[$id])){
			alert('destr');
			$this->destroyObjects(array($id));
		}
		$this->Objects[$id]=new $ClassName($this,$id,$type,$pX,$pY);
		$this->changeObjectSV($con);
		return true;
	}
/* 	function addObjectInGroup($id,$g=0){
		if(!isset($this->Objects[$id])) return false;
		if(isset($this->Groups[$g][$id])) if($this->Groups[$g][$id]!==false) return false;
		$this->Groups[$g][$id]=&$this->Objects[$id];
		return true;
	}
	function delObjectInGroup($id,$g=0){
		if(!isset($this->Groups[$g][$id])) return false;
		unset($this->Groups[$g][$id]);
		return true;
	} */
	function destroyObjects($ids){	// уничтожить объекты по велению сервера // id=array(oid's)
		$ids=array_values($ids);
		for($i=sizeof($ids);$i--;){
			$id=$ids[$i];
			if(!isset($this->Objects[$id])) continue;
			$this->Objects[$id]=false;
			if(isset($this->myControls[$id])) unset($this->myControls[$id]);
			unset($this->Objects[$id]);
		}
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
	// function collision(){
		
	// }
	// function objectChanged($id,$par){	// параметр объекта изменён, это надо зафиксировать и потом передать клиентам
		// if(!isset($this->Objects[$id])) return false;
		// $this->ChangedObjects[$id]
	// }
	/* function sendChanges(){	// отправить клиентам изменения мира
		// созданные объекты
		//$this->RGame->multicast(TRCommander::ObjsDestr, $this->DestroyedObjects,true);
		//$this->DestroyedObjects=array();
		// уничтоженные объекты
		$this->RGame->multicast(TRCommander::ObjsDestr, $this->DestroyedObjects,true);
		$this->DestroyedObjects=array();
		// изменённые объекты
		$ks=array_keys($this->ChangedObjects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			
		}
		$this->RGame->multicast(TRCommander::ObjsDestr, $this->DestroyedObjects,true);
		$this->DestroyedObjects=array();
	}// */
	function hideObject($con){	// команда с сокета на скрытие объекта. Когда объект скрыт, информация от сервера о нём не поступает! Поэтому есть смысл его скрывать :)
		$id=(int)$con;
		if(!isset($this->Objects[$id])) return false;
		$this->Objects[$id]->hide();
		return true;
	}
	function sendetObject(&$con){// присылается полная информация об объекте, если объекта нет то его надо создать
		if(isset($con['id'])) $id=$con['id']; else return false;
		if(!isset($this->Objects[$id])) $this->createObject($con);
		else{
			$this->changeObjectSV($con);
			$this->Objects[$id]->show();
		}
	}
	function GameMessage($con){	// array(time,message)
		if(!isset($con[1])) return false;
		//pre($con);
		$msg=$con[1];
		$this->RGame->RChatTool->addMessage(array(4,mktime(),0,$msg),false);
	}
	
}
class TRGWObject{	// объект мира TRGWorld
	var $World;		// ссылка на объект мира
	var $proced;	// объект просчитан? сюда записывается номер кадра последнего просчёта
	var $id;
// видимость для игроков: true/false/array(PID's)
	var $visible=true;
	var $byPlayer=array();	// array of GID=>array('pX/pY...'=>N/A)	// подконтрольные нам параметры
	var $Images=array();
// скорости и ускорения указываются только как проекции на оси X и Y
	var $pX=0;		// pos X
	var $pY=0;		// pos Y
	var $pD=0;		// направление объекта (куда смотрит), rad
	var $sP=true;// учитывать скорость в расчётах???? может быть указан в виде числа, как множитель // false - static object
	var $sX=0;		// speed X
	var $sY=0;		// speed Y
	var $sD=0;		// направление скорости в радианах
	var $aP=true;// учитывать ускорение в расчётах????	// false - тоолько линейные скорости или статика (без ускорений)
	var $aX=0;		// acceleration Y
	var $aY=0;		// acceleration Y
	var $aD=0;		// направление ускорения в радианах
// описывается объект коллизий
	var $cT=0;		// тип объекта : 0 - точка, 1 - круг(окружность)
	var $cS=0;		// int/float/array размеры объекта соответственно его типу
	var $cD=0;		// id текстуры
	
	
	var $type;
//
	function __construct(&$World,$id,$type,$x,$y){
		$this->World=&$World;
		$this->id=$id;
		$this->type=$type;
		$this->pX=$x;
		$this->pY=$y;
		//$this->Images[]=TRImages::Image($this->World->Form,0,0,16,16,DOC_ROOT.'/data/knob2_b1.gif');
		//$this->setImages(array('Image',array(DOC_ROOT.'/data/knob_3c.png')));
	}
	function setImages($images=array()){
		// array( id=>array('createFunction',array(parametrs)) )
		//array_map('TRImages::destroyImage',$this->Images);
		//$this->Images=array();
		$ks=array_keys($images);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$k=$ks[$i];
			$fn=&$images[$k][0];
			$pars=&$images[$k][1];
			if(method_exists('TRImages',$fn))
				$this->Images[]=call_user_func_array(array('TRImages',$fn),$pars);
		}
	}
	function repaint(){
		for($i=sizeof($this->Images);$i--;){
			$this->Images[$i]->x=$this->pX;
			$this->Images[$i]->y=$this->pY;
		}
	}
	function proc(){	// обработать объект
		if(!$this->visible) return false;
		$dt=$this->World->dt;
		$dax=$this->aP * $this->aX * $dt;
		$day=$this->aP * $this->aY * $dt;
		$xx=$this->pX;
		$yy=$this->pY;
		$this->pX+=$this->sP * $this->sX * $dt + $dax*$dt;
		$this->pY+=$this->sP * $this->sY * $dt + $day*$dt;
		$this->sX+=$dax;
		$this->sY+=$day;
		if($this->pX!=$xx || $this->pY!=$yy) $this->repaint();
	}
	function setPos($x=null,$y=null,$au=false){
		$dt=$this->World->dt;
		$sx=$sy=null;
		if(is_numeric($x)){
			if($au)
				if($this->pX==$x) $sx=0;
				else $sx=($x-$this->pX)/$dt;
			$this->pX=$x;
		//	$this->World->objectChanged($this->id,array('pX'));
		}
		if(is_numeric($y)){
			if($au)
				if($this->pY==$y) $sy=0;
				else $sy=($y-$this->pY)/$dt;
			$this->pY=$y;
		//	$this->World->objectChanged($this->id,array('pY'));
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
		//	$this->World->objectChanged($this->id,array('sX','pX','pY'));
			
		}
		if(is_numeric($sy)){
			if($au)
				if($this->sY==$sy) $this->aY=0;
				else $this->aY=($sy-$this->sY)/$dt;
			$this->sY=$sy;
		//	$this->World->objectChanged($this->id,array('sY','pX','pY'));
		}
		return true;
	}
	function setAcc($ax=null,$ay=null){	// установить ускорение
		if(is_numeric($ax)){
			$this->aX=$ax;
		//	$this->World->objectChanged($this->id,array('aX','pX','pY'));
		}
		if(is_numeric($ay)){
			$this->aY=$ay;
		//	$this->World->objectChanged($this->id,array('aY','pX','pY'));
		}
		return true;
	}
	function __destruct(){
		array_map('TRImages::destroyImage',$this->Images);
	}
	function hide(){
		$this->visible=false;
		for($i=sizeof($this->Images)-1;$i>=0;--$i){
			$this->Images[$i]->hide();
		}
	}
	function show(){
		$this->visible=true;
		for($i=sizeof($this->Images)-1;$i>=0;--$i){
			$this->Images[$i]->show();
		}
	}
	
}
class TRGWTank extends TRGWObject{	// объект мира TRGWorld
	var $sP=false;// учитывать скорость в расчётах???? может быть указан в виде числа, как множитель
	var $aP=false;// учитывать ускорение в расчётах????
// описывается объект коллизий
	var $cT=0;		// тип объекта : 0 - точка, 1 - круг(окружность)
	var $cS=0;		// int/float/array размеры объекта соответственно его типу
	var $cD=0;		// id текстуры

	var $type;
	// game
	var $w1_owner;	// хозяин фигуры
	var $w1_hidden=false;
	
	function __construct(&$World,$id,$type,$x,$y){
		parent::__construct($World,$id,$type,$x,$y);
		//alert(1);
		$this->cS=&$this->World->SteepSize;
	}
	function t_move($arrow=0){
		$this->World->changeObject($this->id,array('t_move'=>array($arrow)));
	}
	function w1_owner($gid){
		$this->w1_owner=$gid;
		if(!isset($this->World->RPlayerList->Players[$gid])) return false;
		$p=&$this->World->RPlayerList->Players[$gid];
		//pre($p['color']);
		//pre(decbin(clBlue));
		$color=($p['color'][2]<<16 & 0xFF0000) | ($p['color'][1]<<8 & 0xFF00) | ($p['color'][0] & 0xFF);
		//pre(dechex($color));
		$this->setImages(array(array('Figure',array(&$this->World->Form,$this->pX,$this->pY,$this->cS,$this->cS,null,$color,null,$p['name']))));
		$this->w1_hidden();
		//$obj=TRImages::Figure(TRGUI::$Interface['GameForm']);
	}
	function w1_hidden($val=null){
		if(!is_null($val)) $this->w1_hidden=$val;
		$v = $this->w1_hidden ? pmNotCopy : pmCopy;
		for($i=sizeof($this->Images)-1;$i>=0;--$i) $this->Images[$i]->penMode=$v;
		return true;
	}
	
}
class TRImages{
	function Figure($form,$x=0,$y=0,$w=16,$h=16,$shape=null,$brushColor=null,$brushStyle=null,$hint=null){
		$obj=new TShape($form);
		$obj->parent=$form;
		//$obj->enabled=false;
		$obj->x=$x;
		$obj->y=$y;
		$obj->w=$w;
		$obj->h=$h;
		$obj->shape		=!is_null($shape)		? $shape		: stRoundRect;
		$obj->brushColor=!is_null($brushColor)	? $brushColor	: clRed;
		$obj->brushStyle=!is_null($brushStyle)	? $brushStyle	: bsSolid;
		$obj->hint=$hint;
		return $obj;
	}
	function Image($form,$x=0,$y=0,$w=16,$h=16,$img){
		$obj=new TMImage($form);
		$obj->parent=$form;
		$obj->transparent=false;
		$obj->enabled=false;
		$obj->x=$x;
		$obj->y=$y;
		$obj->w=$w;
		$obj->h=$h;
		$obj->loadFromFile($img);
		return $obj;
	}
	function destroyImage(&$img){
		//if(get_class($img)=='TMImage') $img->clear();
		$img->free();
		$img=false;
		return true;
	}
}
?>