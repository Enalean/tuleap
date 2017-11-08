FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release \
    && yum install -y nodejs npm

# This is used by bower to disable interactive mode
ENV CI true

# Disable cli progress animation in npm
RUN npm config set progress false

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run