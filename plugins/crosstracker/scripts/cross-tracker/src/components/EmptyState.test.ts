/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { WritingCrossTrackerReport } from "../domain/WritingCrossTrackerReport";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { expect, describe, it } from "vitest";
import EmptyState from "./EmptyState.vue";

describe("EmptyState", () => {
    const getWrapper = (
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof EmptyState>> => {
        return shallowMount(EmptyState, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                writing_cross_tracker_report,
            },
        });
    };

    it(`display no artifact message if expert mode when no tracker`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date FROM @project='self' WHERE start_date != ''`;
        const wrapper = getWrapper(writing_cross_tracker_report);
        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "No artifact found",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "There is no artifact matching the query.",
        );
    });
});
