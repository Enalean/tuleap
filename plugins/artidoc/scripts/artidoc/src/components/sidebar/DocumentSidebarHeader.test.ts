/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { SidebarTab } from "@/components/sidebar/document-sidebar";
import { VERSIONS_TAB, TOC_TAB } from "@/components/sidebar/document-sidebar";
import DocumentSidebarHeader from "@/components/sidebar/DocumentSidebarHeader.vue";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";

describe("DocumentSidebarHeader", () => {
    let current_tab: SidebarTab;

    beforeEach(() => {
        current_tab = TOC_TAB;
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(DocumentSidebarHeader, {
            props: { current_tab },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [ARE_VERSIONS_DISPLAYED.valueOf()]: true,
                },
            },
        });
    };

    it("When the current tab is the TOC tab, then it should be active", () => {
        current_tab = TOC_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=toc-tab]").classes()).toContain("tlp-tab-active");
        expect(wrapper.get("[data-test=versions-tab]").classes()).not.toContain("tlp-tab-active");
    });

    it("When the current tab is the versions tab, then it should be active", () => {
        current_tab = VERSIONS_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=toc-tab]").classes()).not.toContain("tlp-tab-active");
        expect(wrapper.get("[data-test=versions-tab]").classes()).toContain("tlp-tab-active");
    });

    it("When the user clicks the TOC tab, then it should emit a 'switch-sidebar-tab' event", () => {
        current_tab = VERSIONS_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=toc-tab]").trigger("click");

        expect(wrapper.emitted("switch-sidebar-tab")).toStrictEqual([[TOC_TAB]]);
    });

    it("When the user clicks the versions tab, then it should emit a 'switch-sidebar-tab' event", () => {
        current_tab = TOC_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=versions-tab]").trigger("click");

        expect(wrapper.emitted("switch-sidebar-tab")).toStrictEqual([[VERSIONS_TAB]]);
    });
});
