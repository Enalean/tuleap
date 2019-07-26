# Need glibc here (so not alpine) otherwise we face an iconv error with nette/utils dependency
# https://github.com/nette/utils/issues/109
FROM fedora:30

RUN dnf install -y \
    php \
    php-opcache \
    php-xml \
    php-mbstring \
    php-zip \
    subversion \
    composer \
    curl \
    git \
    python \
    bzip2 \
    && \
    dnf clean all \
