FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release \
    https://rpm.nodesource.com/pub_10.x/el/7/x86_64/nodesource-release-el7-1.noarch.rpm && \
    yum install -y nodejs

WORKDIR /plugin
CMD make docker-run
