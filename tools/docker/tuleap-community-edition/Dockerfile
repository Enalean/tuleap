FROM rockylinux/rockylinux:9.2@sha256:8cc691b7e360f484092c50411e45a2ec52eb70b1dea75acb284886284c21d5fe

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
    dnf install --setopt=skip_missing_names_on_install=False --setopt install_weak_deps=false --nodocs -y epel-release sudo https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf update -y && \
    dnf install -y --setopt=skip_missing_names_on_install=False --setopt install_weak_deps=false --nodocs \
        --exclude='tuleap-plugin-tracker-encryption' \
    cronie \
    glibc-locale-source \
    postfix \
    openssh-server \
    rsyslog \
    supervisor \
    tuleap-plugin-* \
    tuleap-theme-* && \
    dnf clean all && \
    localedef -i fr_FR -c -f UTF-8 fr_FR.UTF-8 && \
    localedef -i pt_BR -c -f UTF-8 pt_BR.UTF-8 && \
    awk '$5 >= 3071' /etc/ssh/moduli > /etc/ssh/moduli.tmp && mv /etc/ssh/moduli.tmp /etc/ssh/moduli

COPY docker/tuleap-community-edition/sshd_config /etc/ssh/sshd_config

FROM scratch

EXPOSE 22 80 443

ENV TLP_SYSTEMCTL=docker-centos7
ENV TLP_INSTALL_PLUGINS_TO_ENABLE='agiledashboard api_explorer cardwall docman embed frs git gitlab gitlfs graphontrackersv5 hudson_git kanban mediawiki_standalone onlyoffice openidconnectclient pullrequest tracker'

COPY --from=0 / /

HEALTHCHECK --start-period=5m --timeout=5s CMD /usr/bin/tuleap healthcheck

ENTRYPOINT ["/usr/bin/tuleap-cfg", "docker:tuleap-run"]
