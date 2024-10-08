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
 *
 */

import type { Node } from "prosemirror-model";
import type { EditorView } from "prosemirror-view";
import { type CrossReference, getNodeText } from "./reference-extractor";
import type { DecorateCrossReference } from "./cross-references-decorator";

export function loadCrossReferences(
    tree: Node,
    project_id: number,
    editor_view: EditorView,
    link_decorator: DecorateCrossReference,
): void {
    getNodeText(tree.toString(), project_id).match(
        (references: Array<CrossReference>) => {
            if (references.length === 0) {
                return;
            }

            editor_view.dispatch(
                editor_view.state.tr.setMeta(
                    "asyncDecorations",
                    link_decorator.decorateCrossReference(tree, references),
                ),
            );
        },
        () => {
            throw new Error("error when handling refs");
        },
    );
}
