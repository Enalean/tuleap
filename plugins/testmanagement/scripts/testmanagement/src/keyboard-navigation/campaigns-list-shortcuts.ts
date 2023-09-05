/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { Shortcut, ShortcutsGroup } from "./setup-shortcuts";
import type { GettextProvider } from "./type";

import { clickOnElement, focusElement } from "./shortcuts-handles/trigger-datashortcut-element";

export function createCampaignsListShortcutsGroup(
    doc: Document,
    gettextCatalog: GettextProvider,
): ShortcutsGroup {
    const focus_search_field: Shortcut = {
        keyboard_inputs: "f",
        description: gettextCatalog.getString("Set focus in search campaigns field"),
        handle: (): void => {
            focusElement(doc, "[data-shortcut-search-filter]");
        },
    };

    const toggle_closed_campaigns: Shortcut = {
        keyboard_inputs: "a",
        description: gettextCatalog.getString("Toggle closed campaigns"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-closed-campaigns]");
        },
    };

    return {
        title: gettextCatalog.getString("Campaigns list"),
        shortcuts: [focus_search_field, toggle_closed_campaigns],
    };
}
