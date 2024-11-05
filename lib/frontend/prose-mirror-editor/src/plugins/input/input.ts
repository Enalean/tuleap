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
import { DOMSerializer } from "prosemirror-model";
import { loadTooltips } from "@tuleap/tooltip";

export type PluginInput = Plugin;

export function initPluginInput(update_callback: (content: HTMLElement) => void): PluginInput {
    return new Plugin({
        view(view: EditorView): { update: (view: EditorView) => void } {
            loadTooltips(view.dom);

            const serializer = DOMSerializer.fromSchema(view.state.schema);

            return {
                update(view: EditorView): void {
                    loadTooltips(view.dom);

                    const serialized_content = serializer.serializeFragment(
                        view.state.doc.content,
                        { document },
                        document.createElement("div"),
                    );

                    if (!(serialized_content instanceof HTMLElement)) {
                        throw new Error("Unable to serialize the editor content");
                    }

                    update_callback(serialized_content);
                },
            };
        },
    });
}
