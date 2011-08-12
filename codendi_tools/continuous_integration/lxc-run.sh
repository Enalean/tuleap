#!/bin/sh

set -ex

src_dir=tuleap
sshcmd="ssh -o StrictHostKeyChecking=no"

# This is the machine where the packages are built and distributed
build_lxc_name=lxc-aci-103
build_ip=192.168.1.103
build_host=root@$build_ip
build_remotecmd="$sshcmd $build_host"

# This is the machine which host an instance of tuleap
run_lxc_name=lxc-aci-104
run_ip=192.168.1.104
run_host=root@$run_ip
run_remotecmd="$sshcmd $run_host"

build() {
    # Create the container if it doesn't exist
    if lxc-ls | egrep -q "^$build_lxc_name$"; then
        echo "$build_lxc_name container already exists"
    else
        echo "Creating $build_lxc_name"
        perl -pi -e "s/lxc.network.ipv4\s*=\s*\d.*$/lxc.network.ipv4 = $build_ip\/24/" $src_dir/codendi_tools/continuous_integration/config.centos5
        sudo lxc-create -n $build_lxc_name -f $src_dir/codendi_tools/continuous_integration/config.centos5 -t centos5
    fi
    
    # Start the container if needed
    if sudo lxc-info -q --name $build_lxc_name | grep -q "RUNNING"; then
        echo "$build_lxc_name container is already running"
    else
        echo "Starting $build_lxc_name"
        sudo lxc-start -n $build_lxc_name -d && sleep 5
    fi
    # Upload tuleap src into /root
    rsync --rsh="$sshcmd" --archive $src_dir $build_host:/root
    $build_remotecmd chown root:root -R /root/$src_dir
    
    # Upload and install missing dependencies
    # TODO move that into the lxc template?
    rsync --rsh="$sshcmd" --archive /var/lib/jenkins/docbook $build_host:/root
    $build_remotecmd yum install -y zip
    
    # Build needed rpm to run UnitTests
    $build_remotecmd "export DOCBOOK_TOOLS_DIR=/root/docbook && make -C /root/$src_dir/codendi_tools/rpm all dist PKG_NAME=tuleap"
    
    # Publish yum repository through HTTP
    $build_remotecmd yum install -y nginx
    $build_remotecmd "[ -d /usr/share/nginx/html/yum ] && rm -rf /usr/share/nginx/html/yum"
    $build_remotecmd cp -a /root/rpmbuild/yum /usr/share/nginx/html/
    $build_remotecmd "[ -f /var/run/nginx.pid ] || nginx"
}


run() {
    # Stop the container if it is running
    if sudo lxc-info -q --name $run_lxc_name | grep -q "RUNNING"; then
        echo "Stopping previously started $run_lxc_name container"
        sudo lxc-stop -n $run_lxc_name 
    fi
    
    # Destroy the container if it exists
    if lxc-ls | egrep -q "^$run_lxc_name$"; then
        echo "Destroying exisiting $run_lxc_name container"
        sudo lxc-destroy -n $run_lxc_name
    fi
    
    # Create the container
    echo "Creating $run_lxc_name"
    perl -pi -e "s/lxc.network.ipv4\s*=\s*\d.*$/lxc.network.ipv4 = $run_ip\/24/" $src_dir/codendi_tools/continuous_integration/config.centos5
    sudo lxc-create -n $run_lxc_name -f $src_dir/codendi_tools/continuous_integration/config.centos5 -t centos5
    
    # Start the container if needed
    echo "Starting $run_lxc_name"
    sudo lxc-start -n $run_lxc_name -d && sleep 5
    
    # Configure the yum repository
    $run_remotecmd "echo '[Tuleap]' > /etc/yum.repos.d/Tuleap.repo"
    $run_remotecmd "echo 'name=Tuleap' >> /etc/yum.repos.d/Tuleap.repo"
    $run_remotecmd "echo 'baseurl=http://$build_ip/yum/' >> /etc/yum.repos.d/Tuleap.repo"
    $run_remotecmd "echo 'enabled=1' >> /etc/yum.repos.d/Tuleap.repo"
    $run_remotecmd "echo 'gpgcheck=0' >> /etc/yum.repos.d/Tuleap.repo"
    
    $run_remotecmd yum remove cadaver
    $run_remotecmd yum install -y tuleap-all-deps tuleap tuleap-theme-tuleap tuleap-core-subversion
}


case $1 in
    "build")
        build
        ;;
    "run")
        run
        ;;
    *)
	build && run
esac
