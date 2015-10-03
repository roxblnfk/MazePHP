<?php	// © roxblnfk 2011
class TRGUI{
	static $RGameClient; // TRGameClient
	static $played=false;
	
	static $Interface=array();
	static $Images=array(	// массив со всякими картинками интерфейса
		'buttoon_1'=>array('normal'=>'files/images/buttoon_1a.png','up'=>'files/images/buttoon_1b.png','down'=>'files/images/buttoon_1c.png')
	);
	static $Options=array('bgimgsize'=>1000,'bgimgsave'=>true,
			'lobbyServer'=>'maze.roxblnfk.16mb.com');
	static $KeysDowned=array();
	
	static $CoursorPos=array();
	static $GUIFlags=array('ChatEnterFocus'=>false);
	
	function init($form){
		self::$Interface['Index']=c('Index');
		self::$Interface['popup']=c('popupIndex');	// reserved
		self::$Interface['GeneralChat']=c('GeneralChat');
		self::$Interface['editGeneralChat']=c('GeneralChat->editGeneralChat');
		self::$Interface['Index']->caption=base64_decode('TWF6ZSBQSFAgqSByb3hibG5mayAyMDExLTIwMTI=');
		self::$Interface['BGImage']=c('Index->imageBackground');
		self::$Interface['LevelImg']=c('Index->imageLevel');
		self::$Interface['GameForm']=c('Index->scrollGameForm');
		self::$Interface['IPBox']=c('Index->editIPBox');
		self::$Interface['PortBox']=c('Index->editPortBox');
		self::$Interface['MyNameBox']=c('Index->editMyName');
		self::$Interface['Connect']=new TRImageButton($form,0,45,140,34,'',false
				,array('images'=>self::$Images['buttoon_1']));
		self::$Interface['Connect']->fonts['normal']['color']=clWhite;
		//self::$Interface['Connect']->fonts['up']['color']=clBlack;
		self::$Interface['Connect']->fonts['down']['color']=clWhite;
		self::$Interface['Connect']->cursor=crHandPoint;
		self::$Interface['Connect']->refresh('normal');
		self::$Interface['GetLobby']=new TRImageButton($form,0,80,140,34,'Поиск серверов',false,array('images'=>self::$Images['buttoon_1']));
		self::$Interface['GetLobby']->fonts['normal']['color']=clWhite;
		self::$Interface['GetLobby']->fonts['down']['color']=clWhite;
		self::$Interface['GetLobby']->cursor=crHandPoint;
		self::$Interface['GetLobby']->onClick='TRGUI::lobbyServersFind();';
		self::$Interface['GetLobby']->refresh('normal');
		self::OptionsLoad();
		self::showLobby(true);
		//$obj=TRImages::createFigure(self::$Interface['GameForm']);
	}
	function start(){
		if(self::$played) return false;
		self::$RGameClient=new TRGameClient();
		$ip=c('Index->editIPBox')->text;
		$port=c('Index->editPortBox')->text;
		self::$RGameClient->bind($ip,$port);
		self::$played=true;
		self::showLobby(false);
		self::$RGameClient->Name=trim(c('Index->editMyName')->text);
		self::$RGameClient->RGWorld->GameOptions['BGImgSize']=self::$Options['bgimgsize'];
		self::$RGameClient->RGWorld->GameOptions['BGImgSave']=self::$Options['bgimgsave'];
		
		self::$RGameClient->start();
		self::onStopGame();
	}
	function stop(){
		if(!self::$played) return false;
		self::$RGameClient->stop();
		self::onStopGame();
	}
	function onStopGame(){
		if(!self::$played) return false;
		self::$played=false;
		self::showLobby(true);
	}
	function showLobby($true=true){
		self::$Interface['Connect']->onClick=$true ? 'TRGUI::start();' : 'TRGUI::stop();';
		self::$Interface['Connect']->caption=$true ? 'Присоединиться' : 'Отсоединиться';
		self::$Interface['Connect']->refresh();
		self::$Interface['GetLobby']->{($true ? 'show' : 'hide')}();
		self::$Interface['GeneralChat']->{($true ? 'hide' : 'show')}();
		$enabled=array('Index->editIPBox'=>true,'Index->editPortBox'=>true,'Index->editMyName'=>true,);
		foreach($enabled as $name=>$val){ c($name)->enabled=$true ? $val : !$val; }
	}
	function popupPickServer($a){
		if(self::$played) return false;
		self::$Interface['IPBox']->text=strval($a['ip']);
		self::$Interface['PortBox']->text=abs(intval($a['port']));
	}
	function lobbyServersFinded($content){	// инфа о серверах закачана
		if($GLOBALS['THREAD_SELF']) return false;
		self::$Interface['GetLobby']->caption='Поиск серверов';
		self::$Interface['GetLobby']->refresh();
		err_no();
		$a=unserialize($content);
		err_yes();
		if(!is_array($a)) return false;
		$ks=array_keys($a);
		$aa=array();
		$keys=array('svname','ip','port','width','height','lastupd','maxplayers','psonline','sversion');
		self::$Interface['popup']=false;
		self::$Interface['popup']=new TPopupMenu;
		$menu=new TMenuItem;
		$menu->caption='Закрыть список';
		self::$Interface['popup']->addItem($menu); 
		for($i=0,$j=sizeof($ks);$i<$j;++$i){
			$k=$ks[$i];
			if(!is_array($a[$k])) continue;
			if(!is_keys($a[$k],$keys)) continue;
			$menu=false;
			$menu=new TMenuItem;
			$name=trim(mb_convert_encoding(strval($a[$k]['svname']),'cp1251','utf8'));
			if(!$name) $name='<Безымянный сервер>';
			$sversion=strval($a[$k]['sversion']);
			$psonline=abs(intval($a[$k]['psonline']));
			$maxplayers=abs(intval($a[$k]['maxplayers']));
			$menu->caption='['.$psonline.'/'.$maxplayers.'] '.substr($name,0,30)
					.' (v'.substr($sversion,0,12).')';
			$val1=$a[$k];
			$menu->onclick=function($id)use($val1){TRGUI::popupPickServer($val1);};
			self::$Interface['popup']->addItem($menu); 
		}
		if(self::$played) return false;
		$x=self::$Interface['Index']->x+self::$Interface['GetLobby']->x;
		$y=self::$Interface['Index']->y+self::$Interface['GetLobby']->y;
		self::$Interface['popup']->popup($x,$y);
	}
	function lobbyServersFind($id=null){	// искать серверы на сайте
        if(!$GLOBALS['THREAD_SELF']){	// проверка на поточность
			self::$Interface['GetLobby']->onClick=false;
			self::$Interface['GetLobby']->caption='Идёт поиск...';
			self::$Interface['GetLobby']->onClick='TRGUI::lobbyServersFind();';
			self::$Interface['GetLobby']->refresh();
			$t = new TThread('TRGUI::lobbyServersFind');
			$t->____url='http://'.self::$Options['lobbyServer'].'/?getlobbyes'
				.'&name='.urlencode(self::$Interface['MyNameBox']->text)
				.'&version='.urlencode(TRGameClient::VERSION);
			$t->____func='TRGUI::lobbyServersFinded';
			$t->resume();
			return true;
		}
		$t = TThread::get($id);
		$url=$t->____url;
		$func=$t->____func;
		$content=@file_get_contents($url);
		sync($func, array($content));
		return true;
	}
	function generalChatSend($t){
		if(!self::$played) return false;
		if(self::$RGameClient->RChatTool->sendMessage($t))
			self::$Interface['editGeneralChat']->text='';
		if(self::$GUIFlags['ChatEnterFocus']){
			self::$GUIFlags['ChatEnterFocus']=false;
			self::$Interface['Index']->toFront();
			// self::$Interface['MyNameBox']->enabled=true;
			// self::$Interface['MyNameBox']->setFocus();
			// self::$Interface['MyNameBox']->enabled=false;
		}
	}
	/* function w1_mixObjs(){
		if(!self::$played) return false;
		self::$RGameClient->sockSend(99957,123,true);
		return true;
	} */
	/* function w1_destroyObjs(){
		if(!self::$played) return false;
		self::$RGameClient->sockSend(99956,123,true);
		return true;
	} */
	/* function w1_hide(){
		if(!self::$played) return false;
		self::$RGameClient->sockSend(99961,123,true);
		return true;
	} */
	/* function w1_show(){
		if(!self::$played) return false;
		self::$RGameClient->sockSend(99962,123,true);
		return true;
	} */
	/* function scrollGameFormUp($button,$shift,$x,$y){
		if(!self::$played) return false;
		$sx=$x-self::$CoursorPos[0];
		$sy=$y-self::$CoursorPos[1];
		self::$RGameClient->sockSend(99958,array(self::$CoursorPos[0],self::$CoursorPos[1],$sx,$sy),true);
		return true;
	} */
	/* function scrollGameFormDown($button,$shift,$x,$y){
		if(!self::$played) return false;
		self::$CoursorPos=array($x,$y);
		return true;
	} */
	function w1_MoveTankTo($arr=0){
		if(!self::$played) return false;
		self::$RGameClient->RGWorld->w1_ControlMove($arr);
		return true;
	}
	function w1_HideShowTank(){
		if(!self::$played) return false;
		self::$RGameClient->RGWorld->w1_HideShowTank();
		return true;
	}
	function ButtonAction($key){
		$key=strtolower($key);
		switch($key){
			case 38 :
			case 87 : self::w1_MoveTankTo(1); break;
			case 40 :
			case 83 : self::w1_MoveTankTo(2); break;
			case 37 :
			case 65 : self::w1_MoveTankTo(3); break;
			case 39 :
			case 68 : self::w1_MoveTankTo(4); break;
			case 96 :
			case 32 : self::w1_HideShowTank(); break;
			case 13 : 
				self::$GUIFlags['ChatEnterFocus']=true;
				self::$Interface['editGeneralChat']->setFocus();
			break;
			//default: pre($key); break;
		}
	}
	function KeyboardButtonDown($key){
		self::$KeysDowned[$key]=array(microtime(true),1);
		self::ButtonAction($key);
	}
	function KeyboardButtonUp($key){
		unset(self::$KeysDowned[$key]);
	}
	function KeyboardDisabled(){
		self::$KeysDowned=array();
	}
	
