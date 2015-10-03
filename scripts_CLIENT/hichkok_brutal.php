<?

// ������ �������� ������� ��� ������
class hichkok_brutal {
	static $nx_steps = Array(1, 0, -1, 0);
	static $ny_steps = Array(0, -1, 0, 1);
	static function get_aval_steps(&$map, $w, $h, &$aval, $x, $y) {
		$v =	($x + 1 < $w && !isset($map[$y*$w+($x+1)]) ? '0':'') . 
				($y - 1 >= 0 && !isset($map[($y-1)*$w+$x]) ? '1':'') .
				($x - 1 >= 0 && !isset($map[$y*$w+($x-1)]) ? '2':'') .
				($y + 1 < $h && !isset($map[($y+1)*$w+$x]) ? '3':'');
		// ������ ������ � ����������� ��� �������:
		if ($v) $aval[$y*$w+$x] = true;
		else unset($aval[$y*$w+$x]);
		return $v;
	}

	static function generate_path_brutal(&$map, $w, $h, &$aval, &$way, $x, $y, $maxlen) {
		$cnt = 0;
		do {
			$map[$y*$w+$x] = true;
			$v = self::get_aval_steps($map, $w, $h, $aval, $x, $y);// �������, ����� �� ������ �� ��������� �����
			//������ ������ (������ �������) ������ $aval � �����. � ����
			// ���� ����� ������ � �� ������ ������������ ����� �����:
			if (!$v) break; 
			if (++$cnt > $maxlen) break; 
			// ��� ����, ���� ������ (� ��� ������ ���� '01', '13' � �.�., ������ ����� - ��������� �����������)
			$d = intval($v[rand(0, strlen($v)-1)]); // .. ������� �����������
			$way[] = Array($x, $y, $d); // �������� � ����
			$x += self::$nx_steps[$d]; //
			$y += self::$ny_steps[$d]; // �������
		}while(true);
	}

	static function labirint_cvt(&$map, $w, $h, &$way) {
		// ������������� �� ����� �������
		$A = Array();
		$b=array_fill(0,$w*2-1,1);
		$c=array_fill(0,$w*2-1,'');
		for($x=0; $x<$w-1; ++$x) $c[$x*2+1] = 1;
		for($y=0; $y<$h; ++$y){
			$A[$y*2] = $c;
			if($y < $h-1) $A[$y*2+1] = $b;
		}
		
		// ������� ����� �������� ����
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

		// ������ ����������
		srand(crc32($key));

		$w = $size[1];
		$h = $size[0];
		$wh=$w*$h;
		$map = Array(); // ����� �����
		$way = Array(); // ���� ��������� ����, ����� ����� ������� �����
		$aval = Array(); // ���� ����. ������, �� ������� �� ����� �������� ����� (����� ���� ����. ������)
		
		$maxlen = intval(min(sqrt($wh)*10, $wh)); // ���� ���������
		$minlen = max(intval(sqrt($maxlen)), 5); //
		
		$x = $start_finish[1];
		$y = $start_finish[0];
		self::generate_path_brutal($map, $w, $h, $aval, $way, $x, $y, rand($minlen, $maxlen));
		while (sizeof($aval) > 0) {
			// �������� �����, �� �������� ������� �����:
			$aind = rand(0, sizeof($aval)-1); // ������� �������� �� ��������� �����...
			$i = 0;
			reset($aval);
			while ($i++ < $aind) next($aval); //... �������� ������ ������
			
			$ind = key($aval);
			$y = floor($ind / $w);
			$x = $ind - $y*$w; // ... ��������� ��� �� ��� � �����
			if($y>$h || $x>$w){
				unset($aval[$ind]);
				continue;
			}
			// ���������
			self::generate_path_brutal($map, $w, $h, $aval, $way, $x, $y, rand($minlen, $maxlen));
		}
		
		// ����������� � ������ ����� :)
		return self::labirint_cvt($map, $w, $h, $way);
	}
}
?>