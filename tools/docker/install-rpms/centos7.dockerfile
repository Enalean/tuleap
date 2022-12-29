FROM centos:7 AS tuleap-installrpms-base

ENV container docker

STOPSIGNAL SIGRTMIN+3

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
        epel-release \
        centos-release-scl \
        https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
    yum install -y rh-mysql80-mysql-server && \
    yum clean all && \
    rm -rf /var/cache/yum

COPY tuleap-local.repo /etc/yum.repos.d/
COPY install.el7.sh /install.sh
COPY run.sh /run.sh
COPY sql-mode.cnf /etc/opt/rh/rh-mysql80/my.cnf.d/tuleap.cnf

VOLUME [ "/sys/fs/cgroup" ]
CMD ["/usr/sbin/init"]

FROM tuleap-installrpms-base AS ci
COPY install-and-run.ci.service /etc/systemd/system/install-and-run.service
RUN systemctl enable install-and-run.service && \
    echo "Storage=persistent" >> /etc/systemd/journald.conf

FROM tuleap-installrpms-base AS interactive
COPY install-and-run.service /etc/systemd/system/install-and-run.service
RUN systemctl enable install-and-run.service
