# Install Docker Engine

We will install `docker`.

Minimal versions: docker 1.17

You can ensure docker is working properly by using
`$ docker run hello-world`.

## Install Docker Engine

The recommend platform for Tuleap development is Fedora. It's the base
image used by most of the Tuleap developers, you are more likely to get
help with this base.

### Installing Docker on Fedora

**Fedora 36+**

We recommend you run fedora's version of docker (instead of Docker CE).

However, there will be some configuration needed to run everything:

First, you need to raise the default ulimits:

``` bash
$> sudo vim /etc/sysconfig/docker
    ...
    --default-ulimit nofile=2048:2048 \
    ...
$> sudo systemctl edit docker.service
[Service]
LimitNOFILE=2048
```

Then, SELinux must be disabled:

``` bash
$> sudo setenforce disabled
$> vim /etc/selinux/config
SELINUX=permissive
```

Finally, restart everything:

``` bash
$> sudo systemctl daemon-reload
$> sudo systemctl restart docker.service
```

### Installing Docker on Ubuntu

Follow the official Docker documentation: [Installation on
Ubuntu](https://docs.docker.com/engine/install/ubuntu/).

### Installing Docker with Docker Desktop

Go to https://www.docker.com/products/docker-desktop/ and download Docker for linux.

### Installing Docker on macOS

Go to <https://docs.docker.com/desktop/install/mac-install/> and
download Docker for Mac. It will install all you need to run Tuleap
containers.

## Next

[You are ready to run Tuleap](./run-tuleap.md)
