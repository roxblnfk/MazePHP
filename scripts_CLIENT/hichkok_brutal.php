<?

// Брутал Лабиринт Среэйшн бай Хичкок
class hichkok_brutal {
	static $nx_steps = Array(1, 0, -1, 0);
	static $ny_steps = Array(0, -1, 0, 1);
	static function get_aval_steps(&$map, $w, $h, &$aval, $x, $y) {
		$v =	($x + 1 < $w && !isset($map[$y*$w+($x+1)]) ? '0':'') . 
				($y - 1 >= 0 && !isset($map[($y-1)*$w+$x]) ? '1':'') .
				($x - 1 >= 0 && !isset($map[$y*$w+($x-1)]) ? '2':'') .
				($y + 1 < $h && !isset($map[($y+1)*$w+$x]) ? '3':'');
		// заодно меняем и доступность для шагания:
		if ($v) $aval[$y*$w+$x] = true;
		else unset($aval[$y*$w+$x]);
		return $v;
	}

	static function generate_path_brutal(&$map, $w, $h, &$aval, &$way, $x, $y, $maxlen) {
		$cnt = 0;
		do {
			$map[$y*$w+$x] = true;
			$v = self::get_aval_steps($map, $w, $h, $aval, $x, $y);// смотрим, можем ли шагать из выбранной точки
			//заодно меняем (внутри функции) массив $aval в соотв. с этим
			// пока можем ходить и не прошли максимальную длину ветки:
			if (!$v) break; 
			if (++$cnt > $maxlen) break; 
			// еще есть, куда шагать (а это строка вида '01', '13' и т.д., каждая цифра - возможное направление)
			$d = intval($v[rand(0, strlen($v)-1)]); // .. выбрали направление
			$way[] = Array($x, $y, $d); // добавили в путь
			$x += self::$nx_steps[$d]; //
			$y += self::$ny_steps[$d]; // шагнули
		}while(true);
	}

	static function labirint_cvt(&$map, $w, $h, &$way) {
		// Инициализация со всеми стенами
		$A = Array();
		$b=array_fill(0,$w*2-1,1);
		$c=array_fill(0,$w*2-1,'');
		for($x=0; $x<$w-1; ++$x) $c[$x*2+1] = 1;
		for($y=0; $y<$h; ++$y){
			$A[$y*2] = $c;
			if($y < $h-1) $A[$y*2+1] = $b;
		}
		
		// Удаляем стены согласно пути
		//foreach ($way as $value) {
		for($i=0,$j=sizeof($way);$i<$j;++$i){
			$v=&$way[$i];
			$dx = self::$nx_steps[$v[2]];
			$dy = self::$ny_steps[$v[2]];
			$A[$v[1]*2+$dy][$v[0]*2+$dx] = 0;
		}
		
		/*
		print "<html><body><table border=0 cellpadding=0 cellspacing=0>";
		for ($y = 0; $y < $h*2-1; $y++) {
			print "<tr>";
			for ($x = 0; $x < $w*2-1; $x++) {
				print "<td>".($A[$y][$x] === '' ? "&nbsp;" : $A[$y][$x])."</td>";
			}
			print "</tr>";
		}
		print "</table></body></html>";*/
		return $A;
	}

	static function generate_labirint_brutal($size, $start_finish, $key) {

		// ключег пригодился
		srand(crc32($key));

		$w = $size[1];
		$h = $size[0];
		$wh=$w*$h;
		$map = Array(); // сбсно карта
		$way = Array(); // сюда сохраняем путь, чтобы потом удалить стены
		$aval = Array(); // сюда сохр. ячейки, из которых мы можем отводить ветки (рядом есть своб. клетки)
		
		$maxlen = intval(min(sqrt($wh)*10, $wh)); // надо подгонять
		$minlen = max(intval(sqrt($maxlen)), 5); //
		
		$x = $start_finish[1];
		$y = $start_finish[0];
		self::generate_path_brutal($map, $w, $h, $aval, $way, $x, $y, rand($minlen, $maxlen));
		while (sizeof($aval) > 0) {
			// выбираем место, из которого поведем ветку:
			$aind = rand(0, sizeof($aval)-1); // выбрали рандомно из доступных ячеек...
			$i = 0;
			reset($aval);
			while ($i++ < $aind) next($aval); //... получили индекс ячейки
			
			$ind = key($aval);
			$y = floor($ind / $w);
			$x = $ind - $y*$w; // ... разложили его на икс и игрек
			if($y>$h || $x>$w){
				unset($aval[$ind]);
				continue;
			}
			// понеслась
			self::generate_path_brutal($map, $w, $h, $aval, $way, $x, $y, rand($minlen, $maxlen));
		}
		
		// Преобразуем в формат Рокса :)
		return self::labirint_cvt($map, $w, $h, $way);
	}
}
?>