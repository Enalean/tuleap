#!/bin/bash

set -e
# Do not leak passwords
set +x

# Starts the DB and upgrade the data
start_mysql() {
    if [ -n "$DB_HOST" ]; then
        return;
    fi
    # old password must be disabled for php 5.6 / mysqlnd
    sed -i -e 's/^old_passwords\(.*\)/#old_passwords\1/' /etc/my.cnf
    codendiadm_pass=$(./interpolate_tuleap_var.php /etc/tuleap/conf/database.inc sys_dbpasswd)

    echo "Start mysql"
    /usr/bin/python /usr/lib/python2.6/site-packages/supervisor/pidproxy.py /var/run/mysqld/mysqld.pid /usr/bin/mysqld_safe &
    sleep 1
    wait_for_db localhost codendiadm $codendiadm_pass

    # Update password when switching from old_password
    if grep -q '#old_passwords' /etc/my.cnf; then
        mysql -ucodendiadm -p$codendiadm_pass -e "SET PASSWORD = PASSWORD('$codendiadm_pass')"
    fi
}

wait_for_db() {
    host=$1
    user=$2
    password=$3
    while ! mysql -h$host -u$user -p$password -e "show databases" >/dev/null; do
        echo "Wait for the db"
        sleep 1
    done
}

# Stop Mysql
stop_mysql() {
    if [ -n "$DB_HOST" ]; then
        return;
    fi
    echo "Stop mysql"
    PID=$(cat /var/run/mysqld/mysqld.pid)
    kill -15 $PID
    while ps -p $PID >/dev/null 2>&1; do
	echo "Waiting for mysql ($PID) to stop"
	sleep 1
    done
}
