# Gerrit for Tuleap development

This section covers how to setup a local gerrit next to your Tuleap
development platform in order to develop or debug.

This guide might lag a bit behind the current developments. You can
find an up to date [readme in the sources](../tools/utils/gerrit_setup/Readme.md).

## Create a gerrit admin account

On Tuleap:

-   As site admin create account for gerrit: gerrit-admin-28 with email
    address (eg. <gerrit-admin-28@example.com>)

## Setup gerrit admin account

``` bash
you@workstation $> make start-gerrit
```

-   Go on gerrit web interface
    `http://tuleap-gerrit.gerrit-tuleap.docker:8080`
-   Sign-in with gerrit-admin
-   Generate an HTTP password (Settings > HTTP password)

Be careful, generate a password with only with alphanum \[A-z0-9\]+


## Setup gerrit

``` bash
you@workstation $> docker exec -ti tuleap-web bash
root@tuleap-web $> su - codendiadm
codendiadm@tuleap-web $> cd /usr/share/tuleap/tools/utils/gerrit_setup
codendiadm@tuleap-web $> ./setup_gerrit.sh --password=<generated password in gerrit interface> --useremail=<gerrit-admin@example.com>
# password is the HTTP password you generated on gerrit
# you might need to install php-guzzle: yum -y install php-guzzle
```

## Create gerrit reference on Tuleap

Go on Tuleap as site admin: Admin \> Git \> Gerrit

And create a new server with the SSH key you get with:

-   Host: tuleap-gerrit.gerrit-tuleap.docker
-   HTTP port: 8080
-   ssh port: 29418
-   login: gerrit-admin
-   Identity file: /home/codendiadm/.ssh/id_rsa-gerrit
-   Replication ssh key <you@workstation> \$\> docker run -ti \--rm
    \--volumes-from tuleap_gerrit_data busybox cat
    /home/gerrit/.ssh/id_rsa.pub
-   Use ssl: no
-   Version: 2.8+
-   HTTP password: the one generated in interface at step 2

## Initialize replication

``` bash
you@workstation $> make start-gerrit
```

## Replication

In your gerrit container :

-   <gerrit@tuleap-gerrit> => ssh <gitolite@tuleap-web> (to add in known
    hosts)
-   edit replication.conf to add the remote tuleap-web
-   restart gerrit

Be careful, a repository that does not grant replication ugroup as
reader for refs/\* will not be able to replicate.
