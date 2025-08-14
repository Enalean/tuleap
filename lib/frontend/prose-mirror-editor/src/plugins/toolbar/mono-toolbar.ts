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

import type { EditorState, PluginView } from "prosemirror-state";
import { Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import type { ToolbarBus } from "./helper/toolbar-bus";
import { ToolbarActivator } from "./helper/MonoToolbarActionActivator";
import type { ActivateToolbar } from "./helper/MonoToolbarActionActivator";
import { IsMarkActiveChecker } from "./helper/IsMarkActiveChecker";
import { MarkToggle } from "./helper/MonoToolbarToggler";
import { getQuoteCommand } from "./quote";
import { LinkStateBuilder } from "./links/LinkStateBuilder";
import { LinkPropertiesExtractor } from "../../helpers/LinkPropertiesExtractor";
import { EditorNodeAtPositionFinder } from "../../helpers/EditorNodeAtPositionFinder";
import { LinkNodeDetector } from "../link-popover/helper/LinkNodeDetector";
import { replaceLinkNode } from "../../helpers/replace-link-node";
import { IsMarkTypeRepeatedInSelectionChecker } from "../../helpers/IsMarkTypeRepeatedInSelectionChecker";
import { removeSelectedLinks } from "../link-popover/helper/remove-selected-links";
import { ImageStateBuilder } from "./image/ImageStateBuilder";
import { CanInsertImageChecker } from "./image/CanInsertImageChecker";
import { ImageNodeInserter } from "./image/ImageNodeInserter";
import { ImageFromSelectionExtractor } from "./image/ImageFromSelectionExtractor";
import { ListNodeInserter } from "./list/ListInserter";
import { wrapInList, liftListItem } from "prosemirror-schema-list";
import {
    getFormattedTextCommand,
    getHeadingCommand,
    getPlainTextCommand,
} from "./text-style/transform-text";
import { HeadingInSelectionRetriever } from "./text-style/HeadingInSelectionRetriever";
import { MonoToolbarTextStyleItemsActivator } from "./helper/MonoToolbarTextStyleItemsActivator";
import { PreformattedTextInSelectionDetector } from "./text-style/PreformattedTextInSelectionDetector";
import { ParagraphsInSelectionDetector } from "./text-style/ParagraphsInSelectionDetector";
import { SelectedNodesHaveSameParentChecker } from "./text-style/SelectedNodesHaveSameParentChecker";
import { SingleListInSelectionDetector } from "./list/SingleListInSelectionDetector";
import { ListsInSelectionDetector } from "./list/ListsInSelectionDetector";
import { ListStateBuilder } from "./list/ListStateBuilder";
import type { EmojiProperties } from "../../types/internal-types";
import { EmojiNodeInserter } from "./emoji/EmojiNodeInserter";

const getToolbarActivator = (state: EditorState): ActivateToolbar => {
    const schema = state.schema;
    const check_same_parent = SelectedNodesHaveSameParentChecker();

    const multiple_lists_detector = ListsInSelectionDetector(schema);
    return ToolbarActivator(
        IsMarkActiveChecker(),
        LinkStateBuilder(
            IsMarkTypeRepeatedInSelectionChecker(),
            LinkPropertiesExtractor(EditorNodeAtPositionFinder(state), LinkNodeDetector(state)),
        ),
        ImageStateBuilder(
            CanInsertImageChecker(schema.nodes.image),
            ImageFromSelectionExtractor(EditorNodeAtPositionFinder(state)),
        ),
        ListStateBuilder(
            state,
            SingleListInSelectionDetector(schema.nodes.ordered_list),
            multiple_lists_detector,
        ),
        ListStateBuilder(
            state,
            SingleListInSelectionDetector(schema.nodes.bullet_list),
            multiple_lists_detector,
        ),
        MonoToolbarTextStyleItemsActivator(
            HeadingInSelectionRetriever(check_same_parent, schema.nodes.heading),
            PreformattedTextInSelectionDetector(check_same_parent),
            ParagraphsInSelectionDetector(schema),
        ),
    );
};

export function setupMonoToolbar(toolbar_bus: ToolbarBus): Plugin {
    return new Plugin({
        view(): PluginView {
            return {
                update: (view: EditorView): void => {
                    const schema = view.state.schema;
                    const toolbar_activator = getToolbarActivator(view.state);

                    toolbar_activator.activateToolbarItem(toolbar_bus.view, view.state);

                    toolbar_bus.setCurrentHandler({
                        toggleBold(): void {
                            MarkToggle().toggleMark(view, schema.marks.strong);
                            view.focus();
                        },
                        toggleItalic(): void {
                            MarkToggle().toggleMark(view, schema.marks.em);
                            view.focus();
                        },
                        toggleCode(): void {
                            MarkToggle().toggleMark(view, schema.marks.code);
                            view.focus();
                        },
                        toggleQuote(): void {
                            getQuoteCommand()(view.state, view.dispatch);
                            view.focus();
                        },
                        toggleSubscript(): void {
                            MarkToggle().toggleMark(view, schema.marks.subscript);
                            view.focus();
                        },
                        toggleSuperScript(): void {
                            MarkToggle().toggleMark(view, schema.marks.superscript);
                            view.focus();
                        },
                        applyLink(link): void {
                            replaceLinkNode(view, link);
                            view.focus();
                        },
                        applyUnlink(): void {
                            removeSelectedLinks(view.state, view.dispatch);
                            view.focus();
                        },
                        applyImage(image): void {
                            ImageNodeInserter(view.state, view.dispatch).insertImage(image);
                            view.focus();
                        },
                        toggleOrderedList(): void {
                            ListNodeInserter(
                                view.state,
                                view.dispatch,
                                SingleListInSelectionDetector(schema.nodes.ordered_list),
                                liftListItem(schema.nodes.list_item),
                                wrapInList(schema.nodes.ordered_list),
                            ).insertList();
                            view.focus();
                        },
                        toggleBulletList(): void {
                            ListNodeInserter(
                                view.state,
                                view.dispatch,
                                SingleListInSelectionDetector(schema.nodes.bullet_list),
                                liftListItem(schema.nodes.list_item),
                                wrapInList(schema.nodes.bullet_list),
                            ).insertList();
                            view.focus();
                        },
                        toggleHeading(heading): void {
                            getHeadingCommand(heading.level)(view.state, view.dispatch);
                            view.focus();
                        },
                        toggleSubtitle(): void {
                            getHeadingCommand(1)(view.state, view.dispatch);
                            view.focus();
                        },
                        togglePlainText(): void {
                            getPlainTextCommand(schema.nodes.paragraph)(view.state, view.dispatch);
                            view.focus();
                        },
                        togglePreformattedText(): void {
                            getFormattedTextCommand(schema.nodes.code_block)(
                                view.state,
                                view.dispatch,
                            );
                            view.focus();
                        },
                        applyEmoji(emoji: EmojiProperties): void {
                            EmojiNodeInserter(view.state, view.dispatch).insertEmoji(emoji);
                            view.focus();
                        },
                        focus(): void {
                            view.focus();
                        },
                    });
                },
            };
        },
    });
}
