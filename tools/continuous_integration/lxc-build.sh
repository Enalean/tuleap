#!/bin/sh

set -e

usage() {
    cat <<EOF
Usage: $1  --lxc-name=<value> --lxc-ip=<value> --srcdir=<value>
Options
  --lxc-name=<value>  Name of lxc container (eg. lxc-aci-105)
  --lxc-ip=<value>    IP address of lxc container (eg. 192.168.1.105)
  --srcdir=<value>    Source dir
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
options=`getopt -o h -l help,srcdir:,lxc-name:,lxc-ip: -- "$@"`
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
	 *)
	    break;;
    esac
done

# Go!

set -x

build_host=root@$lxc_ip
sshcmd="ssh -o StrictHostKeyChecking=no"
# -n to close standard input
remotecmd="$sshcmd -n $build_host"


# Create the container if it doesn't exist
if lxc-ls | egrep -q "^$lxc_name$"; then
    echo "LXC container already exists"
else
    cp $src_dir/tools/continuous_integration/lxc-centos5.cro.enalean.com.config lxc.config
    substitute "lxc.config" "%ip_addr%" "$lxc_ip"
    sudo lxc-create -n $lxc_name -f lxc.config -t centos5
fi

# Start the container if needed
if sudo lxc-info -q --name $lxc_name | grep -q "RUNNING"; then
    echo "LXC container is already running"
else
    sudo lxc-start -n $lxc_name -d
    lxc_start_wait $lxc_ip
fi

# Install needed rpms
$remotecmd yum install -y --nogpgcheck zip

# Upload tuleap src into /root
rsync --archive $src_dir $build_host:/root
$remotecmd chown root:root -R /root/$src_dir

# Upload docbook utilities
rsync -az --delete /var/lib/jenkins/docbook $build_host:/root

# Build rpms
time $remotecmd DOCBOOK_TOOLS_DIR=/root/docbook make -C /root/tuleap/tools/rpm all dist PKG_NAME=tuleap

# Clean up the local dir
rm -rf yum

# Publish the generated rpms in the workspace
rsync -a $build_host:/root/rpmbuild/yum .

