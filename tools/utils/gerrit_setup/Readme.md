Start gerrit
------------

    you@workstation $> make start-gerrit

Create a gerrit admin account
-----------------------------

On Tuleap, as site admin create account for gerrit: `gerrit-admin` with email address (eg. `gerrit-admin@example.com`)

In the next command, `gerrit-admin` password will be referenced as %PASSWORD%

Initialize gerrit from Tuleap
-----------------------------

    you@workstation $> make bash-web
    root@tuleap-web $> /usr/share/tuleap/tools/utils/tuleap-dev.php register-ip gerrit
    root@tuleap-web $> su - codendiadm
    codendiadm@tuleap-web $> /usr/share/tuleap/tools/utils/tuleap-dev.php gerrit-setup --gerrit-admin-password %PASSWORD%

Create gerrit reference on Tuleap
---------------------------------

Go on Tuleap as site admin: Admin > Git > Gerrit

And create a new server with the SSH key you get with:

* Host: gerrit.tuleap-aio-dev.docker
* HTTP port: 8080
* Use ssl: no
* ssh port: 29418
* login: gerrit-admin
* password: %PASSWORD%
* Identity file: /home/codendiadm/.ssh/id_rsa-gerrit
* Replication ssh key:
  you@workstation $> make show-gerrit-ssh-pub-key
