ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/enalean/tuleap-aio:centos7

RUN yum install -y php-mediawiki-tuleap-123
