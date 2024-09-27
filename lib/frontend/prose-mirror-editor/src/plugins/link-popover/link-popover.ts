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
import type { EditorView } from "prosemirror-view";
import type { GetText } from "@tuleap/gettext";
import { LinkPopoverInserter } from "./helper/LinkPopoverInserter";
import { DOMNodeAtPositionFinder } from "./helper/DOMNodeAtPositionFinder";
import { CrossReferenceHTMLElementDetector } from "./helper/CrossReferenceNodeDetector";
import { CrossReferenceUrlExtractor } from "./helper/CrossReferenceUrlExtractor";
import { LinkUrlExtractor } from "./helper/LinkUrlExtractor";
import { LinkNodeDetector } from "./helper/LinkNodeDetector";
import { EditorNodeAtPositionFinder } from "./helper/EditorNodeAtPositionFinder";
import { EmptySelectionChecker } from "./helper/EmptySelectionChecker";
import { RemoveLinkCallbackBuilder } from "./helper/RemoveLinkCallbackBuilder";
import { EditLinkCallbackBuilder } from "./helper/EditLinkCallbackBuilder";

export const initLinkPopoverPlugin = (
    doc: Document,
    gettext_provider: GetText,
    editor_id: string,
): Plugin => {
    return new Plugin({
        props: {
            handleClick: (view: EditorView, position: number): boolean =>
                LinkPopoverInserter(
                    doc,
                    gettext_provider,
                    editor_id,
                    EmptySelectionChecker(view.state.selection),
                    DOMNodeAtPositionFinder(view),
                    CrossReferenceHTMLElementDetector(),
                    CrossReferenceUrlExtractor(),
                    LinkUrlExtractor(
                        EditorNodeAtPositionFinder(view.state),
                        LinkNodeDetector(view.state),
                    ),
                    RemoveLinkCallbackBuilder(view.state, view.dispatch),
                    EditLinkCallbackBuilder(view),
                ).insertPopover(position),
        },
    });
};
