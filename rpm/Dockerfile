FROM centos:7

RUN yum install -y rpm-build epel-release tar git fontconfig make && \
    rpm -ivh https://kojipkgs.fedoraproject.org//packages/http-parser/2.7.1/3.el7/x86_64/http-parser-2.7.1-3.el7.x86_64.rpm && \
    yum install -y nodejs npm && \
    npm install -g bower phantomjs-prebuilt

WORKDIR /trafficlights
CMD make docker-run
