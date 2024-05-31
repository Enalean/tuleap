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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import QueryEditor from "./QueryEditor.vue";
import WritingCrossTrackerReport from "./writing-cross-tracker-report";
import type { TQLCodeMirrorEditor } from "@tuleap/plugin-tracker-tql-codemirror";

type QueryEditorExposed = { value: string; code_mirror_instance: TQLCodeMirrorEditor };

const noop = (): void => {
    //Do nothing
};

describe("QueryEditor", () => {
    async function instantiateComponent(
        writingCrossTrackerReport: WritingCrossTrackerReport,
    ): Promise<Wrapper<Vue & QueryEditorExposed, Element>> {
        return shallowMount(QueryEditor, {
            propsData: {
                writingCrossTrackerReport,
            },
            localVue: await createCrossTrackerLocalVue(),
        });
    }

    it("Displays a code mirror integration", async () => {
        const writingCrossTrackerReport = new WritingCrossTrackerReport();
        writingCrossTrackerReport.expert_query = "@title = 'foo'";

        const wrapper = await instantiateComponent(writingCrossTrackerReport);
        expect(wrapper.vm.value).toBe(writingCrossTrackerReport.expert_query);
    });

    it("Update the report when query is updated", async () => {
        jest.spyOn(document, "createRange").mockImplementation(() => {
            return {
                getBoundingClientRect: noop,
                setEnd: noop,
                setStart: noop,
                getClientRects: () => [],
            } as unknown as Range;
        });

        const writingCrossTrackerReport = new WritingCrossTrackerReport();
        writingCrossTrackerReport.expert_query = "@title = 'foo'";
        const wrapper = await instantiateComponent(writingCrossTrackerReport);

        wrapper.vm.code_mirror_instance.setValue("@title = 'bar'");
        expect(writingCrossTrackerReport.expert_query).toBe("@title = 'bar'");
    });
});
