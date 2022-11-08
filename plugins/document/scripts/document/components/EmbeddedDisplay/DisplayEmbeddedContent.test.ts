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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import type { Embedded, RootState } from "../../type";
import type { PreferenciesState } from "../../store/preferencies/preferencies-default-state";

describe("DisplayEmbeddedContent", () => {
    function getWrapper(
        embedded_file: Embedded,
        content_to_display: string,
        specific_version_number: number | null,
        state: RootState
    ): Wrapper<DisplayEmbeddedContent> {
        return shallowMount(DisplayEmbeddedContent, {
            localVue,
            propsData: {
                embedded_file,
                content_to_display,
                specific_version_number,
            },
            mocks: {
                $store: createStoreMock({
                    state,
                }),
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
            {
                preferencies: {
                    is_embedded_in_large_view: false,
                } as PreferenciesState,
            } as unknown as RootState
        );

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toStrictEqual(["tlp-pane", "embedded-document", "narrow"]);
        expect(wrapper.find("[data-test=content]").text()).toBe("My content");
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
            {
                preferencies: {
                    is_embedded_in_large_view: true,
                } as PreferenciesState,
            } as unknown as RootState
        );

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toStrictEqual(["tlp-pane", "embedded-document"]);
    });

    it("should be able to display an old version", () => {
        const wrapper = getWrapper(
            {
                id: 42,
                title: "My embedded content",
                embedded_file_properties: {
                    version_number: 666,
                    content: "Latest content",
                },
            } as Embedded,
            "A very old content",
            42,
            {
                preferencies: {
                    is_embedded_in_large_view: true,
                } as PreferenciesState,
            } as unknown as RootState
        );

        expect(wrapper.find("[data-test=warning]").exists()).toBe(true);
        expect(wrapper.find("[data-test=content]").text()).toBe("A very old content");
    });
});
