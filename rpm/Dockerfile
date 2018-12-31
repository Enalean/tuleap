FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
    yum install -y nodejs npm rubygem-sass

WORKDIR /tuleap/plugins/botmattermost
VOLUME ["/tuleap/plugins/botmattermost"]
CMD make docker-run
