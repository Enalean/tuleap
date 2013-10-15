This explains how to write and run REST tests.

# Setup
In order to Run the tests, certain things need to be installed.

- Go to the root of Tuleap folder run
$ make api_test_setup

This will download phpunit and guzzle which is a php HTTP client.
See http://guzzlephp.org/docs.html

- Next, you will need to add this to the end of /etc/httpd/conf/httpd.conf
Listen 8089
<VirtualHost *:8089>
    ServerName localhost
    #ServerAlias www.shunt.cro.enalean.com
    #ServerAlias lists.shunt.cro.enalean.com
    #CustomLog logs/access_log common

    Include conf.d/php.conf
    Include conf.d/auth_mysql.conf
    Include conf.d/codendi_aliases.conf

    SetEnv CODENDI_LOCAL_INC "/etc/codendi/conf/integration_tests.inc"
</VirtualHost>

- Now restart apache
$service httpd restart

- Update your database
$ mysql -uUSERNAME -pPASSWORD
> GRANT ALL PRIVILEGES on integration_test.* to 'integration_test'@'localhost' identified by 'welcome0';

- Finally, check your configurations:
$ vi /etc/codendi/conf/integration_tests.inc
Typically, you may need to change $codendi_dir

$ vi /etc/codendi/conf/dbtest.inc
If you have used anything other than the default values, change as necessary.

# Running the tests:
$/usr/share/codendi/src/utils/php-launcher.sh vendor/phpunit/phpunit/phpunit.php tests/rest