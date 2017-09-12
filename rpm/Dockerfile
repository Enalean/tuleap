FROM centos:7

RUN yum install -y make rpm-build tar git epel-release \
    && yum install -y nodejs rubygem-sass && gem install scss_lint

# This is used by bower to disable interactive mode
ENV CI true

# Disable cli progress animation in npm
# Install base node modules
RUN npm config set progress false \
    && npm install --global bower

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
