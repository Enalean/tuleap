#! /bin/sh
#
# This file simulates Apache for debugging purposes.
# All it really does is greate the PID file.
#

CONFIGDIR=
CONFIGFILE=
PIDFILE=
LOGFILE=
SERVER=false

cleanup() {
    rm -f "$PIDFILE"
    exit 0
}

server() {
    echo "Running subprocess, my PID is $$"
    trap cleanup TERM
    umask 2
    echo "$$" > "$PIDFILE"
    while true
    do
    	date >> "$LOGFILE"
	sleep 10
    done
}

while getopts "d:f:s" CMD
do
    case $CMD in
    d) CONFIGDIR="$OPTARG";;
    f) CONFIGFILE="$OPTARG";;
    s) SERVER=true;;
    *) exit 1
    esac
done

PIDFILE="$CONFIGDIR/../../var/run/httpd.pid"
LOGFILE="$CONFIGDIR/../../var/run/httpd.log"

if $SERVER
then
    server
    exit 0
fi

cat <<EOF
dummy_http
    CONFIGDIR = $CONFIGDIR
    CONFIGFILE = $CONFIGFILE
    PIDFILE = $PIDFILE
EOF

echo "About to run subprocess, my PID is $$"

$0 -s $@ &

exit 0
