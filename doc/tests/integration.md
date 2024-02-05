# Integration tests

## REST tests

There is a docker image for REST tests, just run the following command:

``` bash
$> make tests-rest
```

It will execute all REST tests in a docker container. This container is
stopped and removed once the tests are finished. If you need to run
tests manually, do the following instead:

``` bash
$> make tests-rest SETUP_ONLY=1
$root@d4601e92ca3f> /opt/remi/php82/root/usr/bin/php \
  /usr/share/tuleap/tests/rest/vendor/bin/phpunit \
  --configuration /usr/share/tuleap/tests/rest/phpunit.xml \
  --do-not-cache-result \
  /usr/share/tuleap/plugins/testmanagement/tests/rest/TestManagement/ExecutionsTest.php # Optional path
```

In case of failure, you may need to attach to this running container in
order to parse logs for example:

``` bash
$> docker exec -ti <name-of-the-container> bash
$root@d4601e92ca3f> tail -f /var/opt/remi/php82/log/php-fpm/error.log
```
