@echo off
C:
chdir \openQRM-Client\ICW\bin
cygrunsrv.exe -E "openQRM-monitord"
cygrunsrv.exe -R "openQRM-monitord"


