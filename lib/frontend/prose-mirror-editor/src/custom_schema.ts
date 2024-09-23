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

import { type DOMOutputSpec, type MarkSpec, Schema } from "prosemirror-model";
import { addListNodes } from "prosemirror-schema-list";
import { schema } from "prosemirror-schema-basic";

// hard_break is redefined here, because I can no longer write any text after creating one by node.create
// see https://discuss.prosemirror.net/t/solved-cant-type-after-hard-break/3752
const nodes = schema.spec.nodes.append({
    custom_hard_break: {
        inline: true,
        group: "inline",
        selectable: false,
        parseDOM: [{ tag: "br" }],
        toDOM() {
            return ["br"];
        },
    },
});

const subscript_mark_spec: MarkSpec = {
    parseDOM: [{ tag: "sub" }],
    toDOM(): DOMOutputSpec {
        return ["sub", 0];
    },
};
export const custom_schema: Schema = new Schema({
    nodes: addListNodes(nodes, "paragraph block*", "block"),
    marks: {
        ...schema.spec.marks.toObject(),
        subscript: subscript_mark_spec,
    },
});
