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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import PreviewFilenameProperty from "./PreviewFilenameProperty.vue";
import type { ConfigurationState } from "../../../store/configuration";
import type { DefaultFileItem } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("PreviewFilenameProperty", () => {
    function getWrapper(
        item: DefaultFileItem,
        configuration: ConfigurationState,
    ): VueWrapper<InstanceType<typeof PreviewFilenameProperty>> {
        return shallowMount(PreviewFilenameProperty, {
            props: {
                item,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: configuration as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
            slots: {
                default: "Lorem ipsum",
            },
        });
    }

    it("should display nothing if filename pattern is not enforced", () => {
        const item = {
            id: 42,
            type: "file",
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileItem;

        const wrapper = getWrapper(item, {
            is_filename_pattern_enforced: false,
        } as ConfigurationState);

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it("should display nothing if item is not a file is not enforced", () => {
        const item = {} as DefaultFileItem;

        const wrapper = getWrapper(item, {
            is_filename_pattern_enforced: true,
        } as ConfigurationState);

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it("should display the preview", () => {
        const item = {
            id: 42,
            type: "file",
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileItem;

        const wrapper = getWrapper(item, {
            is_filename_pattern_enforced: true,
        } as ConfigurationState);

        expect(wrapper.find("[data-test=preview]").text()).toBe("Lorem ipsum");
    });
});
