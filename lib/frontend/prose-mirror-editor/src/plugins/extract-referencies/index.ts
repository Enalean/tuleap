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

import { Plugin } from "prosemirror-state";
import type { DecorationSource, EditorView, DecorationSet } from "prosemirror-view";
import { loadCrossReferences } from "./cross-ref-loader";
import type { CrossReference } from "./reference-extractor";
import { CrossReferencesDecorator } from "./cross-references-decorator";
import { UpdateCrossReferenceHandler } from "./update/UpdateCrossReferenceHandler";
import { UpdatedCrossReferenceInTransactionFinder } from "./update/UpdatedCrossReferenceInTransactionFinder";
import { CrossReferenceDecorationFinder } from "./update/CrossReferenceDecorationFinder";
import { CrossReferenceDecorationReplacer } from "./update/CrossReferenceDecorationReplacer";
import { ReferencePositionComputer } from "./helpers/ReferencePositionComputer";
import { PositionsInDescendentsFinder } from "./helpers/DescendentsContainingReferenceFinder";
import { ParentNodeRetriever } from "./helpers/ParentNodeRetriever";
import { ContextLengthComputer } from "./helpers/ContextLengthPositionComputer";
import { ReferenceWithContextGetter } from "./helpers/ReferenceWithContextGetter";

let editor_view: EditorView;

export function initPluginTransformInput(
    project_id: number,
    references: Array<CrossReference>,
): Plugin {
    let decorations: DecorationSet;
    const link_decorator = CrossReferencesDecorator(
        ReferencePositionComputer(
            ParentNodeRetriever(),
            ContextLengthComputer(),
            ReferenceWithContextGetter(),
        ),
        PositionsInDescendentsFinder(),
    );

    return new Plugin({
        props: {
            decorations(state): DecorationSource {
                return this.getState(state);
            },
        },
        state: {
            init: (config, state): DecorationSet => {
                return link_decorator.decorateCrossReference(state.doc, references);
            },
            apply: (tr, decoration_set: DecorationSet): DecorationSet => {
                decorations = decoration_set;

                if (tr.docChanged) {
                    loadCrossReferences(tr.doc, project_id, editor_view, link_decorator);
                }

                const decorations_promises = tr.getMeta("asyncDecorations");
                if (decorations_promises === undefined) {
                    return decoration_set;
                }

                return decorations_promises;
            },
        },
        appendTransaction: (transactions, old_state) =>
            UpdateCrossReferenceHandler(
                CrossReferenceDecorationFinder(decorations),
                CrossReferenceDecorationReplacer(old_state),
            ).handle(UpdatedCrossReferenceInTransactionFinder().find(transactions)),
        view: function (): { update(view: EditorView): void } {
            return {
                update(view: EditorView): void {
                    editor_view = view;
                },
            };
        },
    });
}
