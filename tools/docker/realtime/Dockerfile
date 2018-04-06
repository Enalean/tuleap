FROM centos:6

COPY Tuleap.repo /etc/yum.repos.d/

RUN yum install -y epel-release && \
    yum install -y tuleap-realtime && \
    yum clean all

VOLUME ["/etc/tuleap-realtime", "/published-certificate"]
EXPOSE 443
