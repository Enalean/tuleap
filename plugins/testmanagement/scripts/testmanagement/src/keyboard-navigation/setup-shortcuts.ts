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

import { addShortcutsGroup } from "@tuleap/keyboard-shortcuts";

import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
export type { Shortcut, ShortcutsGroup };

import type { GettextProvider } from "./type";

import { createCampaignsListShortcutsGroup } from "./campaigns-list-shortcuts";
import { createTestsListNavigation } from "./campaign/navigation-in-tests-list";
import { createCampaignShortcutsGroup } from "./campaign/campaign-shortcuts";
import { createTestExecutionShortcutsGroup } from "./campaign/test-execution-shortcuts";

export function setupTestManagementShortcuts(gettextCatalog: GettextProvider): void {
    const shortcuts_groups = [
        createTestExecutionShortcutsGroup(gettextCatalog),
        createCampaignShortcutsGroup(gettextCatalog),
        createTestsListNavigation(gettextCatalog),
        createCampaignsListShortcutsGroup(gettextCatalog),
    ];

    shortcuts_groups.forEach((group) => {
        addShortcutsGroup(document, group);
    });
}
