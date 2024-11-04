# ONLYOFFICE for Tuleap development

This section covers how to set up a local ONLYOFFICE server next to your Tuleap
development platform in order to develop or debug.

## Setup ONLYOFFICE the first time

First, start the ONLYOFFICE container.

``` bash
you@workstation $> docker-compose up -d onlyoffice
```

Once started, you should be able to reach the server using the following url: [https://tuleap-web.tuleap-aio-dev.docker/onlyoffice-doc-server/welcome/](https://tuleap-web.tuleap-aio-dev.docker/onlyoffice-doc-server/welcome/).

Note: If you get a 502 Bad Gateway error, wait a couple of minutes before refreshing the page.

## Configure the ONLYOFFICE integration

Install and activate the ONLYOFFICE plugin on site-administration if it's not already done.

Then click on [+ Add document server]. Fill in the required fields:
- Document server URL: `https://tuleap-web.tuleap-aio-dev.docker/onlyoffice-doc-server/`
- JWT secret: `TULEAP_DO_NOT_USE_THIS_IN_PRODUCTION` (or the one you set if you've decided to change it).

You should now be able to create/update/delete ONLYOFFICE documents from any Document service.
