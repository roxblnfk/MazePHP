<?
class  TRImageButton {		// объект levelList
	private static $objects=array('id'=>0);
	private $objs=array();
	public $id=0;
	public $var;	// переменная для свободного пользования. классом она не используется! применение: идентификация в своём понимании.
	// параметры объектов ( после изменения нужно вызвать refresh() )
	public $self=0;
	public $defaultStatus='normal';
	public $progressBar=false;	// включение режима полосы загрузки // int:% - для полосы загрузки
	public $fit=true;
	public $percent=100;	// 100% - для полосы загрузки
	public $x=0;
	public $y=0;
	public $w=0;
	public $h=0;
	public $caption='';
	public $cursor;
	public $images=array('normal'=>'{res}/button_1.gif','up'=>'{res}/button_2.gif','down'=>'{res}/button_3.gif');
	public $images1=array('normal'=>'{res}/button_2.gif','up'=>'{res}/button_3.gif','down'=>'{res}/button_2.gif');
	public $fonts=array(
		'normal'=>array('color'=>clSilver,'size'=>10,'style'=>array('fsBold')),
		'up'=>array('color'=>clYellow,'size'=>10,'style'=>array('fsBold')),
		'down'=>array('color'=>clWhite,'size'=>10,'style'=>array('fsBold')));
	// функции
	public $onClick=false;
	public $onDblClick=false;
	public $onMouseEnter=false;
	public $onMouseLeave=false;
	public $onMouseDown=false;
	public $onMouseMove=false;
	public $onMouseUp=false;
	
	function TRImageButton($parent,$x=0,$y=0,$w=0,$h=0,$label='',$progressBar=false,$images=array()){
		$this->id=self::$objects['id']++;
		self::$objects[$this->id]=&$this;
		if(isset($images['images'] )) $this->images =$images['images'];
		if(isset($images['images1'])) $this->images1=$images['images1'];
		$file=$this->images[$this->defaultStatus];
		$this->progressBar=$progressBar;
		$this->x=$x;
		$this->y=$y;
		$this->w=$w;
		$this->h=$h;
		$this->cursor=crDefault;
		$this->caption=$label;
		$this->objs['backImage']=$this->createImage($parent,$this->images[$this->defaultStatus]);
		$this->objs['frontImage']=$this->createImage($parent,$this->images1[$this->defaultStatus]);
		$this->objs['labelText']=$this->createLabel($parent,$label);
		$this->objs['labelText']->font->style=$this->fonts[$this->defaultStatus]['style'];
		$this->objs['labelText']->font->size=$this->fonts[$this->defaultStatus]['size'];
		$this->objs['labelText']->font->color=$this->fonts[$this->defaultStatus]['color'];
		$this->self=$this->objs['backImage']->self;
		$this->refresh();
		foreach($this->objs as $k=>&$v){
			$v->id=$this->id.'_'.$k;
			$v->onClick='TRImageButton::onClick';
			$v->onDblClick='TRImageButton::onDblClick';
			$v->onMouseEnter='TRImageButton::onMouseEnter';
			$v->onMouseLeave='TRImageButton::onMouseLeave';
			$v->onMouseDown='TRImageButton::onMouseDown';
			$v->onMouseMove='TRImageButton::onMouseMove';
			$v->onMouseUp='TRImageButton::onMouseUp';
		}
	}
	private function createImage($form,$file){
		$obj=new TMImage($form);
		$obj->parent=$form;
		$obj->transparent=false;
		$obj->stretch=true;
		$obj->loadFromFile($file);
		return $obj;
	}
	private function createLabel($form){
		$obj=new Tlabel($form);
		$obj->parent=$form;
		$obj->transparent=true;
		$obj->alignment='taCenter';
		$obj->layout='tlCenter';
		$obj->autoSize=false;
		return $obj;
	}
	public function free(){
		foreach($this->objs as $k=>&$v){
			if(is_object($v)) $v->free();
		}
	}
	public function repaint($status=null){	// lias of refresh
		self::refresh($status);
	}
	public function refresh($status=null){
		$this->objs['backImage']->stretch=(bool)$this->fit;
		$this->objs['frontImage']->stretch=(bool)$this->fit;
		if(!is_null($status)){
			//pre($status);
			if(isset($this->images[$status])){
				$this->objs['backImage']->picture->clear();
				$this->objs['backImage']->loadFromFile($this->images[$status]);
			}
			if(isset($this->images1[$status])){
				$this->objs['frontImage']->picture->clear();	// clear не везде ботает
				$this->objs['frontImage']->loadFromFile($this->images1[$status]);
			}
			if(isset($this->fonts[$status])){
				$this->objs['labelText']->font->style=$this->fonts[$status]['style'];
				$this->objs['labelText']->font->size=$this->fonts[$status]['size'];
				$this->objs['labelText']->font->color=$this->fonts[$status]['color'];
			}
			return true;
		}
		$pars=array('x','y','caption','w','h','cursor');
		foreach($this->objs as $k=>&$v){
			if(is_object($v))
				foreach($pars as &$vv){
					if(!is_null($v->$vv)) $v->$vv=$this->$vv;
				}
		}
		if(is_numeric($this->progressBar)){
			$this->objs['frontImage']->w=round($this->progressBar*$this->w/100);
			$this->objs['frontImage']->visible=true;
		} else $this->objs['frontImage']->visible=(bool)$this->progressBar;
	}
	
	public function hide(){
		foreach($this->objs as &$v){
			$v->hide();
		}
	}
	public function show(){
		foreach($this->objs as &$v){
			$v->show();
		}
	}
	public function toBack(){
		foreach($this->objs as &$v){
			$v->toBack();
		}
	}
	public function toFront(){
		$keys=array_keys($this->objs);
		for($i=count($keys);--$i>=0;) $this->objs[$keys[$i]]->toFront();
	}
	
	private function getButtonByObjectId($oid){
		$self=c($oid);
		$ids=explode('_',$self->id);
		$self=isset(self::$objects[$ids[0]]) ? self::$objects[$ids[0]] : false;
		return $self;
	}
	private function execEventFunction($param){
		if(is_bool($this->$param)) return false;
		if(is_string($this->$param)) return eval($this->$param.(substr($this->$param,-1)!=';' ? '($this->self);' : ''));
	}
	
	public function onDblClick($oid){
		$self=self::getButtonByObjectId($oid);
		if(false!==$self) $self->execEventFunction('onDblClick');
	}
	public function onClick($oid){
		$self=self::getButtonByObjectId($oid);
		if(false!==$self) $self->execEventFunction('onClick');
	}
	public function onMouseEnter($oid){
		$self=self::getButtonByObjectId($oid);
		$self->refresh('up');
		if(false!==$self) $self->execEventFunction('onMouseEnter');
	}
	public function onMouseLeave($oid){
		$self=self::getButtonByObjectId($oid);
		$self->refresh($self->defaultStatus);
		if(false!==$self) $self->execEventFunction('onMouseLeave');
	}
	public function onMouseDown($oid){
		$self=self::getButtonByObjectId($oid);
		$self->refresh('down');
		if(false!==$self) $self->execEventFunction('onMouseDown');
	}
	public function onMouseMove($oid){
		$self=self::getButtonByObjectId($oid);
		if(false!==$self) $self->execEventFunction('onMouseMove');
	}
	public function onMouseUp($oid){
		$self=self::getButtonByObjectId($oid);
		$self->refresh('up');
		if(false!==$self) $self->execEventFunction('onMouseUp');
	}
}
?>