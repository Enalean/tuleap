ARG OS
FROM $OS

RUN yum install -y make rpm-build


VOLUME ["/tuleap"]

WORKDIR /tuleap

CMD make -C /tuleap/plugins/taskboard docker-run
