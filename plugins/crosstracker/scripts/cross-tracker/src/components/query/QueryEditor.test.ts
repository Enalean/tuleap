/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

const noop = (): void => {
    //Do nothing
};

describe("QueryEditor", () => {
    function instantiateComponent(tql_query: string): VueWrapper<InstanceType<typeof QueryEditor>> {
        return shallowMount(QueryEditor, {
            props: {
                tql_query,
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
            const state = { doc: "SELECT @id FROM project = 'self' WHERE @id > 1" };

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

    it("Updates the query when tql query is updated", () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("update"),
        );

        const tql_query_before_update =
            "SELECT @pretty_title FROM @project.name = 'projjjjjeeeeecctss' WHERE @id > 1818";

        const wrapper = instantiateComponent(tql_query_before_update);
        expect(wrapper.emitted()["update:tql_query"][0]).toStrictEqual([
            "SELECT @id FROM project = 'self' WHERE @id > 1",
        ]);
    });

    it(`Updates the query and emits an event when the form submit keybinding is run`, () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("submit"),
        );
        const tql_query_before_submit =
            "SELECT @pretty_title FROM @project.name = 'projjjjjeeeeecctss' WHERE @id > 1818";
        const wrapper = instantiateComponent(tql_query_before_submit);

        expect(wrapper.emitted()).toHaveProperty("trigger-search");
    });
});
