FROM centos:7

RUN yum install -y make rpm-build


VOLUME ["/tuleap"]

WORKDIR /tuleap

CMD make -C /tuleap/plugins/dynamic_credentials docker-run
