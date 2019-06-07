/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import artifact_modal from "../tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

describe("NewFollowupComponent -", () => {
    let ctrl, value;

    beforeEach(() => {
        angular.mock.module(artifact_modal);

        value = {
            format: ""
        };

        let $componentController;
        angular.mock.inject(function(_$componentController_) {
            $componentController = _$componentController_;
        });

        ctrl = $componentController("tuleapArtifactModalNewFollowup", null, {
            value
        });
    });

    describe(`isTextCurrentFormat()`, () => {
        it(`returns true when value format is "text"`, () => {
            ctrl.value.format = "text";

            expect(ctrl.isTextCurrentFormat()).toBe(true);
        });
    });

    describe(`isHTMLCurrentFormat()`, () => {
        it(`returns true when value format is "html"`, () => {
            ctrl.value.format = "html";

            expect(ctrl.isHTMLCurrentFormat()).toBe(true);
        });
    });
});
