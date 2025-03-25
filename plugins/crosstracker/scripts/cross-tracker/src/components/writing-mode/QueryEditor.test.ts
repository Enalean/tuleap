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
import type { Query } from "../../type";

const noop = (): void => {
    //Do nothing
};

describe("QueryEditor", () => {
    function getWrapper(writing_query: Query): VueWrapper<InstanceType<typeof QueryEditor>> {
        return shallowMount(QueryEditor, {
            props: { writing_query },
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

    it("Updates the query when query is updated", () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("update"),
        );

        const wrapper = getWrapper({
            id: "",
            tql_query: "@title = 'foo'",
            title: "",
            description: "",
            is_default: false,
        });

        expect(wrapper.vm.tql_query).toBe("@title = 'bar'");
    });

    it(`Updates the query and emits an event when the form submit keybinding is run`, () => {
        vi.spyOn(TQLEditor, "buildTQLEditor").mockImplementation(
            buildFakeEditorImplementation("submit"),
        );

        const wrapper = getWrapper({
            id: "",
            tql_query: "@title = 'foo'",
            title: "",
            description: "",
            is_default: false,
        });

        expect(wrapper.vm.tql_query).toBe("@title = 'bar'");
        expect(wrapper.emitted()).toHaveProperty("trigger-search");
    });
});
