# Keycloak in dev environment

This section covers how to setup a local OIDC provider based on
[Keycloak](https://www.keycloak.org/) next to your Tuleap development
platform in order to develop or debug.

## Configure Keycloak server

### Start a local Keycloak server

Add the following to the `docker-compose.yml`:

``` yaml
keycloak:
  image: quay.io/keycloak/keycloak:20.0.3
  command: start-dev
  environment:
    - KEYCLOAK_ADMIN=admin
    - KEYCLOAK_ADMIN_PASSWORD=admin
```

Then start the container:

    docker-compose up -d keycloak

You will get the keycloak container ip address with:

    make show-ips
    # 192.0.2.11 /tuleap-keycloak-1

Examples below will be based on `192.0.2.11`. You should adapt them to
your own address.

### Create a realm

Open <http://192.0.2.11:8080> in your browser and open the
Administration Console. Admin credentials can be find in the
`docker-compose.yml` you updated beforehand.

By default you are on the realm \"master\", but it is advised to work on
your own. So create a new realm, here we will name it `tuleap-realm`.

In this realm, we now create a user to verify authentication.

-   Create new user
    -   Username: `jdoe`
    -   Email: `jdoe@example.com` *(or your own)*
    -   Email verified: `On`
    -   First name: `Jane`
    -   Last name: `Doe`
-   Set a password in Credentials (Temporary: `Off`)
-   Test the connection
    -   Open <http://192.0.2.11:8080/realms/tuleap-realm/account> in a
        tab
    -   Connect with `jdoe`.
    -   Celebrate your first victory 🎉

### Create a client

This is where we allow Tuleap to use this Keycloak server for
authentication.

As administrator, in the realm `tuleap-realm`, create a Client. We
choose `tuleap-dev` as ID:

-   Client type: `OpenID Connect`
-   Client ID: `tuleap-dev`
-   Client authentication: `On`

Once created, update the Access settings:

-   Valid redirect URIs:
    `https://tuleap-web.tuleap-aio-dev.docker/plugins/openidconnectclient/`
-   Web origins: `https://tuleap-web.tuleap-aio-dev.docker/`

Copy the needed informations to create the provider on Tuleap side (see
below):

-   Client secret is given in the Credentials tab of the server.
-   Endpoints are given by the following URL
    <http://192.0.2.11:8080/realms/tuleap-realm/.well-known/openid-configuration>.

## Configure Tuleap to use Keycloak

As site administrator, install and enable OpenID Connect Client plugin.
Now create a provider:

-   Name: `Keycloak`
-   Authorization endpoint:
    `https://192.0.2.11:8080/realms/tuleap-realm/protocol/openid-connect/auth`
-   Token endpoint:
    `https://192.0.2.11:8080/realms/tuleap-realm/protocol/openid-connect/token`
-   JWKS endpoint:
    `https://192.0.2.11:8080/realms/tuleap-realm/protocol/openid-connect/certs`
-   User information endpoint:
    `https://192.0.2.11:8080/realms/tuleap-realm/protocol/openid-connect/userinfo`
-   Client ID: `tuleap-dev`
-   Client secret: `<client-id>`

Tuleap asks us to enter valid `https` URLs. Since we are in a dev
environment and we didn't set up certificates for SSL we have to create
the provider with `https` URLs first and then update by hand the table
`plugin_openidconnectclient_provider_generic` to switch to `http` URLs.

As anonymous go to login page and use the `Keycloak` button. Connect to
Keycloak by using `jdoe` account you configured earlier. You should be
redirected to Tuleap and be able to link an account to OpenID Connect.

Celebrate your great success 🎉, you deserve it.
