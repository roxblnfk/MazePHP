:: (c) roxblnfk 2011
@echo off
::.\php5.3.1\php.exe _____COMPILLER.php
:__start
echo ���������� . . .
".\php5.3.1\php.exe" -c ".\php5.3.1\php.ini" _____COMPILLER.php
echo ����᪠� �ࢥ� . . .
".\php5.3.1\php.exe" -c ".\php5.3.1\php.ini" ".\scripts\include.php"
::".\php5.3.1\php.exe" -c ".\php5.3.1\php.ini" ".\_____SERVER.php"
pause
goto __start