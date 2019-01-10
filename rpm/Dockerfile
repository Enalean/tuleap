ARG OS
FROM $OS

COPY remi.repo /etc/yum.repos.d/

RUN yum install -y epel-release && \
    yum install -y make rpm-build tar gettext php72-php-cli

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
