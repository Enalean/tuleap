#!/bin/sh

set -ex

src_dir=tuleap
lxc_name=lxc-aci-103
build_host=root@192.168.1.103
sshcmd="ssh -o StrictHostKeyChecking=no"
remotecmd="$sshcmd $build_host"

if lxc-ls | egrep -q "^$lxc_name$"; then
    echo "LXC container already exists"
else
    sudo lxc-create -n $lxc_name -f $src_dir/codendi_tools/continuous_integration/config.centos5 -t centos5
fi

if sudo lxc-info -q --name $lxc_name | grep -q "RUNNING"; then
    echo "LXC container is already running"
else
    sudo lxc-start -n $lxc_name -d
    sleep 5
fi

rsync --rsh="$sshcmd" --archive $src_dir $build_host:/root
$remotecmd chown root:root -R /root/$src_dir
$remotecmd make -C /root/$src_dir/rpm/SPECS rpmprep jpgraph.codendi htmlpurifier
$remotecmd rpmbuild --rebuild /root/$src_dir/rpm/SRPMS/*.src.rpm
$remotecmd yum install -y --nogpgcheck /root/$src_dir/rpm/RPMS/noarch/*.rpm
$remotecmd WORKSPACE=/root /root/$src_dir/codendi_tools/continuous_integration/ci_build.sh

scp $build_host:/root/$src_dir/plugins/tests/www/codendi_unit_tests_report.xml .
scp $build_host:/root/var/tmp/checkstyle.xml .
