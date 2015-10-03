:: (c) roxblnfk 2011
@echo off
::.\php5.2.4\php.exe _____COMPILLER.php
:__start
echo Компилирую . . .
".\php5.2.4\php.exe" -c ".\php5.2.4\php.ini" _____COMPILLER.php
echo Запускаю сервер . . .
".\php5.2.4\php.exe" -c ".\php5.2.4\php.ini" ".\scripts\include.php"
::".\php5.2.4\php.exe" -c ".\php5.2.4\php.ini" ".\_____SERVER.php"
pause
goto __start