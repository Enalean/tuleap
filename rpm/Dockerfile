FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
    yum install -y nodejs npm && \
    mkdir npm_install && pushd npm_install && \
    npm install npm@6.9.0 && \
    rpm -e --nodeps npm && \
    ./node_modules/.bin/npm install -g npm@6.9.0 && \
    popd && \
    rm -rf npm_install && \
    yum clean all

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run