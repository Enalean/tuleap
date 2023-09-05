/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import QuickLookFile from "./QuickLookFile.vue";
import { TYPE_FILE } from "../../constants";
import type { ItemFile, FileProperties } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("QuickLookFile", () => {
    it("renders quick look for file document with a CTA to download the file", () => {
        const item = {
            id: 42,
            title: "my document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                open_href: "",
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = shallowMount(QuickLookFile, {
            props: { item: item },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
    it("renders quick look for file document with a CTA to open the file", () => {
        const item = {
            id: 42,
            title: "my document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                open_href: "/path/to/open/119",
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = shallowMount(QuickLookFile, {
            props: { item: item },
            global: { ...getGlobalTestOptions({}) },
        });

        const cta = wrapper.find<HTMLAnchorElement>(
            "[data-test=document-quick-look-document-cta-open]",
        ).element;
        expect(cta.href).toContain("/path/to/open/119");
    });
});
