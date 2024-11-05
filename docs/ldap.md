# LDAP

## Add LDAP users to your platform

Two commands are available to create users in development stack's LDAP:
- `./tools/utils/tuleap-dev.php add-ldap-user` to create a new user
- `./tools/utils/tuleap-dev.php mod-ldap-user` to modify an existing user

Adding users into the LDAP is mandatory prior being able to login on the platform.

## Create a group in LDAP

This examples illustrates how to create a group \"developers\" in the
LDAP.

In your `tuleap-web` container, create a file `developers.ldif` with the
following content:

``` yaml
dn: cn=developers,ou=groups,dc=tuleap,dc=local
objectClass: groupOfNames
cn: developers
member: uid=pat_le_chef
member: uid=vaceletm
```

Then execute the following command:

``` shell
ldapadd -h ldap -D 'cn=Manager,dc=tuleap,dc=local' -W -f developers.ldif
```

The password can be found in the `.env` file in your sources (where you
run `make start`):

``` ini
LDAP_MANAGER_PASSWORD=8lHoMsOBU…
```

## PHPLDAPAdmin

You can set-up a local ldap with a UI managment front in a few steps.

-   Install docker then follow the instructions here for creating an
    ldap instance <https://github.com/Enalean/docker-ldap>
-   Download and install
    <https://phpldapadmin.sourceforge.net/wiki/index.php/Installation>
-   Modify config.php to your liking
-   Restart apache and go to \[name of your localhost\]/phpldapadmin
-   Hack one of files in phpldapadmin (known bug)
    <https://stackoverflow.com/questions/20673186/getting-error-for-setting-password-field-when-creating-generic-user-account-phpl>
-   Log-in with the crediantials from the docker README: (currently)
    cn=Manager,dc=tuleap,dc=local / welcome0

Example config.php:

``` php
$config->custom->appearance['friendly_attrs'] = array(
    'facsimileTelephoneNumber' => 'Fax',
    'gid'                      => 'Group',
    'mail'                     => 'Email',
    'telephoneNumber'          => 'Telephone',
    'uid'                      => 'User Name',
    'userPassword'             => 'Password'
);

......

/*********************************************
 * Define your LDAP servers in this section  *
 *********************************************/

$servers = new Datastore();

$servers->newServer('ldap_pla');
$servers->setValue('server','name','My LDAP Server');
$servers->setValue('server','host','ldap://localhost');
$servers->setValue('login','auth_type','cookie');
$servers->setValue('login','bind_id','cn=Manager,dc=tuleap,dc=local');
$servers->setValue('login','bind_pass','welcome0');
```

## Using your local LDAP with a local gerrit

Use this config in `etc/gerrit.conf`:

``` bash
[auth]
    type = LDAP
[ldap]
    server = ldap://localhost
    accountBase = ou=people,dc=tuleap,dc=local
    groupBase = ou=groups,dc=tuleap,dc=local
    accountFullName = cn
    sslVerify = false
```
