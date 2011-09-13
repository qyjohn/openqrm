#!/bin/bash

COPSSH_DOWNLOAD="http://downloads.sourceforge.net/project/sereds/Copssh/3.1.4/Copssh_3.1.4_Installer.zip"
WGET_DOWNLOAD="http://users.ugent.be/~bpuype/cgi-bin/fetch.pl?dl=wget/wget.exe"
SLEEP_DOWNLOAD="http://openqrm-support.de/openqrm-build/4.8/plugins/windows/sleep.exe"

if [ ! -f Copssh_3.1.4_Installer.zip ]; then
    echo "-> downloading Copssh 3.1.4 from $COPSSH_DOWNLOAD"
    wget $COPSSH_DOWNLOAD
    unzip Copssh_3.1.4_Installer.zip
fi
if [ ! -f wget.exe ]; then
    echo "-> downloading Wget.exe from $WGET_DOWNLOAD"
    wget $WGET_DOWNLOAD
fi
if [ ! -f sleep.exe ]; then
    echo "-> download Sleep.exe from $SLEEP_DOWNLOAD"
    wget $SLEEP_DOWNLOAD
fi


