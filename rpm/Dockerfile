FROM centos:7

COPY remi.repo /etc/yum.repos.d/
COPY --from=composer:1.9 /usr/bin/composer /usr/bin/composer

RUN yum install -y make rpm-build tar git fontconfig epel-release ca-certificates && \
    yum install -y nodejs npm php73-php-cli && \
    mkdir npm_install && pushd npm_install && \
    npm install npm@6.11.3 && \
    rpm -e --nodeps npm && \
    ./node_modules/.bin/npm install -g npm@6.11.3 && \
    popd && \
    rm -rf npm_install && \
    yum clean all

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
