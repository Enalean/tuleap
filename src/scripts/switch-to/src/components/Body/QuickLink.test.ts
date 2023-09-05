/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { ItemDefinition, QuickLink } from "../../type";
import { shallowMount } from "@vue/test-utils";
import { default as QuickLinkComponent } from "./QuickLink.vue";
import { useKeyboardNavigationStore } from "../../stores/keyboard-navigation";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("QuickLink", () => {
    it.each(["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"])(
        "Changes the focus with arrow key %s",
        async (key) => {
            const quick_link = { html_url: "/link" } as QuickLink;
            const item = {
                icon_name: "fa-columns",
                title: "Kanban",
                color_name: "lake-placid-blue",
                quick_links: [] as ReadonlyArray<QuickLink>,
                project: {
                    label: "Guinea Pig",
                },
            } as ItemDefinition;

            const wrapper = shallowMount(QuickLinkComponent, {
                props: {
                    link: quick_link,
                    item,
                    project: null,
                },
                global: getGlobalTestOptions(),
            });

            await wrapper.trigger("keydown", { key });

            expect(useKeyboardNavigationStore().changeFocusFromQuickLink).toHaveBeenCalledWith({
                item,
                quick_link,
                project: null,
                key,
            });
        },
    );
});
