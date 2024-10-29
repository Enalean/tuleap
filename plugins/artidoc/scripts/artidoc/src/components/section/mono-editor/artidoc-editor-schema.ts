/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { DOMOutputSpec, EditorNodes } from "@tuleap/prose-mirror-editor";
import { prosemirror_nodes, STRUCTURE_BLOCK_GROUP } from "@tuleap/prose-mirror-editor";

export const artidoc_editor_schema: EditorNodes = {
    artidoc_section_title: {
        content: "text*",
        defining: true,
        selectable: false,
        draggable: false,
        isolating: true,
        disableDropCursor: true,
        marks: "",
        toDOM(): DOMOutputSpec {
            return ["artidoc-section-title", 0];
        },
        parseDOM: [{ tag: "artidoc-section-title" }],
        group: STRUCTURE_BLOCK_GROUP,
    },
    artidoc_section_description: {
        content: "block+",
        defining: true,
        selectable: false,
        draggable: false,
        isolating: true,
        toDOM(): DOMOutputSpec {
            return ["artidoc-section-description", 0];
        },
        parseDOM: [{ tag: "artidoc-section-description" }],
        group: STRUCTURE_BLOCK_GROUP,
    },
    artidoc_section: {
        content: "artidoc_section_title artidoc_section_description",
        defining: true,
        selectable: false,
        draggable: false,
        isolating: true,
        toDOM(): DOMOutputSpec {
            return ["artidoc-section", 0];
        },
        parseDOM: [{ tag: "artidoc-section" }],
        group: STRUCTURE_BLOCK_GROUP,
    },
    ...prosemirror_nodes,
    doc: {
        content: "artidoc_section",
    },
};
