FROM centos:7

COPY rpm/tuleap.repo /etc/yum.repos.d/
COPY rpm/RPM-GPG-KEY-Tuleap /etc/pki/rpm-gpg/RPM-GPG-KEY-Tuleap

# initscripts is implicit dependency of openssh-server for ssh-keygen (/etc/rc.d/init.d/functions)

RUN /usr/sbin/groupadd -g 900 -r codendiadm && \
    /usr/sbin/groupadd -g 902 -r gitolite && \
    /usr/sbin/groupadd -g 903 -r dummy && \
    /usr/sbin/groupadd -g 904 -r ftpadmin && \
    /usr/sbin/groupmod -g 50  ftp && \
    /usr/sbin/useradd -u 900 -c 'Tuleap user' -m -d '/var/lib/tuleap' -r -g "codendiadm" -s '/bin/bash' -G ftpadmin,gitolite codendiadm && \
    /usr/sbin/useradd -u 902 -c 'Git' -m -d '/var/lib/gitolite' -r -g gitolite gitolite && \
    /usr/sbin/useradd -u 903 -c 'Dummy Tuleap User' -M -d '/var/lib/tuleap/dumps' -r -g dummy dummy && \
    /usr/sbin/useradd -u 904 -c 'FTP Administrator' -M -d '/var/lib/tuleap/ftp' -r -g ftpadmin ftpadmin && \
    /usr/sbin/usermod -u 14 -c 'FTP User' -d '/var/lib/tuleap/ftp' -g ftp ftp && \
    yum install -y epel-release centos-release-scl sudo https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
    yum update -y && \
    yum install -y \
        --exclude='tuleap-plugin-tracker-encryption,tuleap-plugin-proftpd,tuleap-plugin-referencealias-*,tuleap-plugin-artifactsfolders' \
    cronie \
    initscripts \
    openssh-server \
    postfix \
    rsyslog \
    supervisor \
    tuleap-plugin-* \
    tuleap-theme-burningparrot \
    tuleap-theme-flamingparrot \
    tuleap-realtime && \
    yum clean all && \
    localedef -i fr_FR -c -f UTF-8 fr_FR.UTF-8 && \
    awk '$5 >= 3071' /etc/ssh/moduli > /etc/ssh/moduli.tmp && mv /etc/ssh/moduli.tmp /etc/ssh/moduli

COPY docker/tuleap-community-edition/sshd_config /etc/ssh/sshd_config

FROM scratch

EXPOSE 22 80 443

ENV TLP_SYSTEMCTL=docker-centos7

COPY --from=0 / /

HEALTHCHECK --start-period=2m --timeout=5s CMD /usr/bin/tuleap healthcheck

ENTRYPOINT ["/usr/bin/tuleap-cfg", "docker:tuleap-run"]
