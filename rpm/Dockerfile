FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release \
    && yum install -y nodejs npm \
    && npm install -g grunt-cli bower less recess bless

WORKDIR /app
VOLUME ["/app"]
CMD make docker-run
