# MazePHP
<img src="http://develnet.ru/uploads/images/00/00/09/2012/04/29/d4a6e4.jpg"  title="превью"  align="center"  alt="превью" />

Когда-то я <a href="http://develnet.ru/blog/40.html">писал</a> всякую непонятную хрень в группе статей "RGameEngine", которую мало кто читал, ещё меньше кто понимал и, в итоге, ни кому это не пригодилось вообще :) И вот, наконец, выкладываю играбельный результат! Это не то, к чему я стремился в той или иной степени, но как промежуточный вариант, имеет место быть.
<cut>

<h5>Игровой алгоритм</h5>
Запускается уровень. Задача игроков: <strong>нужно первым дойти до финиша</strong> (желтой ячейки). После факта финиширования запускается обратный отсчёт, по окончании которого меняется карта. Все игроки получают коды карты, генерируют её (на это по умолчанию отводится 3 секунды, должно хватать всем), после чего все игроки появляются в одной точке и снова бегут к финишу :)

<h5>Особенности</h5>
- любой игрок может исчезнуть, стать инкогнито (клавиша пробел). Например, чтобы скрыться от преследования, или втихаря обогнать всех.
- кто доходит до финиша, тому предоставляется возможность летать :) Клавиши движения превращаются в рычаги управления вектором ускорения своего движения.
- каждый уровень по возможности сопровождается случайной картинкой с сервера ob5.ru. По умолчанию картинки в среднем качестве сохраняются в папку Pictures
- игра написана на движке <a href="http://develnet.ru/blog/40.html">RGameEngine</a>. Первичный алгоритм генерации уровней написал hichkok, за что ему выдвигается особая благодарность, алгоритм хороший, но вышло говнокодисто :)

<h5>Перспективы развития</h5>
- любой участник сети develnet может написать свой адекватный генератор уровней, который я подгоню под игру (нужно уточнить все условия для этого);
- можно сделать расчёт нескольких равноудалённых от финиша респаунов, но для этого нужно бы другой генератор уровней, тот что сейчас мало для этого подходит;
- добавление возможности админить сервер;
- очки, статистика, базы игроков...
<s>- сделать лобби на отдельном сайте, в котором можно будет увидеть доступные запущенные серверы</s>

<h5>Запуск и настройка сервера</h5>
Запуск сервера производится файлом "START SERVER!.bat"
Настройка: в папке /server/files лежит файл options.txt, открываем его текстовым редактором и правим.
Список доступных параметров с их значениями по умолчанию и расшифровками:
<br>name=The First Maze Server - имя сервера, отображаемое в публичном списке серверов
<br>port=7931 - порт сервера
<br>addr=0.0.0.0 - занимаемый локальный адрес (по умолчанию адреса всех интерфейсов)
<br>wdth=20 - ширина карты
<br>hght=20 - высота карты
<br>finishtime=10.0 - время с момента первого финиширования до смены карты
<br>loadingtime=3.0 - время, дающееся клиентам на отрисовку карты
<br>maxplayers=32 - ограничение по кол-ву игроков
<br>maxconnections=300 - бессмысленный параметр
<br>globallobby=1 - регистрировать сервер в публичном списке серверов
<br>Лучше не удалять конфиг-файл.

<h5>Мой отзыв</h5>
Несмотря на свои же ожидания и прогнозы, игра получилась довольно забавной! В этой игре вы можете продемонстрировать не только скорость прохождения головоломки типа лабиринт, но, также, можете публично затроллить оппонентов, коварно <s>наяб</s> обманывать, направляя их на путь ложный. По моим наблюдениям, новички (и не только) обладают развитым стадным инстинктом, профессионалы же сей игры более индивидуальны и умеют управлять массами, ясно предвидеть исходы выбранных путей.

<h6>Замечания</h6>
Уровни 100х100 генерируются слишком долго, что даже игроки вылетают по таймауту на среднестатистических компах. Хоть такие уровни и не популярны, но это всё-равно не очень хорошо…
При плохом интернете наблюдаются лаги и задержки

<a href="http://www.microsoft.com/download/en/details.aspx?displaylang=en&id=29" target="_blank">Может потребоваться vcredist</a>
