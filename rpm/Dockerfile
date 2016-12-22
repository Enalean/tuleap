FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
	yum install -y nodejs npm rubygem-sass && \
	npm install -g grunt-cli bower

WORKDIR /tuleap/plugins/botmattermost_git
VOLUME ["/tuleap/plugins/botmattermost_git"]
CMD make docker-run
