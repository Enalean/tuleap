/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import execution_module from "./execution.js";
import angular from "angular";
import ui_router from "@uirouter/angularjs";
import "angular-mocks";

describe("ExecutionListFilter", function () {
    var ngFilter;

    beforeEach(angular.mock.module(ui_router));
    beforeEach(angular.mock.module(execution_module));
    beforeEach(
        angular.mock.inject(function ($filter) {
            ngFilter = $filter;
        }),
    );

    var list = [
        {
            id: 24605,
            uri: "executions/24605",
            results: "",
            status: "passed",
            last_update_date: null,
            assigned_to: null,
            //...
            definition: {
                id: 24600,
                uri: "testdef/24600",
                summary: "Tracker Rule date verifications for a workflow",
                category: "AgileDashboard",
                automated_tests: "bip boop!",
            },
        },
        {
            id: 24606,
            uri: "executions/24606",
            results: "",
            status: "failed",
            last_update_date: null,
            assigned_to: {
                id: 101,
                uri: "users/101",
                email: "renelataupe@example.com",
                real_name: "rtaupe",
                username: "rtaupe",
                ldap_id: "",
                avatar_url: "https://paelut/users/rtaupe/avatar.png",
            },
            //...
            definition: {
                id: 24601,
                uri: "testdef/24601",
                summary: "Html notification for tracker v5",
                category: "SOAP",
            },
        },
    ];

    it("filters on execution status when active", function () {
        var results = ngFilter("AutomatedTestsFilter")(list, false);
        expect(results).toHaveLength(1);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24606 }));
    });

    it("does not filter otherwise", function () {
        var results = ngFilter("AutomatedTestsFilter")(list, true);
        expect(results).toHaveLength(2);
    });
});
