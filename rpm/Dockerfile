FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
    yum install -y nodejs npm rubygem-sass

WORKDIR /tuleap/plugins/botmattermost_agiledashboard
VOLUME ["/tuleap/plugins/botmattermost_agiledashboard"]
CMD make docker-run
