FROM centos:7

COPY remi.repo /etc/yum.repos.d/
COPY --from=composer:1.9 /usr/bin/composer /usr/bin/composer

RUN yum install -y make rpm-build tar git fontconfig epel-release ca-certificates \
    https://rpm.nodesource.com/pub_10.x/el/7/x86_64/nodesource-release-el7-1.noarch.rpm && \
    yum install -y nodejs php73-php-cli && \
    mkdir npm_install && \
    npm install --global npm@6.13.4 && \
    yum clean all

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