	function BGImagetDownloaded($content,$file){
		self::SetBackgroundImage($content,$file);
	}
	function BGImagetDownload($id){
        if(!$GLOBALS['THREAD_SELF']) return;	// проверка на поточность
		$t = TThread::get($id);
		$url=$t->____url;
		$func=$t->____func;
		$file=$t->____file;
		$content=file_get_contents($url);
		sync($func, array($content,$file));
		return true;
	}
	function SetLevelImageVisible($show=true){
		if($show)	self::$Interface['LevelImg']->show();
		else		self::$Interface['LevelImg']->hide();
	}
	function SetLevelImage(&$img){
		self::$Interface['LevelImg']->picture->loadFromStr(TRLevelConstructor::ImageToStr($img),'png');
		$w=imagesx($img);
		$h=imagesy($img);
		imagedestroy($img);
		self::$Interface['LevelImg']->w=$w;
		self::$Interface['LevelImg']->h=$h;
		$bg=array(self::$Interface['BGImage']->w,self::$Interface['BGImage']->h);
		self::$Interface['BGImage']->w=$w;
		self::$Interface['BGImage']->h=$h;
		self::SetGameZoneSize($w,$h);
		if($bg[0]!=$w || $bg[1]!=$h)
			self::SetBackgroundImage(null);
	}
	function SetBackgroundImage($imgStr=null,$file=false){
		if(is_null($imgStr))
			$imgStr=file_get_contents(DOC_ROOT.'/files/images/DefaultImage.jpg');
		$w=self::$Interface['BGImage']->w;
		$h=self::$Interface['BGImage']->h;
		$img=TRLevelConstructor::ImageToSize($imgStr,$w,$h,$file);
		if(false===$img) return false;
		self::$Interface['BGImage']->picture->loadFromStr(TRLevelConstructor::ImageToStr($img),'png');
		imagedestroy($img);
	}
	function SetGameZoneSize($w,$h){
		self::$Interface['GameForm']->w=$w;
		self::$Interface['GameForm']->h=$h;
		self::$Interface['IPBox']->x=$w;
		self::$Interface['PortBox']->x=$w+self::$Interface['IPBox']->w;
		self::$Interface['MyNameBox']->x=$w;
		self::$Interface['Connect']->x=$w;
		self::$Interface['Connect']->refresh();
		self::$Interface['GetLobby']->x=$w;
		self::$Interface['GetLobby']->refresh();
		self::$Interface['Index']->clientWidth=$w+self::$Interface['MyNameBox']->w;
		self::$Interface['Index']->clientHeight=max(80,$h);
	}
	function process(){	// своеобразный таймер на GUI :D
		
	}
	
