FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
    yum install -y nodejs npm

WORKDIR /plugin
CMD make docker-run