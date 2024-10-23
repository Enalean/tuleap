/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import QueryEditor from "./QueryEditor.vue";
import { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";

const noop = (): void => {
    //Do nothing
};

describe("QueryEditor", () => {
    function instantiateComponent(
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof QueryEditor>> {
        return shallowMount(QueryEditor, {
            props: {
                writing_cross_tracker_report,
            },
            global: { ...getGlobalTestOptions() },
        });
    }

    it("Displays a code mirror integration", () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = "@title = 'foo'";

        const wrapper = instantiateComponent(writing_cross_tracker_report);
        expect(wrapper.vm.value).toBe(writing_cross_tracker_report.expert_query);
    });

    it("Update the report when query is updated", () => {
        vi.spyOn(document, "createRange").mockImplementation(() => {
            return {
                getBoundingClientRect: noop,
                setEnd: noop,
                setStart: noop,
                getClientRects: () => [],
            } as unknown as Range;
        });

        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = "@title = 'foo'";
        const wrapper = instantiateComponent(writing_cross_tracker_report);

        wrapper.vm.code_mirror_instance?.setValue("@title = 'bar'");
        expect(writing_cross_tracker_report.expert_query).toBe("@title = 'bar'");
    });
});
