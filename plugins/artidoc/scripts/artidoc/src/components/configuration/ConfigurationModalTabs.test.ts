/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { describe, it, expect, beforeEach } from "vitest";
import { createGettext } from "vue3-gettext";
import ConfigurationModalTabs from "@/components/configuration/ConfigurationModalTabs.vue";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import {
    READONLY_FIELDS_SELECTION_TAB,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";
import type { Tracker } from "@/stores/configuration-store";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";

describe("ConfigurationModalTabs", () => {
    let current_tab: ConfigurationTab, selected_tracker: Tracker | null;

    beforeEach(() => {
        current_tab = TRACKER_SELECTION_TAB;
        selected_tracker = { id: 102 } as Tracker;
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(ConfigurationModalTabs, {
            props: {
                current_tab,
                selected_tracker,
            },
            global: {
                provide: {
                    [ARE_FIELDS_ENABLED.valueOf()]: true,
                },
                plugins: [createGettext({ silent: true })],
            },
        });

    it("When the current tab is the tracker selection tab, then it should be active", () => {
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
    });

    it("When the current tab is the fields selection tab, then it should be active", () => {
        current_tab = READONLY_FIELDS_SELECTION_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).toContain(
            "tlp-tab-active",
        );
    });

    it("When the user clicks the tracker selection tab, then it should emit a 'switch-configuration-tab' event", () => {
        current_tab = READONLY_FIELDS_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=tracker-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toStrictEqual([
            [TRACKER_SELECTION_TAB],
        ]);
    });

    it("Given that no tracker has been configured, When the user clicks the fields selection tab, then it should NOT emit a 'switch-configuration-tab' event", () => {
        selected_tracker = null;
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=fields-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toBeUndefined();
    });

    it("Given that a tracker has been configured, When the user clicks the fields selection tab, then it should emit a 'switch-configuration-tab' event", () => {
        selected_tracker = { id: 102 } as Tracker;
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=fields-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toStrictEqual([
            [READONLY_FIELDS_SELECTION_TAB],
        ]);
    });
});
