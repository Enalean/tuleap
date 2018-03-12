FROM centos:centos6

COPY Tuleap.repo /etc/yum.repos.d/

RUN yum upgrade -y && \
    yum install -y epel-release && \
    yum install -y nodejs npm tuleap-realtime && \
    yum clean all

VOLUME ["/etc/tuleap-realtime", "/published-certificate"]
EXPOSE 443
