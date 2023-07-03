/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { LegacyLabelsBoxDisplay } from "./LegacyLabelsBoxDisplay";
import { AngularUIRouterStateStub } from "../../../../tests/stubs/AngularUIRouterStateStub";

describe("LegacyLabelsBoxDisplay", () => {
    it.each([
        [false, "the current view is not overview", "commits", false],
        [false, "the current view is the new pullrequest-overview", "overview", true],
        [true, "the current view is the legacy overview", "overview", false],
    ])("Should return %s when %s", (will_be_displayed, when, view_name, is_vue_overview_shown) => {
        expect(
            LegacyLabelsBoxDisplay.shouldLegacyLabelsBoxBeDisplayed(
                AngularUIRouterStateStub.withCurrentView(view_name),
                {
                    isVueOverviewShown: () => is_vue_overview_shown,
                }
            )
        ).toBe(will_be_displayed);
    });
});
