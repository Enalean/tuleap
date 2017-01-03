FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release \
    && yum install -y nodejs npm rubygem-sass

# This is used by bower to disable interactive mode
ENV CI true

# Disable cli progress animation in npm
# Install base node modules
RUN npm config set progress false \
    && npm install --global \
    bower \
    phantomjs-prebuilt

WORKDIR /tuleap/plugins/pullrequest
VOLUME ["/tulap/plugins/pullrequest"]
CMD make docker-run
