/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { validateOpenListFieldValue } from "./open-list-field-validate-service.js";

describe("TuleapArtifactModalOpenListFieldValidateService", function () {
    describe("validateFieldValue() -", function () {
        it("Given a field value that was undefined, then it will return null", function () {
            const result = validateOpenListFieldValue(undefined);

            expect(result).toBe(null);
        });

        it("Given an open list value model, then it will return only the 'field_id' an 'value' attributes", function () {
            const value_model = {
                bindings: { type: "static" },
                field_id: 628,
                label: "chubby smiddum",
                permissions: ["read", "update", "create"],
                type: "tbl",
                value: {
                    bind_value_objects: [],
                },
            };

            const result = validateOpenListFieldValue(value_model);

            expect(result).toEqual({
                field_id: 628,
                value: {
                    bind_value_objects: [],
                },
            });
        });

        it("Given a static open list value model, then it will return the values with only 'id' and 'label' attributes", function () {
            const value_model = {
                bindings: { type: "static" },
                value: {
                    bind_value_objects: [
                        {
                            id: 127,
                            label: "metallotherapy",
                            color: null,
                            other_property: "palaeographic",
                        },
                        {
                            id: 126,
                            label: "upshut",
                            color: [249, 235, 74],
                        },
                    ],
                },
            };

            const result = validateOpenListFieldValue(value_model);

            expect(result).toEqual({
                value: {
                    bind_value_objects: [
                        {
                            id: 127,
                            label: "metallotherapy",
                        },
                        {
                            id: 126,
                            label: "upshut",
                        },
                    ],
                },
            });
        });

        it("Given a ugroups open list value model, then it will return the values with only 'id' and 'short_name' attributes", function () {
            const value_model = {
                bindings: { type: "ugroups" },
                value: {
                    bind_value_objects: [
                        {
                            id: "769",
                            key: "frothily",
                            label: "frothily",
                            short_name: "frothily",
                            uri: "user_groups/769",
                            users_uri: "user_groups/769/users",
                            other_property: "togs",
                        },
                        {
                            id: "175_3",
                            key: "ugroup_project_members_name_key",
                            short_name: "project_members",
                            label: "Project members",
                            uri: "user_groups/175_3",
                            users_uri: "user_groups/175_3/users",
                        },
                    ],
                },
            };

            const result = validateOpenListFieldValue(value_model);

            expect(result).toEqual({
                value: {
                    bind_value_objects: [
                        {
                            id: "769",
                            short_name: "frothily",
                        },
                        {
                            id: "175_3",
                            short_name: "project_members",
                        },
                    ],
                },
            });
        });

        it("Given a users open list value model, then it will return the values with only 'email' for anonymous users and 'id', 'username' and 'email' attributes for registered users", function () {
            const value_model = {
                bindings: { type: "users" },
                value: {
                    bind_value_objects: [
                        {
                            id: 168,
                            avatar_url:
                                "http://malleolable.com/rhizophora/unthrivingness?a=witter&b=threadiness#reascensional",
                            display_name: "Richelle Zeschke (rzeschke)",
                            email: "philyra@reoxygenate.net",
                            is_anonymous: false,
                            ldap_id: "168",
                            real_name: "Richelle Zeschke",
                            status: "A",
                            uri: "/users/rzeschke",
                            username: "rzeschke",
                            other_property: "orotund",
                        },
                        {
                            id: null,
                            avatar_url:
                                "http://charadriidae.com/themes/common/images/avatar_default.png",
                            display_name: "mexica@cadastral.com",
                            email: "mexica@cadastral.com",
                            is_anonymous: true,
                            ldap_id: null,
                            real_name: null,
                            status: null,
                            uri: null,
                            username: null,
                            other_property: "spiggoty",
                        },
                    ],
                },
            };

            const result = validateOpenListFieldValue(value_model);

            expect(result).toEqual({
                value: {
                    bind_value_objects: [
                        {
                            id: 168,
                            email: "philyra@reoxygenate.net",
                            username: "rzeschke",
                        },
                        {
                            email: "mexica@cadastral.com",
                        },
                    ],
                },
            });
        });

        it("Given a users open list value model with an anonymous user entered through select2, then it will return the value with only the 'email' attribute", function () {
            const value_model = {
                bindings: { type: "users" },
                value: {
                    bind_value_objects: [
                        {
                            display_name: "nubbling@thasian.edu",
                            email: "nubbling@thasian.edu",
                            id: "nubbling@thasian.edu",
                            is_anonymous: true,
                        },
                    ],
                },
            };

            const result = validateOpenListFieldValue(value_model);

            expect(result).toEqual({
                value: {
                    bind_value_objects: [
                        {
                            email: "nubbling@thasian.edu",
                        },
                    ],
                },
            });
        });
    });
});
