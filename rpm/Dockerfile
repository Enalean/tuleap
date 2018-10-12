FROM centos:7

RUN yum install -y yum install https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
    yum install -y make rpm-build tar gettext php72-php-cli

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
