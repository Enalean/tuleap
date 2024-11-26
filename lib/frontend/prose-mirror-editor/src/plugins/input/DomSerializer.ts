/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { DOMOutputSpec, Fragment, Mark, Schema } from "prosemirror-model";
import { DOMSerializer } from "prosemirror-model";

export type SerializeDOM = {
    serializeDOM(content: Fragment): HTMLElement;
};

type MarksToDOM = Record<string, (mark: Mark, inline: boolean) => DOMOutputSpec>;

const buildMarksToDOMMapWithoutNotSerializableMarks = (schema: Schema): MarksToDOM => {
    const marks_to_DOM: MarksToDOM = {};
    for (const mark_name in schema.marks) {
        if (mark_name === schema.marks.async_cross_reference.name) {
            continue;
        }
        const toDOM = schema.marks[mark_name].spec.toDOM;
        if (toDOM) {
            marks_to_DOM[mark_name] = toDOM;
        }
    }
    return marks_to_DOM;
};

export const buildDOMSerializer = (schema: Schema): SerializeDOM => {
    const serializer = new DOMSerializer(
        DOMSerializer.nodesFromSchema(schema),
        buildMarksToDOMMapWithoutNotSerializableMarks(schema),
    );

    return {
        serializeDOM: (content): HTMLElement => {
            const serialized_content = serializer.serializeFragment(
                content,
                { document },
                document.createElement("div"),
            );

            if (!(serialized_content instanceof HTMLElement)) {
                throw new Error("Unable to serialize the editor content");
            }

            return serialized_content;
        },
    };
};
