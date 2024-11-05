# Vault Plugin: Tuleap Secret Backend

This is a standalone plugin for [Hashicorp Vault](https://www.github.com/hashicorp/vault).

The Tuleap secret backend for Vault generates user credentials dynamically based on Tuleap instance hostnames.
This means that users that need to access a Tuleap instance (e.g. to support a end user) no longer need to know hardcoded
credentials on the instance: they can request them from Vault to get a temporarily privileged account on the instance.


## Setup

The setup guide assumes some familiarity with Vault and [Vault's plugin ecosystem](https://www.vaultproject.io/docs/plugin/index.html).
You must have a Vault server already running, unsealed, and authenticated.

1. Move the compiled plugin into Vault's configured `plugin_directory`:

    ```sh
    $ mv vault-tuleap-plugin /etc/vault/plugins/vault-tuleap-plugin
    ```

2. Calculate the SHA256 of the plugin and register it in Vault's plugin catalog.
If you are downloading the pre-compiled binary, it is highly recommended that
you use the published checksums to verify integrity. The parameter `-insecure-tls`
can be passed to the command to disable the TLS verification. This option should
only be used in a development environment.

    ```sh
    $ export SHA256=$(sha256sum "/etc/vault/plugins/vault-tuleap-plugin" | cut -d' ' -f1)

    $ vault write sys/plugins/catalog/tuleap-secret-plugin \
        sha_256="${SHA256}" \
        command="vault-tuleap-plugin"
    ```

3. Mount the secrets engine:

    ```sh
    $ vault secrets enable -path="tuleap" -plugin-name="tuleap-secret-plugin" plugin
    ```


4. Configure a connection to communicate with a Tuleap instance:

    ```sh
    $ vault write tuleap/config/my-tuleap \
        allowed_roles="..."
    ```

5. Configure a role that maps a name in Vault to hosts running a Tuleap instance:


    ```sh
    $ vault write tuleap/roles/my-role \
        config_name=my-tuleap \
        allowed_hosts="..."
        default_ttl="30m"
    ```

6. Retrieve the public key of the connection to set it in the configuration file
of the dynamic credentials plugin on the Tuleap intance:


    ```text
    $ vault read tuleap/config/my-tuleap
    Key                Value
    ---                -----
    public_key         IpuL6ZHoKzsbGFiFLPuUvD/8dTlZ14t47O5WAyzRpgk=
    allowed_roles      ...
    ```

## Usage

After the secrets engine is configured and a user/machine has a Vault token with
the proper permission, it can generate credentials.

1. Generate a new credential by reading from the `/creds` endpoint with the name
of the role:

    ```text
    $ vault read tuleap/creds/my-role
    Key                Value
    ---                -----
    lease_id           tuleap/creds/my-role/b6761aad-403a-4cf9-a41d-4610e79fc90e
    lease_duration     30m
    lease_renewable    false
    password           6b36775a-ae36-4a62-8025-770c38fc4a84
    username           forge__dynamic_credential-970f1d44-4456-4bcd-b6e2-de247ae8b1d4
    ```

## HTTP API

This documentation assumes the Tuleap secrets engine is enabled at the
`/tuleap` path in Vault. Since it is possible to enable secrets engines at any
location, please update your API calls accordingly.

### Configure connection

This endpoint configures the connection used to communicate with Tuleap instances.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `POST`   | `/tuleap/config/:name`       | `204 (empty body)`     |

#### Parameters
- `name` `(string: <required>)` – Specifies the name for this connection.
  This is specified as part of the URL.

- `allowed_roles` `(slice: [])` - Array or comma separated string of the roles
  allowed to use this configuration. Defaults to empty (no roles), if contains a
  "*" any role can use this connection.

#### Sample Payload

```json
{
  "allowed_roles": ["*"]
}
```

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request POST \
    --data @payload.json \
    http://127.0.0.1:8200/v1/tuleap/config/internals
```

### Read connection

This endpoint returns the configuration settings for a connection.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `GET`    | `/tuleap/config/:name`       | `200 application/json` |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the connection to read.
  This is specified as part of the URL.

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request GET \
    http://127.0.0.1:8200/v1/tuleap/config/internals
```

#### Sample Response

```json
{
	"data": {
		"allowed_roles": [
			"*"
		],
		"public_key": "IpuL6ZHoKzsbGFiFLPuUvD/8dTlZ14t47O5WAyzRpgk="
	},
}
```

### List connections

This endpoint returns a list of available connections. Only the connection names
are returned, not any values.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `LIST`   | `/tuleap/config`             | `200 application/json` |

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request LIST \
    http://127.0.0.1:8200/v1/tuleap/config
```

#### Sample Response

```json
{
  "data": {
    "keys": ["tuleap-one", "tuleap-two"]
  }
}
```

### Delete connection

This endpoint deletes a connection.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `DELETE` | `/tuleap/config/:name`       | `204 (empty body)`     |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the connection to delete.
  This is specified as part of the URL.

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request DELETE \
    http://127.0.0.1:8200/v1/tuleap/config/internals
```

### Create role

This endpoint creates or updates a role definition.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `POST`   | `/tuleap/roles/:name`        | `204 (empty body)`     |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the role to create. This
  is specified as part of the URL.

- `config_name` `(string: <required>)` - The name of the connection to use for
  this role.

- `default_ttl` `(string/int: 0)` - Specifies the TTL for the leases
  associated with this role. Accepts time suffixed strings ("1h") or an integer
  number of seconds. Defaults to system/engine default TTL time.

- `max_ttl` `(string/int: 0)` - Specifies the maximum TTL for the leases
  associated with this role. Accepts time suffixed strings ("1h") or an integer
  number of seconds. Defaults to system/engine default TTL time.

- `allowed_hosts` `(slice: [])` – Specifies the allowed hosts of the role.

- `allow_glob_hosts` `(bool: false)` - Allows names specified in
  `allowed_hosts` to contain glob patterns (e.g. `rt*.example.com`). Clients
  will be allowed to request credentials with hosts matching the glob
  patterns.

#### Sample Payload

```json
{
    "config_name": "internals",
    "default_ttl": "1h",
    "allowed_hosts": ["tuleap.example.com"]
}
```

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request POST \
    --data @payload.json \
    http://127.0.0.1:8200/v1/tuleap/roles/my-role
```

### Read role

This endpoint queries the role definition.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `GET`    | `/tuleap/roles/:name`        | `200 application/json` |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the role to read. This
  is specified as part of the URL.

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    http://127.0.0.1:8200/v1/tuleap/roles/my-role
```

#### Sample Response

```json
{
    "data": {
		"config_name": "mysql",
		"default_ttl": 3600,
		"max_ttl": 86400,
		"allowed_hosts": ["tuleap.example.com"],
		"allow_glob_hosts": false
	},
}
```

### List roles

This endpoint returns a list of available roles. Only the role names are
returned, not any values.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `LIST`   | `/tuleap/roles`              | `200 application/json` |

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request LIST \
    http://127.0.0.1:8200/v1/tuleap/roles
```

#### Sample Response

```json
{
    "data": {
        "keys": ["dev", "prod"]
    }
}
```

### Delete role

This endpoint deletes the role definition.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `DELETE` | `/tuleap/roles/:name`        | `204 (empty body)`     |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the role to delete. This
  is specified as part of the URL.

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request DELETE \
    http://127.0.0.1:8200/v1/tuleap/roles/my-role
```


### Generate credentials

This endpoint generates a new set of dynamic credentials based on the named
role.

| Method   | Path                         | Produces               |
| :------- | :--------------------------- | :--------------------- |
| `POST`    | `/tuleap/creds/:name`       | `200 application/json` |

#### Parameters

- `name` `(string: <required>)` – Specifies the name of the role to create
  credentials against. This is specified as part of the URL.
- `host` `(string: <required>)` – Specifies the name of the host to create
  credentials against.

#### Sample Payload

```json
{
    "host": "tuleap.example.com"
}
```

#### Sample Request

```
$ curl \
    --header "X-Vault-Token: ..." \
    --request POST \
    --data @payload.json \
    http://127.0.0.1:8200/v1/tuleap/creds/my-role
```

#### Sample Response

```json
{
    "data": {
        "username": "forge__dynamic_credential-970f1d44-4456-4bcd-b6e2-de247ae8b1d4",
        "password": "6b36775a-ae36-4a62-8025-770c38fc4a84"
    }
}
