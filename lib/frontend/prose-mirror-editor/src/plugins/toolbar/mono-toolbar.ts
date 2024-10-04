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

export function setupMonoToolbar(toolbar_bus: ToolbarBus): Plugin {
    return new Plugin({
        view(): PluginView {
            return {
                update: (view: EditorView): void => {
                    if (toolbar_bus.view) {
                        ToolbarActivator().activateToolbarItem(
                            toolbar_bus.view,
                            view.state,
                            IsMarkActiveChecker(),
                        );
                    }

                    toolbar_bus.setCurrentHandler({
                        toggleBold(): void {
                            MarkToggle().toggleMark(view, custom_schema.marks.strong);
                        },
                        toggleEmbedded(): void {
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
                    });
                },
            };
        },
    });
}
