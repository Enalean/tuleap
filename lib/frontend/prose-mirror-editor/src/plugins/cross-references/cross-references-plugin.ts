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

import type { PluginView, Transaction } from "prosemirror-state";
import { Plugin, PluginKey } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import type { Slice } from "prosemirror-model";
import { EditorNodeAtPositionFinder } from "../../helpers/EditorNodeAtPositionFinder";
import { AllCrossReferencesLoader } from "./first-loading/AllCrossReferencesLoader";
import { MarkExtentsRetriever } from "./update/MarkExtentsRetriever";
import { UpdatedCrossReferenceHandler } from "./update/UpdatedCrossReferenceHandler";
import { UpdatedCrossReferenceInTransactionFinder } from "./update/UpdatedCrossReferenceInTransactionFinder";
import { PastedReferencesTransformer } from "./paste/PastedReferencesTransformer";
import { TextNodeWithReferencesSplitter } from "./paste/TextNodeWithReferencesSplitter";

export const CrossReferencesPlugin = (project_id: number): Plugin => {
    return new Plugin({
        key: new PluginKey("CrossReferencesPlugin"),
        view(view: EditorView): PluginView {
            view.dispatch(
                AllCrossReferencesLoader(view.state, project_id).loadAllCrossReferences(),
            );
            return {};
        },
        appendTransaction: (transactions, old_state): Transaction | null => {
            const updated_cross_reference =
                UpdatedCrossReferenceInTransactionFinder().find(transactions);
            if (!updated_cross_reference) {
                return null;
            }

            return UpdatedCrossReferenceHandler(
                MarkExtentsRetriever(EditorNodeAtPositionFinder(old_state)),
                project_id,
            ).handle(old_state, updated_cross_reference);
        },
        props: {
            transformPasted: (slice, view): Slice =>
                PastedReferencesTransformer(
                    TextNodeWithReferencesSplitter(view.state.schema, project_id),
                ).transformPastedCrossReferencesToMark(slice),
        },
    });
};
