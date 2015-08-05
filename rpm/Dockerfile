FROM centos:6

RUN yum install -y scl-utils rpm-build centos-release-SCL tar git fontconfig && \
    yum install -y nodejs010-nodejs nodejs010-npm && \
    scl enable nodejs010 "npm install -g grunt-cli bower less recess bless"

WORKDIR /app
VOLUME ["/app"]
CMD make docker-run
