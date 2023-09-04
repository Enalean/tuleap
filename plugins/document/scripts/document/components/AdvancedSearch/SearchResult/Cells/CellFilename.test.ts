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

import { shallowMount } from "@vue/test-utils";
import type { FileProperties, ItemSearchResult } from "../../../../type";
import CellFilename from "./CellFilename.vue";

describe("CellFilename", () => {
    it("should display the filename of a File document", () => {
        const wrapper = shallowMount(CellFilename, {
            props: {
                item: {
                    id: 123,
                    type: "file",
                    title: "Lorem",
                    file_properties: {
                        file_name: "doc-spec.html",
                        file_type: "text/html",
                        download_href: "/path/to/file",
                    } as FileProperties,
                } as unknown as ItemSearchResult,
            },
        });

        expect(wrapper.text()).toBe("doc-spec.html");
    });

    it.each(["folder", "link", "wiki", "embedded", "empty"])(
        "should display empty string if document is not a File",
        (type: string): void => {
            const wrapper = shallowMount(CellFilename, {
                props: {
                    item: {
                        id: 123,
                        type,
                        title: "Lorem",
                    } as unknown as ItemSearchResult,
                },
            });

            expect(wrapper.text()).toBe("");
        },
    );
});
