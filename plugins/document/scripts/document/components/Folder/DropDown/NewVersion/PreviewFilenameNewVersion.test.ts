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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ConfigurationState } from "../../../../store/configuration";
import type { DefaultFileNewVersionItem, NewVersion } from "../../../../type";
import PreviewFilenameNewVersion from "./PreviewFilenameNewVersion.vue";

describe("PreviewFilenameNewVersion", () => {
    function getWrapper(
        item: DefaultFileNewVersionItem,
        version: NewVersion,
        configuration: ConfigurationState
    ): Wrapper<PreviewFilenameNewVersion> {
        return shallowMount(PreviewFilenameNewVersion, {
            localVue,
            propsData: {
                item,
                version,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration,
                    },
                }),
            },
        });
    }

    it.each([
        ["enforced", false],
        ["a file", true],
    ])(
        `"should display nothing if filename pattern is not %s"`,
        (string_display_condition, is_filename_pattern_enforced) => {
            const wrapper = getWrapper(
                {} as DefaultFileNewVersionItem,
                {} as NewVersion,
                { is_filename_pattern_enforced, filename_pattern: "" } as ConfigurationState
            );

            expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
        }
    );

    it("should update the preview according to item and version's values", async () => {
        const item = {
            id: 42,
            type: "file",
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileNewVersionItem;

        const version = { title: "wololo", changelog: "" } as NewVersion;

        const wrapper = getWrapper(item, version, {
            is_filename_pattern_enforced: true,
            // eslint-disable-next-line no-template-curly-in-string
            filename_pattern: "${ID}-toto-${TITLE}-${STATUS}-${VERSION_NAME}",
        } as ConfigurationState);

        expect(wrapper.find("[data-test=preview]").text()).toBe(
            "42-toto-Lorem ipsum-approved-wololo.json"
        );

        item.status = "rejected";
        version.title = "nope";
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=preview]").text()).toBe(
            "42-toto-Lorem ipsum-rejected-nope.json"
        );
    });
});
