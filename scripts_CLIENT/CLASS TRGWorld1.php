<?php // © roxblnfk 2012
class TRGWorld1CL extends TRGWorldCL{	// игровой 2D мир с кинематикой	// tennis
//	var $RGame;
	
//	var $Objects=array();	// array(oid=>object TRGWObject)
//	var $Groups=array();	// array of [group]=>array(oid=>&$obj[oid])
//	var $maxID=0;

//	var $procedID;		// номер фрейма, нужен для обозначения того, что объект просчитан в этом фрэйме

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
	// game
	var $GameOptions=array('BGImgSize'=>1000, 'BGImgSave'=>true);
	var $SteepSize=10;	// шаг, с которым движутся объекты
	var $w1_Control=false;
	
	function __construct(){
		parent::__construct();
		$this->Form=c('Index->scrollGameForm');
		$this->Form->doubleBuffered=true;
		return true;
	}
	function LevelStart(&$con){
		parent::LevelStart($con);
		// определим, чем мы управляем :Р
		$this->w1_Control=$this->w1_getControlUnit();
	}
	function LevelLoad(&$con){	// сервак прислал нам параметры левела
		//$LVL=TRLevelConstructor::Generate($con);
		//if(!is_array($LVL)) return false;
		// $A=array(
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
		$A=TRLevelConstructor::Generate($con);
		$w=$con['size'][0];
		$h=$con['size'][1];
		$r=20;//рамка
		$s=$con['steep_size'];
		$img=TRLevelConstructor::Draw($A,$w,$h,$s,$r);
		TRGUI::SetLevelImage($img);
		TRGUI::SetLevelImageVisible(true);
		
		//imagedestroy($img);
		$this->SteepSize=$s-1;
		if(isset($con['bgimage']))
			$this->LevelBGImage_SV($con['bgimage']);
	}
	function LevelFinished(&$con){
		TRGUI::SetLevelImageVisible(false);
	}
	function LevelBGImage_SV($id){	// нужно скачать картинку и поставить в фон
		//if(strtolower(substr($url,0,7))!=='http://') return false;
		$url='http://ob5.ru/download.php?id='.intval($id).'&w='.$this->GameOptions['BGImgSize'];
		$t = new TThread('TRGUI::BGImagetDownload');
		$t->____url = $url;
		$t->____file = $this->GameOptions['BGImgSave']
				? DOC_ROOT.'/Pictures/Img_'.$this->GameOptions['BGImgSize'].'_'.intval($id).'.jpg' : false;
		$t->____func = 'TRGUI::BGImagetDownloaded';
		$t->resume();
		return true;
	}
	function setControl(&$con){
		parent::setControl($con);
		$this->w1_Control=$this->w1_getControlUnit();	// определим, чем мы теперь управляем :Р
		alert($con);
	}
	function destroyObjects($ids){
		parent::destroyObjects($ids);
		if(in_array($this->w1_Control,$ids))	// определим, чем мы теперь управляем :Р
			$this->w1_Control=$this->w1_getControlUnit();
	}
	function w1_getControlUnit(){
		$cobjs=array_keys($this->myControls);
		for($i=0,$j=sizeof($cobjs);$i<$j;++$i){
			$o=&$this->myControls[$cobjs[$i]];
			if(get_class($o)!=='TRGWTank') continue;
			if(!isset($o->byPlayer['t_move'])) continue;
			return $o->id;
		}
		return false;
	}
	function w1_ControlMove($arr){
		if(false===$this->w1_Control) return false;
		if(!isset($this->Objects[$this->w1_Control])) return false;
		$o=&$this->Objects[$this->w1_Control];
		//$arr=rand(0,4);
		$o->t_move($arr);
		//pre($arr);
		return true;
	}
	function w1_HideShowTank(){
		if(false===$this->w1_Control) return false;
		if(!isset($this->Objects[$this->w1_Control])) return false;
		//$o=&$this->Objects[$this->w1_Control];
		//$arr=rand(0,4);
		$this->changeObject($this->w1_Control,array('w1_hidden'=>array()));
		//pre($arr);
		return true;
	}

}
?>