@echo off
C:
chdir \openQRM-Client\ICW\bin
cygrunsrv.exe -I "openQRM-monitord" -t auto -O -p "/cygdrive/c/openQRM-Client/openqrm-monitord.win"
cygrunsrv.exe -S "openQRM-monitord"


