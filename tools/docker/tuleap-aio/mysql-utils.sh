#!/bin/bash

set -e
# Do not leak passwords
set +x

# Starts the DB and upgrade the data
start_mysql() {
    # old password must be disabled for php 5.6 / mysqlnd
    sed -i -e 's/^old_passwords\(.*\)/#old_passwords\1/' /etc/my.cnf
    codendiadm_pass=$(./interpolate_tuleap_var.php /etc/tuleap/conf/database.inc sys_dbpasswd)

    echo "Start mysql"
    /usr/bin/python /usr/lib/python2.6/site-packages/supervisor/pidproxy.py /var/run/mysqld/mysqld.pid /usr/bin/mysqld_safe &
    sleep 1
    while ! mysql -ucodendiadm -p$codendiadm_pass -e "show databases" >/dev/null; do
	echo "Wait for the db"
	sleep 1
    done

    # Update password when switching from old_password
    if grep -q '#old_passwords' /etc/my.cnf; then
        mysql -ucodendiadm -p$codendiadm_pass -e "SET PASSWORD = PASSWORD('$codendiadm_pass')"
    fi
}

# Stop Mysql
stop_mysql() {
    echo "Stop mysql"
    PID=$(cat /var/run/mysqld/mysqld.pid)
    kill -15 $PID
    while ps -p $PID >/dev/null 2>&1; do
	echo "Waiting for mysql ($PID) to stop"
	sleep 1
    done
}
