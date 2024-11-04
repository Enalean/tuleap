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
import { loadTooltips } from "@tuleap/tooltip";

export function initPluginInput(update_callback: (newTextContent: string) => void): Plugin {
    return new Plugin({
        view(view: EditorView): { update: (view: EditorView) => void } {
            loadTooltips(view.dom);

            return {
                update(view: EditorView): void {
                    loadTooltips(view.dom);
                    update_callback(view.dom.innerHTML);
                },
            };
        },
    });
}
