FROM centos:7

RUN yum install -y rpm-build epel-release tar git fontconfig make && \
    yum install -y nodejs npm && \
    npm install -g bower phantomjs-prebuilt

WORKDIR /tuleap/plugins/trafficlights
VOLUME ["/tuleap/plugins/trafficlights"]
CMD make docker-run
