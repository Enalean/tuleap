#!/bin/bash

set -e

usage() {
    cat <<EOF
Usage: $1  --lxc-name=<value> --lxc-ip=<value> --srcdir=<value> --install-mode=<value>
Options
  --install-mode=[update-snapshot|upgrade|clean-install] update-snapshot is assumed if the option is not given
  --lxc-name=<value>  Name of lxc container (eg. lxc-aci-105)
  --lxc-ip=<value>    IP address of lxc container (eg. 192.168.1.105)
  --srcdir=<value>    Source dir
  --repo-base-url=<value>   Base url (eg. http://degaine:8080/job/build_101_graphs_on_tracker_v5/ws/yum/)
EOF
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  perl -pi -e "s/$2/$replacement/g" $1
}

lxc_start_wait() {
    ip=$1

    # First check network
    maxwait=10
    until ping -q -W 2 -c 1 "$ip" 2>&1 >/dev/null; do
	if [ "$maxwait" -eq "0" ]; then
	    echo "*** Error: cannot reach $name ($ip) after 10 attempts";
	    exit 1
	fi
	sleep 5;
	maxwait=$(($maxwait-1))
    done

    # Then check ssh activation
    maxwait=10
    until $remotecmd true; do
	if [ "$maxwait" -eq "0" ]; then
	    echo "*** Error: cannot reach $name ($ip) after 10 attempts";
	    exit 1
	fi
	sleep 5;
	maxwait=$(($maxwait-1))
    done
}

##
## Parse options
##
install_mode="update-snapshot"
options=`getopt -o h -l help,srcdir:,lxc-name:,lxc-ip:,repo-base-url:,install-mode: -- "$@"`
if [ $? != 0 ] ; then echo "Terminating..." >&2 ; usage $0 ;exit 1 ; fi
eval set -- "$options"
while true
do
    case "$1" in
	-h|--help)
	    usage $0
	    exit 0;;
	--srcdir)
	    src_dir=$2; 
	    shift 2;;
	--lxc-name)
	    lxc_name=$2; 
	    shift 2;;
	--lxc-ip)
	    lxc_ip=$2; 
	    shift 2;;
	--repo-base-url)
	    repo_base_url=$2
	    shift 2;;
        --install-mode)
            install_mode=$2
            shift 2;;
	 *)
	    break;;
    esac
done


# TODO: What to do if the repo-base-url is not filled? (same for other ones)

# Go!

set -x

build_host=root@$lxc_ip
sshcmd="ssh -o StrictHostKeyChecking=no"
# -n to close standard input
remotecmd="$sshcmd -n $build_host"

if  ! lxc-ls | egrep -wq "$lxc_name" || [ $install_mode = "clean-install" ] ; then
    if lxc-ls | egrep -wq "$lxc_name" ; then
        sudo lxc-stop --name=$lxc_name
	sudo lxc-destroy --name=$lxc_name
    fi
    # Setup an lxc instance and install tuleap
    echo "Create a new container $lxc_name"
    cp $src_dir/tools/continuous_integration/lxc-centos5.cro.enalean.com.config lxc.config
    substitute "lxc.config" "%ip_addr%" "$lxc_ip"

    sudo lxc-create -n $lxc_name -f lxc.config -t centos5

    # Start the container
    sudo lxc-start -n $lxc_name -d
    lxc_start_wait $lxc_ip

    # Upload installation script into /root
    $remotecmd /bin/rm -fr /root/lxc-inst.sh
    rsync --delete --archive $src_dir/tools/continuous_integration/lxc-inst.sh $build_host:/root

    # Install
    $remotecmd /bin/sh -x /root/lxc-inst.sh $repo_base_url
elif [ $install_mode = "upgrade" ] ; then
    $remotecmd yum clean metadate
    $remotecmd yum install tuleap -y --disablerepo=epel php-pecl-json tuleap-all
    $remotecmd service httpd restart
else 
    # the server already exists and we suppose this is a reinstall
    # ==========>>> Warning : yum reinstall probably wont work very well with parts of tuleap that doesnt support yum remove but we havent run into problems yet <<<<<==========
    $remotecmd yum clean metadata
    $remotecmd yum reinstall tuleap -y --disablerepo=epel php-pecl-json tuleap-all
    $remotecmd service httpd restart
fi

# Make sure that selenium server is up
if lxc-ls | egrep -q "^lxc-selenium-server$"; then
    if sudo lxc-info -q --name lxc-selenium-server | grep -q "RUNNING"; then
        echo "Selenium server is running"
    else
        echo "Starting Selenium server"
        sudo lxc-start -n lxc-selenium-server -d
    fi
else
    echo "ERROR: There is no selenium server installed"
    exit 1
fi

# Get the mysql password from the install
mysql_pass=$($remotecmd grep sys_dbpasswd /etc/codendi/conf/database.inc | cut -d\" -f2)  

# Tests need to have fixture.sql on target server so upload it
rsync --delete --archive $src_dir/tests $build_host:/usr/share/codendi

# And test!
TULEAP_HOST=$lxc_ip TULEAP_ENV=aci TULEAP_MYSQL_PASS=$mysql_pass cucumber -f junit -o test_results $src_dir/tests/functional/features
