ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/centos:7

COPY RPM-GPG-KEY-remi /etc/pki/rpm-gpg/
COPY *.repo /etc/yum.repos.d/

RUN yum update -y && \
    yum install -y epel-release centos-release-scl && \
    yum install -y nginx \
        php74-php-cli \
        php74-php-process && \
    yum clean all
