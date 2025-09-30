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

import type { VueWrapper } from "@vue/test-utils";
import { describe, it, expect } from "vitest";
import type { Ref } from "vue";
import { ref } from "vue";
import DocumentSidebar from "@/components/sidebar/DocumentSidebar.vue";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";
import TableOfContents from "@/components/sidebar/toc/TableOfContents.vue";
import ArtidocVersions from "@/components/sidebar/versions/ArtidocVersions.vue";
import DocumentSidebarHeader from "@/components/sidebar/DocumentSidebarHeader.vue";
import { TOC_TAB, VERSIONS_TAB } from "@/components/sidebar/document-sidebar";
import process from "node:process";
import { REGISTER_VERSIONS_SHORTCUT_HANDLER } from "@/register-shortcut-handler-injection-keys";
import { noop } from "@/helpers/noop";

describe("DocumentSidebar", () => {
    function getWrapper(are_versions_displayed: Ref<boolean>): VueWrapper {
        return shallowMount(DocumentSidebar, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [ARE_VERSIONS_DISPLAYED.valueOf()]: are_versions_displayed,
                    [REGISTER_VERSIONS_SHORTCUT_HANDLER.valueOf()]: noop,
                },
            },
        });
    }
    it("by default the TOC tab is displayed", () => {
        const are_versions_displayed = ref(true);

        const wrapper = getWrapper(are_versions_displayed);

        expect(wrapper.findComponent(DocumentSidebarHeader).props("current_tab")).toBe(TOC_TAB);
        expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
        expect(wrapper.findComponent(ArtidocVersions).exists()).toBe(false);
    });

    it("when user click on Versions tab, then it is displayed", async () => {
        const are_versions_displayed = ref(true);

        const wrapper = getWrapper(are_versions_displayed);

        await wrapper
            .findComponent(DocumentSidebarHeader)
            .vm.$emit("switch-sidebar-tab", VERSIONS_TAB);

        expect(wrapper.findComponent(DocumentSidebarHeader).props("current_tab")).toBe(
            VERSIONS_TAB,
        );
        expect(wrapper.findComponent(TableOfContents).exists()).toBe(false);
        expect(wrapper.findComponent(ArtidocVersions).exists()).toBe(true);
    });

    it("when user click on Versions tab, then click on TOC tab, then the latter is displayed", async () => {
        const are_versions_displayed = ref(true);

        const wrapper = getWrapper(are_versions_displayed);

        await wrapper
            .findComponent(DocumentSidebarHeader)
            .vm.$emit("switch-sidebar-tab", VERSIONS_TAB);
        await wrapper.findComponent(DocumentSidebarHeader).vm.$emit("switch-sidebar-tab", TOC_TAB);

        expect(wrapper.findComponent(DocumentSidebarHeader).props("current_tab")).toBe(TOC_TAB);
        expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
        expect(wrapper.findComponent(ArtidocVersions).exists()).toBe(false);
    });

    it("when user click on Versions tab, then decides to not display at all the versions in the configuration, then the TOC is displayed", async () => {
        const are_versions_displayed = ref(true);

        const wrapper = getWrapper(are_versions_displayed);

        await wrapper
            .findComponent(DocumentSidebarHeader)
            .vm.$emit("switch-sidebar-tab", VERSIONS_TAB);

        are_versions_displayed.value = false;
        await new Promise(process.nextTick);

        expect(wrapper.findComponent(DocumentSidebarHeader).props("current_tab")).toBe(TOC_TAB);
        expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
        expect(wrapper.findComponent(ArtidocVersions).exists()).toBe(false);
    });
});
