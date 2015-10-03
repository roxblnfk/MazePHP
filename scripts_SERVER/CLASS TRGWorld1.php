<?php // © roxblnfk 2012
class TRGWorld1 extends TRGWorld{	// игровой 2D мир с кинематикой	// tennis
//	var $RGame;
	
//	var $Objects=array();	// array(oid=>object TRGWObject)
//	var $Groups=array();	// array of [group]=>array(oid=>&$obj[oid])
//		[0]// шарики )
//		[1]// танчики	-	у них должны быть "владельцы"
//	var $maxID=0;

//	var $procedID;		// номер фрейма, нужен для обозначения того, что объект просчитан в этом фрэйме
	
//	var $DestroyedObjects=array();	// array(oid)
//	var $ChangedObjects=array();	// array of [oid]=>array(ch.params..)
	
//	var $dt;
//	var $dt2;	// (dt^2)/2
//	function RGWorld
//	function processingWorld($dt,$gt)
//	function createObject($type,$x=0,$y=0)
//	function destroyObject($grps,$id)
//	function delObjectInGroup($id,$g=0)
//	function addObjectInGroup($id,$g=0)
//	function procObjects()
//	function collision()
//	function objectChanged($id,$par)
//	var $WorldSize=array(0,0,360,360);	// array(left,up,right,down)
	var $SteepSize=10;	// шаг, с которым движутся объекты
	var $WorldSize=array(5,5);	// шаг, с которым движутся объекты
	var $GameOptions=array('FinishTime'=>10, 'LoadingTime'=>3);
	var $WorldArray=array();
	var $StartPos=array();	// позиции старта
	var $FinishPos=array();	// позиции финиша
	//var $WorldObjPos=array();	// позиции объектов в мире WorldArray
	var $LevelParams=array();	// здесь лежат параметры уровня для его воссоздания.
	var $ResultsOfCurrentLevel=array();	//[end,result]
	var $StartPositions=array();	//[start]// начальные атрибуты старта каждого игрока
	
