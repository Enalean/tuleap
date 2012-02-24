#!/bin/sh


ip_address=`LC_ALL=C ifconfig  | grep 'inet addr:'| grep -v '127.0.0.1' | cut -d: -f2 | awk '{ print $1}'`
repo_base_url=$1

# Take the local centos mirror
perl -pi -e 's%baseurl=http://mirror.bytemark.co.uk/centos%baseurl=ftp://degaine.cro.enalean.com/ftp.centos.org%' /etc/yum.repos.d/CentOS-Base.repo

# install rpms
# Configure the Tuleap repositories
# - Tuleap: the main repo for dependencies
# - Tuleap-dev: the fresh one
cat <<'EOF' >/etc/yum.repos.d/Tuleap.repo
[Tuleap]
name=Tuleap
baseurl=ftp://ci.tuleap.net/yum/tuleap/dev/$basearch
enabled=1
gpgcheck=0
exclude=tuleap*

[Tuleap-dev]
name=Tuleap-dev
EOF

echo "baseurl=$repo_base_url" >> /etc/yum.repos.d/Tuleap.repo

cat <<'EOF' >>/etc/yum.repos.d/Tuleap.repo
enabled=1
gpgcheck=0
EOF

# we must clean yum metadata, otherwise reinstall of a "snapshot" version isn't possible
yum clean metadata

# local centos mirror may produce ftp io errors
# => we need to retry install
maxretry=10
until [ -d "/usr/share/tuleap-install" ]; do
    if [ "$maxretry" -eq "0" ]; then
        echo "*** Error: cannot install Tuleap after 10 attempts";
        exit 1
    fi
    yum install -y --disablerepo=epel postfix php-pecl-json php-zendframework tuleap-all
    maxretry=$(($maxretry-1))
done

# install Tuleap
# TODO: redirect output and errors in a file for debugging purpose
bash -x /usr/share/tuleap-install/setup.sh --auto-passwd --without-bind-config --disable-subdomains --sys-default-domain=$ip_address --sys-fullname=$ip_address --sys-ip-address=$ip_address --sys-org-name=Tuleap --sys-long-org-name=Tuleap

# activate tuleap licence
touch /etc/codendi/CODENDI_LICENSE_ACCEPTED


