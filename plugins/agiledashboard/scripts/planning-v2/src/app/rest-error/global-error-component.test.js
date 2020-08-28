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

import angular from "angular";
import "angular-mocks";
import planning_module from "../app";
import * as location_helper from "./location-helper";

describe(`GlobalErrorComponent`, () => {
    let controller;
    beforeEach(() => {
        angular.mock.module(planning_module);
        angular.mock.inject(function ($componentController) {
            controller = $componentController("globalError");
        });
    });

    describe(`reloadPage()`, () => {
        it(`will reload the page`, () => {
            const reload = jest.spyOn(location_helper, "reload").mockImplementation(() => {});
            controller.reloadPage();
            expect(reload).toHaveBeenCalled();
        });
    });
});
