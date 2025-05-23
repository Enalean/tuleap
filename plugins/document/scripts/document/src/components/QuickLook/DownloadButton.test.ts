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

import { describe, expect, it } from "vitest";
import type { FileProperties, ItemFile } from "../../type";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DownloadButton from "./DownloadButton.vue";
import { TYPE_FILE } from "../../constants";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("DownloadButton", () => {
    function getWrapper(item: ItemFile): VueWrapper<InstanceType<typeof DownloadButton>> {
        return shallowMount(DownloadButton, {
            props: { item },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`Given file_properties is not set
        Then component is empty`, () => {
        const item = {
            id: 42,
            title: "my corrupted document",
            file_properties: null,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it(`Given file_properties is set
        Then component is not empty`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(wrapper.text()).toBe("Download");
    });
});
