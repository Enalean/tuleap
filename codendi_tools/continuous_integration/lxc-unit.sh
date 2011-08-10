#!/bin/sh

src_dir=tuleap
lxc_name=lxc-aci-103
build_host=root@192.168.1.103
remotecmd="ssh -o StrictHostKeyChecking=no $lxc-aci-103"

sudo lxc-create -n $lxc_name -f config.centos5 -t centos5
sudo lxc-start -n $lxc_name -d

rsync $src_dir $build_host:~/
$remotecmd make -C /root/$src_dir/rpm/SPECS rpmprep jpgraph.codendi htmlpurifier
$remotecmd yum install -y --nogpgcheck /root/$src_dir/rpm/RPMS/noarch/*.rpm
$remotecmd /root/$src_dir/codendi_tools/ci_build.sh

scp $build_host:/root/$src_dir/plugins/tests/www/codendi_unit_tests_report.xml .
scp $build_host:/root/var/tmp/checkstyle.xml .
