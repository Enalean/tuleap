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

import { MenuItem } from "prosemirror-menu";
import type { GettextProvider } from "@tuleap/gettext";
import { getQuoteCommand } from "./quote-command";
import { isSelectionABlockQuote } from "./is-selection-a-block-quote";

export function getQuoteMenuItem(gettext_provider: GettextProvider): MenuItem {
    return new MenuItem({
        title: gettext_provider.gettext("Wrap in block quote `Ctrl->`"),
        active: (state) => isSelectionABlockQuote(state),
        render: (): HTMLElement => {
            const icon = document.createElement("i");
            icon.classList.add("fa-solid", "fa-quote-left", "ProseMirror-icon");
            return icon;
        },
        run: getQuoteCommand(),
    });
}
