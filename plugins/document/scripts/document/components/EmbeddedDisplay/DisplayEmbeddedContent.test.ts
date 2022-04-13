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
import type { RootState } from "../../type";
import type { PreferenciesState } from "../../store/preferencies/preferencies-default-state";

describe("DisplayEmbeddedContent", () => {
    function getWrapper(state: RootState): Wrapper<DisplayEmbeddedContent> {
        return shallowMount(DisplayEmbeddedContent, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
        });
    }

    it(`renders an embedded document in narrow view`, () => {
        const wrapper = getWrapper({
            currently_previewed_item: {
                id: 42,
                title: "My embedded content",
                embedded_file_properties: {
                    content: "My content",
                },
            },
            preferencies: {
                is_embedded_in_large_view: false,
            } as PreferenciesState,
        } as unknown as RootState);

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document", "narrow"]);
    });

    it(`renders an embedded document in large view`, () => {
        const wrapper = getWrapper({
            currently_previewed_item: {
                id: 42,
                title: "My embedded content",
                embedded_file_properties: {
                    content: "My content",
                },
            },
            preferencies: {
                is_embedded_in_large_view: true,
            } as PreferenciesState,
        } as unknown as RootState);

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document"]);
    });

    it(`does not throw error if embedded_file_properties key is missing`, () => {
        const wrapper = getWrapper({
            currently_previewed_item: {
                id: 42,
                title: "My embedded content",
            },
            preferencies: {
                is_embedded_in_large_view: true,
            } as PreferenciesState,
        } as unknown as RootState);

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document"]);
    });
});
