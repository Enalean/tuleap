FROM centos:7

RUN yum install -y make rpm-build tar gettext

VOLUME ["/tuleap", "/plugin"]

WORKDIR /plugin

CMD make docker-run
