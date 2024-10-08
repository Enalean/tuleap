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

import type { PluginView } from "prosemirror-state";
import { Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import type { ToolbarBus } from "./helper/toolbar-bus";
import { ToolbarActivator } from "./helper/MonoToolbarActionActivator";
import { IsMarkActiveChecker } from "./helper/IsMarkActiveChecker";
import { MarkToggle } from "./helper/MonoToolbarToggler";
import { custom_schema } from "../../custom_schema";
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
import { ListStateBuilder } from "./list/ListStateBuilder";
import { IsSelectionAListWithTypeChecker } from "./list/IsSelectionAListWithTypeChecker";
import { ListNodeInserter } from "./list/ListInserter";
import { IsSelectionAListChecker } from "./list/IsListChecker";
import { lift } from "prosemirror-commands";
import { wrapInList } from "prosemirror-schema-list";

export function setupMonoToolbar(toolbar_bus: ToolbarBus): Plugin {
    return new Plugin({
        view(): PluginView {
            return {
                update: (view: EditorView): void => {
                    if (toolbar_bus.view) {
                        const toolbar_activator = ToolbarActivator(
                            IsMarkActiveChecker(),
                            LinkStateBuilder(
                                IsMarkTypeRepeatedInSelectionChecker(),
                                LinkPropertiesExtractor(
                                    EditorNodeAtPositionFinder(view.state),
                                    LinkNodeDetector(view.state),
                                ),
                            ),
                            ImageStateBuilder(
                                CanInsertImageChecker(),
                                ImageFromSelectionExtractor(EditorNodeAtPositionFinder(view.state)),
                            ),
                            ListStateBuilder(view.state, IsSelectionAListWithTypeChecker()),
                        );

                        toolbar_activator.activateToolbarItem(toolbar_bus.view, view.state);
                    }

                    toolbar_bus.setCurrentHandler({
                        toggleBold(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.strong);
                        },
                        toggleItalic(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.em);
                        },
                        toggleCode(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.code);
                        },
                        toggleQuote(): void {
                            getQuoteCommand()(view.state, view.dispatch);
                        },
                        toggleSubscript(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.subscript);
                        },
                        toggleSuperScript(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.superscript);
                        },
                        applyLink(link): void {
                            replaceLinkNode(view, link);
                        },
                        applyUnlink(): void {
                            removeSelectedLinks(view.state, view.dispatch);
                        },
                        applyImage(image): void {
                            ImageNodeInserter(view.state, view.dispatch).insertImage(image);
                        },
                        toggleOrderedList(): void {
                            ListNodeInserter(
                                view.state,
                                view.dispatch,
                                IsSelectionAListChecker(),
                                custom_schema.nodes.ordered_list,
                                lift,
                                wrapInList(custom_schema.nodes.ordered_list),
                            ).insertList();
                        },
                        toggleBulletList(): void {
                            ListNodeInserter(
                                view.state,
                                view.dispatch,
                                IsSelectionAListChecker(),
                                custom_schema.nodes.bullet_list,
                                lift,
                                wrapInList(custom_schema.nodes.bullet_list),
                            ).insertList();
                        },
                    });
                },
            };
        },
    });
}
