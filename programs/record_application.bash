#!/bin/bash

export wpub=$WIFF_CONTEXT_ROOT # same as `wiff.php --getValue=rootdirectory`
. "`dirname $0`"/core_environment

if [ -z "$2" ]; then
    "$wpub/wsh.php"  --api=appadmin --method=update --appname="$1"
    RET=$?
    exit $RET
fi

if [ "$2" = "I" ]; then
    "$wpub/wsh.php" --api=appadmin --method=init --appname="$1"
    RET=$?
    if [ $RET -ne 0 ]; then
	echo "Error: appadmin init of '$1' returned with exit code '$RET'"
	exit $RET
    fi
    
    "$wpub/wsh.php" --api=appadmin --method=update --appname="$1"
    RET=$?
    if [ $RET -ne 0 ]; then
	echo "Error: appadmin update of '$1' returned with exit code '$RET'"
	exit $RET
    fi
elif [ "$2" = "U" ]; then
    "$wpub/wsh.php" --api=appadmin --method=update --appname="$1"
    RET=$?
    if [ $RET -ne 0 ]; then
	echo "Error: appadmin update of '$1' returned with exit code '$RET'"
	exit $RET
    fi
fi

exit 0