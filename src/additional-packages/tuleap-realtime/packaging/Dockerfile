ARG OS
FROM $OS

RUN yum install -y epel-release && yum install -y make jq tar rpm-build

VOLUME /realtime
VOLUME /output

CMD make -C /realtime/packaging build