	function OptionsSave(){
		$s['addr']=self::$Interface['IPBox']->text;
		$s['port']=(int)self::$Interface['PortBox']->text;
		$s['name']=self::$Interface['MyNameBox']->text;
		$s['bgimgsize']=self::$Options['bgimgsize'];
		$s['bgimgsave']=self::$Options['bgimgsave'];
		$str='';
		for($ks=array_keys($s),$i=0,$j=sizeof($ks);$i<$j;++$i){
			$k=$ks[$i];
			$str.=$k.' = '.$s[$k]."\r\n";
		}
		file_put_contents(DOC_ROOT.'/files/options.txt',$str);
		return true;
	}
	function OptionsLoad(){
		$f=DOC_ROOT.'/files/options.txt';
		if(is_file($f)) $c=@file_get_contents($f); else $c=false;
		$dt=array();
		if(is_string($c)){
			$l=explode("\n",$c);
			for($i=0,$j=sizeof($l);$i<$j;++$i){
				$kv=explode('=',$l[$i],2);
				$k=strtolower(trim($kv[0]));
				if(!$k) continue;
				$v=isset($kv[1]) ? trim($kv[1]) : true;
				$dt[$k]=$v;
			}
		}
		$k='addr';
		$ip=isset($dt[$k]) && $dt[$k]!='' ? $dt[$k] : '127.0.0.1';
		$k='port';
		$port=isset($dt[$k]) && (int)$dt[$k]>0 ? (int)$dt[$k] : 7931;
		$k='name';
		$name=isset($dt[$k]) && $dt[$k]!='' ? $dt[$k] : 'Игрок #'.rand(0,99999);
		$k='bgimgsize';
		$bgimgsize=isset($dt[$k]) && (int)$dt[$k]>100 ? (int)$dt[$k] : 1000;
		$k='bgimgsave';
		$bgimgsave=isset($dt[$k]) ? (bool)$dt[$k] : true;
		self::$Interface['IPBox']->text=$ip;
		self::$Interface['PortBox']->text=$port;
		self::$Interface['MyNameBox']->text=$name;
		self::$Options['bgimgsize']=$bgimgsize;
		self::$Options['bgimgsave']=$bgimgsave;
		return true;
	}
	
}
?>