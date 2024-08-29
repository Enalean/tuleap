/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { updateHeadingDropdownTitle } from "./text-style";
import type { EditorState } from "prosemirror-state";
import type { GetText } from "@tuleap/gettext";

const gettext_provider: GetText = {
    gettext(value: string): string {
        return value;
    },
} as GetText;

describe("text-style", () => {
    describe("updateHeadingDropdownTitle", () => {
        let doc: Document;
        let dropdown: Element | null;
        const state = {} as unknown as EditorState;

        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();
            dropdown = doc.createElement("div");
            dropdown.classList.add("heading_class");
            dropdown.textContent = "old content";
            doc.body.appendChild(dropdown);
        });
        describe("when the selected node is a paragraph", () => {
            it("should not update heading dropdown title to paragraph", () => {
                const mock_get_selected_node = vi
                    .fn()
                    .mockReturnValue({ node_type: "paragraph", node_level: 1 });

                expect(dropdown).toBeDefined();
                expect(dropdown?.textContent).toBe("old content");

                updateHeadingDropdownTitle(state, dropdown, gettext_provider, {
                    get_selected_node: mock_get_selected_node,
                });

                expect(dropdown?.textContent).toBe("paragraph");
            });
        });
        describe("when the selected node is a heading 3", () => {
            it("should update heading dropdown title to title 3", () => {
                const mock_get_selected_node = vi
                    .fn()
                    .mockReturnValue({ node_type: "heading", node_level: 3 });

                expect(dropdown).toBeDefined();
                expect(dropdown?.textContent).toBe("old content");

                updateHeadingDropdownTitle(state, dropdown, gettext_provider, {
                    get_selected_node: mock_get_selected_node,
                });

                expect(dropdown?.textContent).toBe("title 3");
            });
        });
        describe("when the selected node is unknown", () => {
            it("should not update heading dropdown title", () => {
                const mock_get_selected_node = vi
                    .fn()
                    .mockReturnValue({ node_type: "unknown type", node_level: 0 });

                expect(dropdown).toBeDefined();
                expect(dropdown?.textContent).toBe("old content");

                updateHeadingDropdownTitle(state, dropdown, gettext_provider, {
                    get_selected_node: mock_get_selected_node,
                });

                expect(dropdown?.textContent).toBe("old content");
            });
        });
    });
});
