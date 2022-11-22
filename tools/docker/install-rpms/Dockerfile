FROM rockylinux:9

ENV container docker

STOPSIGNAL SIGRTMIN+3

COPY install-and-run.service /etc/systemd/system

RUN (cd /lib/systemd/system/sysinit.target.wants/; for i in *; do [ $i == \
    systemd-tmpfiles-setup.service ] || rm -f $i; done); \
    rm -f /lib/systemd/system/multi-user.target.wants/*;\
    rm -f /etc/systemd/system/*.wants/*;\
    rm -f /lib/systemd/system/local-fs.target.wants/*; \
    rm -f /lib/systemd/system/sockets.target.wants/*udev*; \
    rm -f /lib/systemd/system/sockets.target.wants/*initctl*; \
    rm -f /lib/systemd/system/basic.target.wants/*;\
    rm -f /lib/systemd/system/anaconda.target.wants/* && \
    yum install -y \
        openssh-server \
        createrepo \
        mysql-server \
        https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm \
        https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    yum clean all && \
    rm -rf /var/cache/yum && \
    systemctl enable install-and-run.service

COPY tuleap-local.repo /etc/yum.repos.d/
COPY install.sh /install.sh
COPY run.sh /run.sh
COPY sql-mode.cnf /etc/my.cnf.d/sql-mode.cnf

VOLUME [ "/sys/fs/cgroup" ]
CMD ["/usr/sbin/init"]
