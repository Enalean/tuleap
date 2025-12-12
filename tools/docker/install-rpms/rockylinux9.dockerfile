FROM rockylinux/rockylinux:9.7@sha256:53f4c6dcb34e1403bd93207351f0af9a593610faeb7165cb8a037346765199b0 AS tuleap-installrpms-base
# To test RHEL9:
#FROM registry.access.redhat.com/ubi9 AS tuleap-installrpms-base
# To test AlmaLinux
#FROM almalinux:9

ENV container=docker

STOPSIGNAL SIGRTMIN+3

RUN rm -f /lib/systemd/system/multi-user.target.wants/*;\
    rm -f /etc/systemd/system/*.wants/*;\
    rm -f /lib/systemd/system/local-fs.target.wants/*; \
    rm -f /lib/systemd/system/sockets.target.wants/*udev*; \
    rm -f /lib/systemd/system/sockets.target.wants/*initctl*; \
    rm -f /lib/systemd/system/basic.target.wants/*;\
    rm -f /lib/systemd/system/anaconda.target.wants/* && \
    dnf install -y --setopt install_weak_deps=false --nodocs \
        openssh-server \
        createrepo \
        mysql-server \
        rocky-release-security \
        epel-release \
        https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf install -y dnf-plugins-core && \
    dnf config-manager --enable security-common && \
    dnf clean all && \
    rm -rf /var/cache/yum

COPY tuleap-local.repo /etc/yum.repos.d/
COPY install.sh /install.sh
COPY run.sh /run.sh
COPY sql-mode.cnf /etc/my.cnf.d/sql-mode.cnf

VOLUME [ "/sys/fs/cgroup" ]
CMD ["/usr/sbin/init"]

FROM tuleap-installrpms-base AS ci
COPY install-and-run.ci.service /etc/systemd/system/install-and-run.service
RUN systemctl enable install-and-run.service && \
    echo "Storage=persistent" >> /etc/systemd/journald.conf

FROM tuleap-installrpms-base AS interactive
COPY install-and-run.service /etc/systemd/system/install-and-run.service
RUN systemctl enable install-and-run.service
