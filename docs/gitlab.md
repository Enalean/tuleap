# GitLab for Tuleap development

This section covers how to set up a local GitLab next to your Tuleap
development platform in order to develop or debug.

## Setup GitLab

To download source and start GitLab instance, you can run:

``` bash
you@workstation $> make start-gitlab
```

## First starting

### /etc/hosts

After run `make start-gitlab`, you need to edit (as root) the
`/etc/hosts` file with GitLab IP: `172.17.0.4    gitlab.local`.

You can know the IP address of GitLab container with `make show-ips`.

### Trust GitLab instance

If you want your Tuleap can communicate with GitLab, you need to insert
in Tuleap container GitLab certs. You can use `docker cp` to copy files
from container to computer, and from computer to container.

``` bash
you@workstation $> docker cp tuleap-gitlab-1:/etc/gitlab/ssl/gitlab.local.crt /tmp
you@workstation $> docker cp tuleap-gitlab-1:/etc/gitlab/ssl/gitlab.local.key /tmp
you@workstation $> docker cp /tmp/gitlab.local.key tuleap-web-1:/etc/pki/ca-trust/source/anchors/
you@workstation $> docker cp /tmp/gitlab.local.crt tuleap-web-1:/etc/pki/ca-trust/source/anchors/
```

When your certs are copied in Tuleap container, you need to trust them
([Add a new certification authority to the CA bundle](https://docs.tuleap.org/administration-guide/system-administration/certification-authority.html)).

### Create a GitLab admin account

Once your GitLab instance is deployed, you can go to `https://gitlab.local`.
The administrator account username is `root`. The password can be found in the GitLab container at `/etc/gitlab/initial_root_password`.
You can log in with this account and manage your GitLab.

### Trust Tuleap IP

You need to allow Tuleap to access to GitLab.

As root account, you need to go to
`https://gitlab.local/admin/application_settings/network#js-outbound-settings`.

-   Check `Allow requests to the local network from system hooks`
-   Enter `tuleap-web.tuleap-aio-dev.docker` in
    `Local IP addresses and domain names that hooks and services may access.`
-   Uncheck `Enforce DNS rebinding attack protection`

## After Gitlab Webhook created

Once a GitLab repository is integrated in Tuleap (see
[Register GitLab repository](https://docs.tuleap.org/user-guide/code-versioning/gitlab.html#gitlab-repository-registration)), you must edit the new webhook's settings and disable
`SSL verification`. You can access to your webhooks with
`https://gitlab.local/${namespace}/${project_name}/hooks`.

## After each restart

GitLab instance doesn't know Tuleap IP. You need to edit (as root) the
`/etc/hosts` file of GitLab container:

``` bash
you@workstation $> docker exec -it tuleap-gitlab-1 /bin/bash
you@workstation $> vi /etc/hosts
```

Add IP of your Tuleap:
`172.17.0.3       tuleap-web.tuleap-aio-dev.docker`. You can know the IP
address of web container with `make show-ips`.
