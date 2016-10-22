@ECHO off

ECHO Startuji server
ECHO --------------------
php -dxdebug.remote_enable=1 -dxdebug.remote_mode=req -dxdebug.remote_port=9000 -dxdebug.remote_host=127.0.0.1 -dxdebug.remote_connect_back=0 -S 127.0.0.1:80 -t www