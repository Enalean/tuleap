/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe(`UserPreferencesService`, () => {
    let UserPreferencesService, $q, wrapPromise;
    beforeEach(() => {
        angular.mock.module(kanban_module);
        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _$q_, _UserPreferencesService_) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            UserPreferencesService = _UserPreferencesService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    describe(`setPreference`, () => {
        it(`will call PATCH on user's preferences`, async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = UserPreferencesService.setPreference(
                110,
                "agiledashboard_kanban_item_view_mode_16",
                "compact-view",
            );

            await expect(wrapPromise(promise)).resolves.toBeTruthy();
            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/users/110/preferences", {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    key: "agiledashboard_kanban_item_view_mode_16",
                    value: "compact-view",
                }),
            });
        });
    });
});
