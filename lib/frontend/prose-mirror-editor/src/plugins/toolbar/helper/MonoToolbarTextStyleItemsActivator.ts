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

import type { EditorState } from "prosemirror-state";
import type { RetrieveHeading } from "../text-style/HeadingInSelectionRetriever";
import type { ToolbarView } from "./toolbar-bus";
import type { DetectPreformattedTextInSelection } from "../text-style/PreformattedTextInSelectionDetector";
import type { DetectParagraphsInSelection } from "../text-style/ParagraphsInSelectionDetector";

export type ActivateMonoToolbarTextStyleItems = {
    activateTextStyleItems(toolbar_view: ToolbarView, state: EditorState): void;
};

export const MonoToolbarTextStyleItemsActivator = (
    retrieve_heading: RetrieveHeading,
    detect_formatted_text: DetectPreformattedTextInSelection,
    detect_paragraphs: DetectParagraphsInSelection,
): ActivateMonoToolbarTextStyleItems => ({
    activateTextStyleItems: (toolbar_view, state): void => {
        toolbar_view.activateHeading(
            retrieve_heading.retrieveHeadingInSelection(state.doc, state.selection),
        );

        toolbar_view.activatePlainText(
            detect_paragraphs.doesSelectionContainOnlyParagraphs(state.doc, state.selection),
        );

        toolbar_view.activatePreformattedText(
            detect_formatted_text.doesSelectionContainOnlyPreformattedText(
                state.doc,
                state.selection,
            ),
        );
    },
});
