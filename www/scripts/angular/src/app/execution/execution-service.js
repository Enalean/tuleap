angular
    .module('campaign')
    .service('ExecutionService', ExecutionService);

ExecutionService.$inject = ['Restangular'];

function ExecutionService(Restangular) {
    Restangular.setBaseUrl('/api/v1');

    return {
        getExecutions: getExecutions
    };

    function getExecutions(campaign_id) {
        return [
            {
                "id": 24605,
                "uri": "executions/24605",
                "results": "",
                "status": "passed",
                "last_update_date": null,
                "assigned_to": {
                    "id": 101,
                    "uri": "users/101",
                    "email": "hugo@example.com",
                    "real_name": "hugo",
                    "username": "hugo",
                    "ldap_id": "",
                    "avatar_url": "https://paelut/users/hugo/avatar.png"
                },
                "previous_execution": {
                    "changeset_id": 25120,
                    "last_change_date": "2014-08-07T10:35:46+02:00",
                    "by": {
                        "id": 102,
                        "uri": "users/102",
                        "email": "hugo.kelfani+hkelf.paelut@enalean.com",
                        "real_name": "hkelf",
                        "username": "hkelf",
                        "ldap_id": "",
                        "avatar_url": "https://paelut/users/hkelf/avatar.png"
                    },
                    "status": "Not Run"
                },
                "test_def": {
                    "id": 24600,
                    "uri": "testdef/24600",
                    "summary": "Tracker Rule date verifications for a workflow",
                    "category": "AgileDashboard"
                }
            },
            {
                "id": 24606,
                "uri": "executions/24606",
                "results": "",
                "status": "failed",
                "last_update_date": null,
                "assigned_to": null,
                "previous_execution": {
                    "changeset_id": 25121,
                    "last_change_date": "2014-08-07T10:35:55+02:00",
                    "by": {
                        "id": 102,
                        "uri": "users/102",
                        "email": "hugo.kelfani+hkelf.paelut@enalean.com",
                        "real_name": "hkelf",
                        "username": "hkelf",
                        "ldap_id": "",
                        "avatar_url": "https://paelut/users/hkelf/avatar.png"
                    },
                    "status": "Not Run"
                },
                "test_def": {
                    "id": 24601,
                    "uri": "testdef/24601",
                    "summary": "Html notification for tracker v5",
                    "category": "SOAP"
                }
            },
            {
                "id": 24607,
                "uri": "executions/24607",
                "results": "",
                "status": "blocked",
                "last_update_date": null,
                "assigned_to": null,
                "previous_execution": {
                    "changeset_id": 25122,
                    "last_change_date": "2014-08-07T10:36:38+02:00",
                    "by": {
                        "id": 102,
                        "uri": "users/102",
                        "email": "hugo.kelfani+hkelf.paelut@enalean.com",
                        "real_name": "hkelf",
                        "username": "hkelf",
                        "ldap_id": "",
                        "avatar_url": "https://paelut/users/hkelf/avatar.png"
                    },
                    "status": "Not Run"
                },
                "test_def": {
                    "id": 24602,
                    "uri": "testdef/24602",
                    "summary": "Migrate a tracker v3 to a tracker v5",
                    "category": "AgileDashboard"
                }
            },
            {
                "id": 24608,
                "uri": "executions/24608",
                "results": "",
                "status": "notrun",
                "last_update_date": null,
                "assigned_to": {
                    "id": 102,
                    "uri": "users/102",
                    "email": "nico@example.com",
                    "real_name": "nico",
                    "username": "nico",
                    "ldap_id": "",
                    "avatar_url": "https://paelut/users/nico/avatar.png"
                },
                "previous_execution": {
                    "changeset_id": 25123,
                    "last_change_date": "2014-08-07T10:36:45+02:00",
                    "by": {
                        "id": 102,
                        "uri": "users/102",
                        "email": "hugo.kelfani+hkelf.paelut@enalean.com",
                        "real_name": "hkelf",
                        "username": "hkelf",
                        "ldap_id": "",
                        "avatar_url": "https://paelut/users/hkelf/avatar.png"
                    },
                    "status": "Not Run"
                },
                "test_def": {
                    "id": 24603,
                    "uri": "testdef/24603",
                    "summary": "Deleted tracker should no more be part of the exported DB Deleted tracker should no more be part of the exported DB ",
                    "category": "Git"
                }
            },
            {
                "id": 24609,
                "uri": "executions/24609",
                "results": "",
                "status": "notrun",
                "last_update_date": null,
                "assigned_to": {
                    "id": 102,
                    "uri": "users/102",
                    "email": "nico@example.com",
                    "real_name": "nico",
                    "username": "nico",
                    "ldap_id": "",
                    "avatar_url": "https://paelut/users/nico/avatar.png"
                },
                "previous_execution": {
                    "changeset_id": 25124,
                    "last_change_date": "2014-08-07T10:36:51+02:00",
                    "by": {
                        "id": 102,
                        "uri": "users/102",
                        "email": "hugo.kelfani+hkelf.paelut@enalean.com",
                        "real_name": "hkelf",
                        "username": "hkelf",
                        "ldap_id": "",
                        "avatar_url": "https://paelut/users/hkelf/avatar.png"
                    },
                    "status": "Not Run"
                },
                "test_def": {
                    "id": 24604,
                    "uri": "testdef/24604",
                    "summary": "Project admin can import users from a LDAP Project dmin can import users from a LDAP",
                    "category": "null"
                }
            }
        ];
        //        return Restangular.one('campaigns', campaign_id).all('executions').getList().$object;
    }
}