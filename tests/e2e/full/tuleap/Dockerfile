FROM centos:6

COPY Tuleap.repo /etc/yum.repos.d/

RUN yum -y install epel-release centos-release-scl && \
    yum -y install \
        tuleap \
        tuleap-plugin-svn \
        tuleap-plugin-agiledashboard \
        tuleap-plugin-git-gitolite3 \
        rh-mysql56-mysql \
        rh-mysql56-mysql-server \
    && \
    yum remove -y tuleap-core-\* \
        tuleap-plugin-\*  \
        tuleap-documentation && \
    yum clean all
