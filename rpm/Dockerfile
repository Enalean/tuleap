FROM centos:7

RUN yum install -y rpm-build epel-release tar git fontconfig make && \
    yum install -y nodejs npm rubygem-sass && \
    npm install -g grunt-cli bower

WORKDIR /tuleap/plugins/trafficlights
VOLUME ["/tuleap/plugins/trafficlights"]
CMD make docker-run