	function __construct(&$RGame){
		parent::__construct($RGame);
		$this->Groups[0]=array();	// шарики )
		$this->Groups[1]=array();	// ходячие объекты
		$this->LevelLoad();
		// $this->WorldArray=array(
			// array(	'',	1,	'',	0,	'',	0,	'',	0,	''),
			// array(	0,	1,	0,	1,	0,	1,	1,	1,	0),
			// array(	'',	1,	'',	1,	'',	1,	'',	0,	''),
			// array(	0,	1,	1,	1,	0,	1,	0,	1,	0),
			// array(	'',	0,	'',	0,	'',	1,	'',	1,	''),
			// array(	1,	1,	1,	1,	1,	1,	1,	1,	0),
			// array(	'',	1,	'',	0,	'',	1,	'',	0,	''),
			// array(	0,	1,	0,	1,	0,	1,	0,	1,	1),
			// array(	'',	0,	'',	1,	'',	0,	'',	0,	''),
		// );
	}
	function LevelGenerate(){
		$Pars=$this->LevelParams;
		$size = $Pars["size"];
		$start_finish = $Pars["start_finish"];
		$key = $Pars["key"];
		return hichkok_brutal::generate_labirint_brutal($size, $start_finish, $key);
	}
	function LevelLoad(){
		$w=$this->WorldSize[0];
		$h=$this->WorldSize[1];
		//$this->StartPos=array(1,1);
		$this->StartPos=array($w-1,$h-1);
		$this->FinishPos=array(0,0);
		$s=explode(' ',microtime());
		$key= intval($s[0]) ^ (intval($s[1])<<16);
		//generate level
		$this->SteepSize=floor(max(5,min(25,600/$w,400/$h)));//размер ячейки
		$bgurl=$this->w1_getNewBGImage();
		$this->LevelParams=array(
				'size' => Array($w, $h),
				'start_finish' => array_merge($this->StartPos,$this->FinishPos),
				'steep_size' => $this->SteepSize,
				'key' => $key,
				'bgimage'=>$bgurl
			);
		
		$Players = $this->RGame->getPlayersArray(true, self::GS, true);
		$this->LevelSendToPlayer($Players);		// полсать уровень игрокам
		$this->WorldArray = $this->LevelGenerate();
		return $this->RGame->log('Новый уровень спроектирован!');
	}
	function LevelStart(){
		if($this->GameStarted) return false;
		parent::LevelStart();
		$this->ResultsOfCurrentLevel=array();
		$this->StartPositions=array();
		// все объекты на стартовую позицию
		$this->DestroyAllObjectsInGroup(1);
		$Players = $this->RGame->getPlayersArray(true, self::GS, true);
		// создаём управляемые игроками объекты
		$time=microtime(true);
		for($i=sizeof($Players);--$i>=0;){
			$gid=$Players[$i];
			$this->w1_gotTankToPlayer($gid);
			$this->StartPositions[$gid]=array('start'=>$time);
		}
	}
	function w1_gotTankToPlayer($gid){	// предоставить игроку его инвентарь
		if(!isset($this->RGame->Players[$gid])) return false;
		$id=$this->createObject(1,$this->StartPos[0],$this->StartPos[1],'TRGWTank');
		$this->addObjectInGroup($id,1);
		$o=&$this->Objects[$id];
		$o->w1_owner=$gid;
		$o->setChangeable($gid,'t_move',true);	// разрешаем движение этим объектом
		$o->setChangeable($gid,'w1_hidden',true);	// разрешаем управлять видимостью
		$o->sendChangeablesToPlayer($gid);
		$o->setVisible( $this->RGame->getPlayersArray(true, self::GS, true));	// сделать его видимым всем
		return true;
	}
	function LevelSendToPlayer($GIDs){
		$content=$this->LevelParams;
		for($i=0,$j=sizeof($GIDs);$i<$j;++$i){
			$gid=$GIDs[$i];
			if(!isset($this->RGame->Players[$gid])) continue;
			$p=&$this->RGame->Players[$gid];
			$p->sockSend(TRCommander::LevelParams,$content,$stack=false);
		}
		return true;
	}
	/* function w1_mixObjs(){
		//$bid=$this->createObject($type=1,$x=0,$y=0);
		//$this->addObjectInGroup($bid,0);
		$ks=array_keys($this->Groups[0]);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$id=$ks[$i];
			$this->Objects[$id]->setSpeed(rand(-15,15),rand(-15,15),false);	// запустить шары с новыми скоростями
			$this->Objects[$id]->setAcc(rand(-15,15),rand(-15,15));
		}
	} */
	/* function w1_destroyObjs(){
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$id=$ks[$i];
			$this->destroyObject($id);
		}
	} */
	/* function w1_hideObjs(){
		$ks=array_keys($this->Objects);
		//$ks=array_rand($ks,rand(0,sizeof($ks)-1));
		//$ps=$this->RGame->getPlayersArray(true);
		$p=$this->RGame->getPlayersArray(true,self::GS,true);
		//for($p=array(),$i=sizeof($ps);--$i>=0;) $p[]=$ps[$i][0];
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$id=$ks[$i];
			$this->Objects[$id]->setVisible($p, false);
		}
	} */
	/* function w1_showObjs(){
		$ks=array_keys($this->Objects);
		$p=$this->RGame->getPlayersArray(true,self::GS,true);
		//for($p=array(),$i=sizeof($ps);--$i>=0;) $p[]=$ps[$i][0];
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$id=$ks[$i];
			$this->Objects[$id]->setVisible($p, true);
		}
	} */
	/* function w1_createBall($gid,$con){
		if(!is_array($con)) return false;
		if(count($con)<4) return false;
		$con=array_values($con);
		$x=$con[0];
		$y=$con[1];
		$sx=$con[2];
		$sy=$con[3];
		$id=$this->createObject(1,$x,$y);
		$this->addObjectInGroup($id,0);
		$o=&$this->Objects[$id];
		$o->visible=array($gid=>true);	// так можно делать только в момент создания объекта
		$o->setSpeed($sx,$sy);
		$o->setAcc(0,98.1);	// добавим аналог земного притяжения
		
		$o->setChangeable($gid,'pX',true);
		$o->setChangeable($gid,'pY',true);
		$o->setChangeable($gid,'sX',true);
		$o->setChangeable($gid,'sY',true);
		$o->sendChangeablesToPlayer($gid);
		return true;
	} */
	/* function w1_collision(){	// в нашем мире иные законы
		$ks0=array_keys($this->Groups[0]);
		for($i=0,$j=sizeof($ks0);$i<$j;++$i){
			$id=$ks0[$i];
			if(!is_object($this->Groups[0][$id])){
				unset($this->Groups[0][$id]);
				continue;
			}
			$o=&$this->Groups[0][$id];
			if($o->pX < $this->WorldSize[0])		$nx=$this->WorldSize[0]-$o->pX;
			elseif($o->pX > $this->WorldSize[2])	$nx=2*$this->WorldSize[2]-$o->pX;
			else $nx=null;
			if($o->pY < $this->WorldSize[1])		$ny=$this->WorldSize[1]-$o->pY;
			elseif($o->pY > $this->WorldSize[3])	$ny=2*$this->WorldSize[3]-$o->pY;
			else $ny=null;
			if(isset($nx) || isset($ny)){
				$o->setPos($nx,$ny);
				$o->setSpeed(isset($nx) ? -$o->sX : null, isset($ny) ? -$o->sY : null);
			}
		}
	} */
	function onPlayerDestroy($gid){
		parent::onPlayerDestroy($gid);
		$ps=$this->RGame->getPlayersArray(true, self::GS, true);
		if(sizeof($ps)==0) $this->LevelStop();
		// удалить объекты, принадлежащие игроку
		$ks=array_keys($this->Groups[1]);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	//
			$id=$ks[$i];
			if(sizeof($this->Objects[$id]->byPlayer)==0) $this->destroyObject($id);
			elseif($this->Objects[$id]->w1_owner==$gid) $this->destroyObject($id);
		}
		return true;
	}
	function onPlayerCreate($gid){	// игрок создаётся/присоединяется
		parent::onPlayerCreate($gid);
		$ks=array_keys($this->Objects);
		for($i=0,$j=sizeof($ks);$i<$j;++$i){	// добавляем видимость ВСЕХ обектов новому игроку
			$id=$ks[$i];
			if(!$this->Objects[$id]->w1_hidden)
				$this->Objects[$id]->setVisible($gid,true);
			// $this->Objects[$id]->setChangeable($gid,'pX',true);
			// $this->Objects[$id]->setChangeable($gid,'pY',true);
			// $this->Objects[$id]->setChangeable($gid,'sX',true);
			// $this->Objects[$id]->setChangeable($gid,'sY',true);
		}
		if($this->GameStarted)
			$this->w1_gotTankToPlayer($gid);// создаём управляемый игроком объект
		$this->StartPositions[$gid]=array('start'=>microtime(true));
		$this->LevelSendToPlayer(array($gid));		// полсать уровень этому игроку
		if($this->GameStarted) $this->RGame->sendEvent(self::GS,'LevelStart','fuck eah :D погнали!',true,array(array($gid)));
		else $this->LevelStart();	// видимо карта не начата.. гыгы дак начнём
		return true;
	}
	function initStartGame(){
		
	}
	/* function w1_TankControl($gid,$con){
		var_dump($con);
	} */
	function w1_onPlayerFinished($gid,$obj=null){
		if(!isset($this->RGame->Players[$gid])) return false;
		$p=&$this->RGame->Players[$gid];
		if(isset($this->ResultsOfCurrentLevel[$gid])) return false;// игрок этот уже финишировал
		$this->ResultsOfCurrentLevel[$gid]=array(
				'end'=>microtime(true),
				'result'=>(microtime(true)-$this->StartPositions[$gid]['start']),
			);
		if(sizeof($this->ResultsOfCurrentLevel)==1){
			$this->SendGameMessage('ВНЕЗАПНО!');
			$wait=$this->GameOptions['FinishTime'];
			for($i=1,$j=floor($wait/10),$k=$wait%10;$i<=$j;++$i)
				$this->RGame->RTaskMamager->addTask($wait-$i*10+$k,
						array(&$this,'SendGameMessage'),
						array('Раунд завершится через '.($i*10).' сек.')
					);
			$this->RGame->RTaskMamager->addTask(10,
					array(&$this,'w1_LevelFinished'),
					array(microtime(true))
				);
		}
		if(!is_null($obj)){
			$obj->aP=5;
			$obj->sP=1;
			$this->objectChanged($obj->id,array('aP','sP'));
			$obj->w1_finished=true;
			//$obj->setAcc(null,9800.1);
			$p->sockSend(TRCommander::LevelFinished,
					$this->ResultsOfCurrentLevel[$gid]['result'],$stack=false);
		}
		$this->SendGameMessage($this->RGame->Players[$gid]->Name.' прошёл уровень! Время прохождения: '.round($this->ResultsOfCurrentLevel[$gid]['result'],$this->GameOptions['LoadingTime']).' сек.');
		
	}
	function w1_LevelFinished($time=null){	// $time-время начала конца игры
		if(false===$this->GameStarted) return false;
		if($time < $this->GameStarted) return false;
		$this->LevelStop();
		$this->LevelLoad();
		$this->RGame->RTaskMamager->addTask(3,
				array(&$this,'LevelStart'),
				array()
			);
		$this->SendGameMessage('Новая карта начнётся через 3 сек.');
	}
	function w1_getNewBGImage(){
		//return 'http://ob5.ru/download.php?id='.rand(1,39022).'&w=1000';
		return rand(1,39022);
	}
	
}
class TRGWTank extends TRGWObject{	// объект мира TRGWorld
	var $sP=false;// учитывать скорость в расчётах???? может быть указан в виде числа, как множитель
	var $aP=false;// учитывать ускорение в расчётах????
	var $pars=array('type','pX','pY','id','w1_owner','w1_hidden','aP','aY');	// параметры, о которых должны знать клиенты
// описывается объект коллизий
	var $cT=0;		// тип объекта : 0 - точка, 1 - круг(окружность)
	var $cS=10;		// int/float/array размеры объекта соответственно его типу
	var $cD=0;		// id текстуры

