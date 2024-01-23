# Post-action add comment

This module is an example of what you can do with this plugin. For this example we choose to use Rust, but you can use
any language which have a target to WASI Preview 1 (Go 1.21 for example).

## How to use

First you need to run `nix-shell` to have all needed tools.

Then you can test the module with:

```shell
cat file.json | cargo run
# or
echo '{"my": "json"}' | cargo run
```

The module takes a json as input representing an artifact changeset. The structure is the same as
for [Tracker webhooks](https://docs.tuleap.org/user-guide/integration/webhook.html#tracker). For example:

```json
{
    "id": 111,
    "action": "update",
    "user": {
        "id": 102,
        "uri": "users/102",
        "user_url": "/users/fred",
        "real_name": "Fred Bob",
        "display_name": "Fred Bob (fred)",
        "username": "fred",
        "ldap_id": "",
        "avatar_url": "",
        "is_anonymous": false,
        "has_avatar": false
    },
    "current": {
        "id": 938,
        "submitted_by": 102,
        "submitted_by_details": {
            "id": 102,
            "uri": "users/102",
            "user_url": "/users/fred",
            "real_name": "Fred Bob",
            "display_name": "Fred Bob (fred)",
            "username": "fred",
            "ldap_id": "",
            "avatar_url": "",
            "is_anonymous": false,
            "has_avatar": false
        },
        "submitted_on": "2024-01-22T09:04:43+00:00",
        "last_comment": {
            "body": "",
            "post_processed_body": "",
            "format": "html",
            "commonmark": "",
            "ugroups": null
        },
        "values": [
            {
                "field_id": 297,
                "type": "int",
                "label": "field_a",
                "value": 5
            },
            {
                "field_id": 299,
                "type": "int",
                "label": "field_b",
                "value": 5
            },
            {
                "field_id": 333,
                "type": "string",
                "label": "field_sum",
                "value": "odd"
            }
        ],
        "last_modified_date": "2024-01-22T09:04:43+00:00"
    },
    "is_custom_code_execution": false
}
```

And ouput a json representing new modifications. The structure must be identical to the one for REST
API `PUT /api/artifacts/:id`. For example:

```json
{
    "values": [
        {
            "field_id": 333,
            "value": "even",
            "bind_value_ids": [],
            "links": [],
            "all_links": null,
            "parent": [],
            "is_autocomputed": null,
            "manual_value": null
        }
    ],
    "comment": {
        "body": "Sum of field_a and field_b is even -> 5 + 5 = 10",
        "format": "text"
    }
}
```

---

If you want to use it for your Tracker, you first need to build it:

```shell
cargo build --target wasm32-wasi --release
```

Then upload the binary result file (`target/wasm32-wasi/release/post-action-add-comment.wasm`) to your Tracker
administration (Administration > Workflow > Custom code execution).
