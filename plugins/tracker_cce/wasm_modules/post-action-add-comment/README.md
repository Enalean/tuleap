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
    "is_custom_code_execution": false,
    "tracker": {
        "id": 10,
        "uri": "trackers/10",
        "html_url": "/plugins/tracker/?tracker=10",
        "project": {
            "id": 101,
            "uri": "projects/101",
            "label": "Project 1",
            "icon": "1️⃣"
        },
        "label": "Tracker Tests 1",
        "description": "",
        "item_name": "test",
        "fields": [
            {
                "field_id": 297,
                "label": "field_a",
                "name": "field_a",
                "type": "int",
                "values": null,
                "required": false,
                "collapsed": false,
                "bindings": {
                    "type": null,
                    "list": []
                },
                "permissions": [
                    "read",
                    "update",
                    "create"
                ],
                "permissions_for_groups": {
                    "can_submit": [
                        {
                            "id": "2",
                            "uri": "user_groups/2",
                            "label": "Registered users",
                            "users_uri": "user_groups/2/users",
                            "short_name": "registered_users",
                            "key": "ugroup_registered_users_name_key"
                        }
                    ],
                    "can_read": [
                        {
                            "id": "1",
                            "uri": "user_groups/1",
                            "label": "Anonymous",
                            "users_uri": "user_groups/1/users",
                            "short_name": "all_users",
                            "key": "ugroup_anonymous_users_name_key"
                        }
                    ],
                    "can_update": [
                        {
                            "id": "101_3",
                            "uri": "user_groups/101_3",
                            "label": "Project members",
                            "users_uri": "user_groups/101_3/users",
                            "short_name": "project_members",
                            "key": "ugroup_project_members_name_key"
                        }
                    ]
                },
                "default_value": "0"
            },
            {
                "field_id": 299,
                "label": "field_b",
                "name": "field_b",
                "type": "int",
                "values": null,
                "required": false,
                "collapsed": false,
                "bindings": {
                    "type": null,
                    "list": []
                },
                "permissions": [
                    "read",
                    "update",
                    "create"
                ],
                "permissions_for_groups": {
                    "can_submit": [
                        {
                            "id": "2",
                            "uri": "user_groups/2",
                            "label": "Registered users",
                            "users_uri": "user_groups/2/users",
                            "short_name": "registered_users",
                            "key": "ugroup_registered_users_name_key"
                        }
                    ],
                    "can_read": [
                        {
                            "id": "1",
                            "uri": "user_groups/1",
                            "label": "Anonymous",
                            "users_uri": "user_groups/1/users",
                            "short_name": "all_users",
                            "key": "ugroup_anonymous_users_name_key"
                        }
                    ],
                    "can_update": [
                        {
                            "id": "101_3",
                            "uri": "user_groups/101_3",
                            "label": "Project members",
                            "users_uri": "user_groups/101_3/users",
                            "short_name": "project_members",
                            "key": "ugroup_project_members_name_key"
                        }
                    ]
                },
                "default_value": "0"
            },
            {
                "field_id": 333,
                "label": "field_sum",
                "name": "field_sum_11",
                "type": "string",
                "values": null,
                "required": false,
                "collapsed": false,
                "bindings": {
                    "type": null,
                    "list": []
                },
                "permissions": [
                    "read",
                    "update",
                    "create"
                ],
                "permissions_for_groups": {
                    "can_submit": [
                        {
                            "id": "2",
                            "uri": "user_groups/2",
                            "label": "Registered users",
                            "users_uri": "user_groups/2/users",
                            "short_name": "registered_users",
                            "key": "ugroup_registered_users_name_key"
                        }
                    ],
                    "can_read": [
                        {
                            "id": "1",
                            "uri": "user_groups/1",
                            "label": "Anonymous",
                            "users_uri": "user_groups/1/users",
                            "short_name": "all_users",
                            "key": "ugroup_anonymous_users_name_key"
                        }
                    ],
                    "can_update": [
                        {
                            "id": "101_3",
                            "uri": "user_groups/101_3",
                            "label": "Project members",
                            "users_uri": "user_groups/101_3/users",
                            "short_name": "project_members",
                            "key": "ugroup_project_members_name_key"
                        }
                    ]
                },
                "default_value": ""
            }
        ],
        "structure": [
            {
                "id": 298,
                "content": [
                    {
                        "id": 297,
                        "content": null
                    },
                    {
                        "id": 299,
                        "content": null
                    },
                    {
                        "id": 333,
                        "content": null
                    }
                ]
            }
        ],
        "semantics": {},
        "workflow": {
            "field_id": 0,
            "is_used": "",
            "is_legacy": false,
            "is_advanced": true,
            "rules": {
                "dates": [],
                "lists": []
            },
            "transitions": []
        },
        "permissions_for_groups": {
            "can_access": [],
            "can_access_submitted_by_user": [
                {
                    "id": "2",
                    "uri": "user_groups/2",
                    "label": "Registered users",
                    "users_uri": "user_groups/2/users",
                    "short_name": "registered_users",
                    "key": "ugroup_registered_users_name_key"
                },
                {
                    "id": "101_3",
                    "uri": "user_groups/101_3",
                    "label": "Project members",
                    "users_uri": "user_groups/101_3/users",
                    "short_name": "project_members",
                    "key": "ugroup_project_members_name_key"
                }
            ],
            "can_access_assigned_to_group": [],
            "can_access_submitted_by_group": [],
            "can_admin": []
        },
        "parent": null,
        "resources": [
            {
                "type": "reports",
                "uri": "trackers/10/tracker_reports"
            }
        ],
        "color_name": "inca-silver"
    }
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
