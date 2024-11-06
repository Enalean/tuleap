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
import type { TQLCodeMirrorEditor } from "@tuleap/plugin-tracker-tql-codemirror";
import * as TQLEditor from "@tuleap/plugin-tracker-tql-codemirror";
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

    function buildFakeEditorImplementation(
        test: "submit" | "update",
    ): typeof TQLEditor.buildTQLEditor {
        const doc = document.implementation.createHTMLDocument();
        return (
            _definition,
            _placeholder,
            _initial_value,
            submitCallback,
            updateCallback,
        ): TQLCodeMirrorEditor => {
            const dom = doc.createElement("div");
            const state = { doc: "@title = 'bar'" };

            const editor = {
                dom,
                state,
                focus: noop,
                dispatch: noop,
            } as unknown as TQLCodeMirrorEditor;
            if (test === "submit") {
                submitCallback(editor);
            } else {
                updateCallback?.(editor);
            }
            return editor;
        };
    }

    it("Updates the report when query is updated", () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("update"),
        );

        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = "@title = 'foo'";
        instantiateComponent(writing_cross_tracker_report);

        expect(writing_cross_tracker_report.expert_query).toBe("@title = 'bar'");
    });

    it(`Updates the report and emits an event when the form submit keybinding is run`, () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("submit"),
        );

        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = "@title = 'foo'";
        const wrapper = instantiateComponent(writing_cross_tracker_report);

        expect(writing_cross_tracker_report.expert_query).toBe("@title = 'bar'");
        expect(wrapper.emitted()).toHaveProperty("trigger-search");
    });
});
