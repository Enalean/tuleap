# Keycloak in dev environment

This section covers how to setup a local OIDC provider based on
[Keycloak](https://www.keycloak.org/) next to your Tuleap development
platform in order to develop or debug.

## Start Keycloak server

```bash
docker-compose up -d keycloak
```

The URL of the server is `https://tuleap-web.tuleap-aio-dev.docker/keycloak/`.

This comes with a ready to use realm and client secret (see below). If you need to access to the admin console of keycloak
(to create users for example), then you will find admin credentials [in docker-compose.yml](../../docker-compose.yml).

## Configure Tuleap to use Keycloak

As site administrator, install and enable OpenID Connect Client plugin.

Now create a provider:

-   Name: `Keycloak`
-   Authorization endpoint:
    `https://tuleap-web.tuleap-aio-dev.docker/keycloak/realms/tuleap-realm/protocol/openid-connect/auth`
-   Token endpoint:
    `https://tuleap-web.tuleap-aio-dev.docker/keycloak/realms/tuleap-realm/protocol/openid-connect/token`
-   JWKS endpoint:
    `https://tuleap-web.tuleap-aio-dev.docker/keycloak/realms/tuleap-realm/protocol/openid-connect/certs`
-   User information endpoint:
    `https://tuleap-web.tuleap-aio-dev.docker/keycloak/realms/tuleap-realm/protocol/openid-connect/userinfo`
-   Client ID: `tuleap-dev`
-   Client secret: `TULEAP_DO_NOT_USE_THIS_IN_PRODUCTION`

As anonymous go to login page and use the `Keycloak` button. Connect to
Keycloak by using preconfigured `jdoe` account with password `welcome0`.
You should be redirected to Tuleap and be able to link an account to OpenID Connect.
