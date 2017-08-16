FROM centos:7

RUN yum install -y make rpm-build tar git fontconfig epel-release && \
    rpm -ivh https://kojipkgs.fedoraproject.org//packages/http-parser/2.7.1/3.el7/x86_64/http-parser-2.7.1-3.el7.x86_64.rpm && \
	yum install -y nodejs npm rubygem-sass && \
	npm install -g grunt-cli bower

WORKDIR /tuleap/plugins/botmattermost
VOLUME ["/tuleap/plugins/botmattermost"]
CMD make docker-run
