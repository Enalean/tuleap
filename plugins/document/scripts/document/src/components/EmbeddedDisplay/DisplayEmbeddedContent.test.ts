/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import type { Embedded, EmbeddedFileDisplayPreference } from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE, EMBEDDED_FILE_DISPLAY_NARROW } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { createRouter, createWebHistory } from "vue-router";
import { routes } from "../../router/router";

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return { autocomplete_users_for_select2: vi.fn() };
});

describe("DisplayEmbeddedContent", () => {
    function getWrapper(
        embedded_file: Embedded,
        content_to_display: string,
        specific_version_number: number | null,
        embedded_file_display_preference: EmbeddedFileDisplayPreference,
    ): VueWrapper<InstanceType<typeof DisplayEmbeddedContent>> {
        return shallowMount(DisplayEmbeddedContent, {
            props: {
                embedded_file,
                content_to_display,
                specific_version_number,
                embedded_file_display_preference,
            },
            global: {
                plugins: [router],
                ...getGlobalTestOptions({}),
                directives: {
                    "dompurify-html": vi.fn().mockImplementation(() => {
                        return content_to_display;
                    }),
                },
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    it(`renders an embedded document in narrow view`, () => {
        const wrapper = getWrapper(
            {
                id: 42,
                title: "My embedded content",
                embedded_file_properties: {
                    version_number: 666,
                    content: "My content",
                },
            } as Embedded,
            "My content",
            null,
            EMBEDDED_FILE_DISPLAY_NARROW,
        );

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toStrictEqual(["tlp-pane", "embedded-document", "narrow"]);
    });

    it(`renders an embedded document in large view`, () => {
        const wrapper = getWrapper(
            {
                id: 42,
                title: "My embedded content",
                embedded_file_properties: {
                    version_number: 666,
                    content: "My content",
                },
            } as Embedded,
            "My content",
            null,
            EMBEDDED_FILE_DISPLAY_LARGE,
        );

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toStrictEqual(["tlp-pane", "embedded-document"]);
    });
});