	var $type;
	// game
	var $w1_owner;	// хозяин фигуры
	var $w1_Pos=array(0,0);	//текущая позиция в сетке игрового мира
	var $w1_hidden=false;
	var $w1_finished=false;
	
	function __construct(&$World,$id,&$type,&$x=0,&$y=0){
		$this->w1_Pos=array($x*2,$y*2);
		$SS=$World->SteepSize;
		$x=20 + $x*$SS + 1;
		$y=20 + $y*$SS + 1;
		//echo $x.' '.$y;
		parent::__construct($World,$id,$type,$x,$y);
	}
	function t_move($arrow=0){	// 0 - на месте 1 - верх 2 - низ 3 - лево 4 - право
		if($this->World->GameStarted===false) return false;
		$arrow=max(0,min((int)$arrow,4));
		$SS=$this->World->SteepSize;
		$x=&$this->w1_Pos[0];
		$y=&$this->w1_Pos[1];
		$WorldArray=&$this->World->WorldArray;
		$FinishPos=&$this->World->FinishPos;
		//$this->setSpeed(0,0);
		$acc=(bool)$this->w1_finished;
		switch($arrow){
			case 1 :	// up
				if($acc) return $this->setAcc(null,$this->aY-10);
				if(!isset($WorldArray[$x][$y-2])) break;
				if($WorldArray[$x][$y-1]!==0) break;
				$y-=2;
				$this->setPos(null,$this->pY-$SS);
			break;
			case 2 :	// down
				if($acc) return $this->setAcc(null,$this->aY+10);
				if(!isset($WorldArray[$x][$y+2])) break;
				if($WorldArray[$x][$y+1]!==0) break;
				$y+=2;
				$this->setPos(null,$this->pY+$SS);
			break;
			case 3 :	// left
				if($acc) return $this->setAcc($this->aX-10,null);
				if(!isset($WorldArray[$x-2][$y])) break;
				if($WorldArray[$x-1][$y]!==0) break;
				$x-=2;
				$this->setPos($this->pX-$SS,null);
			break;
			case 4 :	// rigth
				if($acc) return $this->setAcc($this->aX+10,null);
				if(!isset($WorldArray[$x+2][$y])) break;
				if($WorldArray[$x+1][$y]!==0) break;
				$x+=2;
				$this->setPos($this->pX+$SS,null);
			break;
		}
		if($x==$FinishPos[0] && $y==$FinishPos[1])
			$this->World->w1_onPlayerFinished($this->w1_owner,$this);
		return true;
	}
	function w1_hidden(){	// меняет видимость
		$this->w1_hidden=!$this->w1_hidden;
		if(!$this->w1_hidden){
			$a = $this->World->RGame->getPlayersArray(true, TRGWorld1::GS, true);
			$this->setVisible($a,true);
		}else{
			$a=array_keys($this->visible);
			if(isset($this->visible[$this->w1_owner]))
				unset($a[array_search($this->w1_owner,$a)]);
			else $this->setVisible(array($this->w1_owner),true);
			if(sizeof($a)>0) $this->setVisible(array_values($a),false);
		}
		$this->World->objectChanged($this->id,array('w1_hidden'));
		return true;
	}
	
}
?>