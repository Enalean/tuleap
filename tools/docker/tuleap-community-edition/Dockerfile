FROM rockylinux/rockylinux:9.6@sha256:7620071ad66949c3aef9fd313151f0cfee46b75485990c3b03710624f5e1b7b3

COPY rpm/tuleap.repo /etc/yum.repos.d/
COPY rpm/RPM-GPG-KEY-Tuleap /etc/pki/rpm-gpg/RPM-GPG-KEY-Tuleap

RUN /usr/bin/ln -s /var/lib/tuleap /var/lib/codendi

EXPOSE 22 80 443

ENV TLP_SYSTEMCTL=docker
ENV TLP_INSTALL_PLUGINS_TO_ENABLE='agiledashboard api_explorer cardwall docman embed frs git gitlab gitlfs graphontrackersv5 hudson_git kanban mediawiki_standalone onlyoffice openidconnectclient pullrequest tracker'

HEALTHCHECK --start-period=5m --timeout=5s CMD /usr/bin/tuleap healthcheck

RUN /usr/sbin/groupadd -g 900 -r codendiadm && \
    /usr/sbin/groupadd -g 902 -r gitolite && \
    /usr/sbin/groupadd -g 904 -r ftpadmin && \
    /usr/sbin/groupmod -g 50  ftp && \
    /usr/sbin/useradd -u 900 -c 'Tuleap user' -m -d '/var/lib/tuleap' -r -g "codendiadm" -s '/bin/bash' -G ftpadmin,gitolite codendiadm && \
    /usr/sbin/useradd -u 902 -c 'Git' -m -d '/var/lib/gitolite' -r -g gitolite gitolite && \
    /usr/sbin/useradd -u 904 -c 'FTP Administrator' -M -d '/var/lib/tuleap/ftp' -r -g ftpadmin ftpadmin && \
    /usr/sbin/usermod -u 14 -c 'FTP User' -d '/var/lib/tuleap/ftp' -g ftp ftp && \
    dnf install --setopt=skip_missing_names_on_install=False --setopt install_weak_deps=false --nodocs -y epel-release rocky-release-security sudo https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf install -y dnf-plugins-core && \
    dnf config-manager --enable security-common && \
    dnf remove -y dnf-plugins-core && \
    dnf update -y && \
    dnf install -y --setopt=skip_missing_names_on_install=False --setopt install_weak_deps=false --nodocs \
        --exclude='tuleap-plugin-tracker-encryption' \
    cronie \
    postfix \
    openssh-server \
    supervisor \
    tuleap-plugin-* \
    tuleap-theme-* && \
    dnf clean all && \
    awk '$5 >= 3071' /etc/ssh/moduli > /etc/ssh/moduli.tmp && mv /etc/ssh/moduli.tmp /etc/ssh/moduli

COPY docker/tuleap-community-edition/sshd_config /etc/ssh/sshd_config

ENTRYPOINT ["/usr/bin/tuleap-cfg", "docker:tuleap-run"]
