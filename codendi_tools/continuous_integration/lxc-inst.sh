#!/bin/sh

ip_address=$1

perl -pi -e 's%baseurl=http://mirror.bytemark.co.uk%baseurl=ftp://degaine/mirror.centos.org%' /etc/yum.repos.d/CentOS-Base.repo
yum install -y --disablerepo=epel tuleap-all
/usr/share/tuleap-install/setup.sh --auto-passwd --without-bind-config --disable-subdomains --sys-default-domain=$ip_address --sys-fullname=$ip_address --sys-ip-address=$ip_address --sys-org-name=Tuleap --sys-long-org-name=Tuleap