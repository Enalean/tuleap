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

import { addShortcutsGroup, removeShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
export type { Shortcut, ShortcutsGroup };

import type { GettextProvider } from "./type";

import { createCampaignsListShortcutsGroup } from "./campaigns-list-shortcuts";
import { createTestsListNavigation } from "./campaign/navigation-in-tests-list";
import { createCampaignShortcutsGroup } from "./campaign/campaign-shortcuts";
import { createTestExecutionShortcutsGroup } from "./campaign/test-execution-shortcuts";

export class KeyboardShortcuts {
    private readonly test_execution_shortcuts_group: ShortcutsGroup;
    private readonly campaign_shortcuts_group: ShortcutsGroup;
    private readonly tests_list_navigation_shortcuts_group: ShortcutsGroup;
    private readonly campaigns_list_shortcuts_group: ShortcutsGroup;

    constructor(gettextCatalog: GettextProvider) {
        this.test_execution_shortcuts_group = createTestExecutionShortcutsGroup(
            document,
            gettextCatalog,
        );
        this.campaign_shortcuts_group = createCampaignShortcutsGroup(document, gettextCatalog);
        this.tests_list_navigation_shortcuts_group = createTestsListNavigation(
            document,
            gettextCatalog,
        );
        this.campaigns_list_shortcuts_group = createCampaignsListShortcutsGroup(
            document,
            gettextCatalog,
        );
    }

    setCampaignPageShortcuts(): void {
        removeShortcutsGroup(document, this.campaigns_list_shortcuts_group);

        const shortcuts_groups = [
            this.test_execution_shortcuts_group,
            this.campaign_shortcuts_group,
            this.tests_list_navigation_shortcuts_group,
        ];
        shortcuts_groups.forEach((shortcut_group) => {
            addShortcutsGroup(document, shortcut_group);
        });
    }

    setCampaignsListPageShortcuts(): void {
        const shortcuts_groups = [
            this.test_execution_shortcuts_group,
            this.campaign_shortcuts_group,
            this.tests_list_navigation_shortcuts_group,
        ];
        shortcuts_groups.forEach((shortcut_group) => {
            removeShortcutsGroup(document, shortcut_group);
        });

        addShortcutsGroup(document, this.campaigns_list_shortcuts_group);
    }
}
