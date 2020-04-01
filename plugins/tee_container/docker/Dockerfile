FROM centos:7

# DOCKERFILE_BASEDIR_PATH is the directory where the Dockerfiles is
ARG DOCKERFILE_BASEDIR_PATH
# YUM_REPO_PATH is the path toward the .repo yum definition
ARG YUM_REPO_PATH
# PACKAGES_PATH is the path toward the .rpm packages
ARG PACKAGES_PATH
# RELEASE_CYCLE is the Tuleap release cycle (eg. 11.12)
ARG RELEASE_CYCLE=dev

COPY $YUM_REPO_PATH /etc/yum.repos.d/
COPY $PACKAGES_PATH /rpms

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
    echo "$RELEASE_CYCLE" > /etc/yum/vars/tuleap_release_cycle && \
    yum install -y epel-release centos-release-scl sudo https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
    yum update -y && \
    yum install -y \
    cronie \
    initscripts \
    openssh-server \
    postfix \
    rsyslog \
    supervisor \
    php73-php-pecl-redis \
    tuleap-documentation \
    tuleap-plugin-agiledashboard \
    tuleap-plugin-api-explorer \
    tuleap-plugin-archivedeleteditems \
    tuleap-plugin-baseline \
    tuleap-plugin-botmattermost \
    tuleap-plugin-botmattermost-agiledashboard \
    tuleap-plugin-botmattermost-git \
    tuleap-plugin-captcha \
    tuleap-plugin-cardwall \
    tuleap-plugin-crosstracker \
    tuleap-plugin-docman \
    tuleap-plugin-document \
    tuleap-plugin-dynamic-credentials \
    tuleap-plugin-frs \
    tuleap-plugin-git \
    tuleap-plugin-gitlfs \
    tuleap-plugin-graphontrackers \
    tuleap-plugin-hudson \
    tuleap-plugin-hudson-git \
    tuleap-plugin-hudson-svn \
    tuleap-plugin-label \
    tuleap-plugin-ldap \
    tuleap-plugin-mediawiki \
    tuleap-plugin-oauth2-server \
    tuleap-plugin-openidconnectclient \
    tuleap-plugin-projectmilestones \
    tuleap-plugin-project-ownership \
    tuleap-plugin-prometheus-metrics \
    tuleap-plugin-pullrequest \
    tuleap-plugin-svn \
    tuleap-plugin-taskboard \
    tuleap-plugin-tee-container \
    tuleap-plugin-testmanagement \
    tuleap-plugin-textualreport \
    tuleap-plugin-timetracking \
    tuleap-plugin-velocity \
    tuleap-theme-burningparrot \
    tuleap-theme-flamingparrot && \
    yum clean all && \
    /bin/rm -rf /rpms /etc/yum.repos.d/Tuleap.repo && \
    awk '$5 >= 3071' /etc/ssh/moduli > /etc/ssh/moduli.tmp && mv /etc/ssh/moduli.tmp /etc/ssh/moduli

COPY $DOCKERFILE_BASEDIR_PATH/sshd_config /etc/ssh/sshd_config

FROM scratch

EXPOSE 22 80 443

ENV TLP_SYSTEMCTL=docker-centos7

COPY --from=0 / /

CMD [ "/usr/share/tuleap/plugins/tee_container/bin/tee-container.php", "run" ]
