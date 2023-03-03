# Development tools

## Blackfire

Create a personal account in blackfire site:

Your credentials are stored in `Account > Credentials`

follow instructions of (depending of your OS):
<https://blackfire.io/docs/up-and-running/installation#installation-instructions>

You need to follow the following sections

:   -   Configuring the Red Hat Repository
    -   Installing the Agent
    -   Installing the PHP Probe

Once done restart php-fpm: `make restart-services`

Install the blackfire extension in your browser, then launch \"profile\"

## Link database to IDE

In your tuleap root directory execute `make show-ips`

you should have a db line like: xxx.xxx.xxx.xxx db

then run `make show-passwords`

you should have a line for codendiadm:

`Codendiadm unix & DB (codendiadm): <password>`

In your IDE, the `Database` add a new MySqlData source:

-   Host: Copy/Paste IP
-   login: codendiadm
-   password: <password>
-   database: tuleap

Then test connection: you should be able to read/write in tuleap
database.

After each container reload, the database IP might change, if connection
is lost, just `make show-ips` and update the host in
PhpStorm.

## PHPUnit configuration

`Settings > Language & Frameworks > PHP`

Choose a cli interpreter and validate

`Settings > Language & Frameworks > PHP > Test Framework`

-   PHPUnit Local
-   Use composer autoloader
-   Path to script : `<tuleap_root>/src/vendor/autoload.php`

Default configuration file:
`<tuleap_root>/tests/unit/phpunit.xml`

`Run > Edit configuration > Default > PHPUnit` Choose
custom working directory : `<tuleap_root>`

Open a phpunit file, run test to check if ok

## Configure Debugger in PHPStorm

In PHPStorm `Settings > Language & Frameworks > PHP > Server`

Add server and define your mapping

-   Host: `tuleap-web.tuleap-aio-dev.docker`
-   Port: `443`
-   File/Directory: `<tuleap root>`
-   Absolute path on server: `/usr/share/tuleap`

In PHPStorm `Run > Webserver validation`

-   Path to create validation script: `<tuleap_root>`/tuleap/src/www
-   Url to validation script: `https://tuleap-web.tuleap-aio-dev.docker/`

Click on validate, you should only have a warning about remote host.
Close modal.

Then start listening `Run` > `listen for PHP Debug
Connections` in the PHPStorm toolbar

Go on <https://www.jetbrains.com/phpstorm/marklets/>

click on \"generate\". Add the link of start debugger in bookmark (right
click > bookmark this link)

Add a break point (in pre.php for instance) Go on your tuleap page, then
click on bookmark then refresh your page, debugger should start
