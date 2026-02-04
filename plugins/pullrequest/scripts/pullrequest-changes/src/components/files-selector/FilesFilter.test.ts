/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { Lazybox } from "@tuleap/lazybox";
import { Option } from "@tuleap/option";
import type { FilesFilter } from "./FilesFilter";
import { getFilesFilter } from "./FilesFilter";
import { FILE_STATUS_MODIFIED } from "../../api/rest-querier";

const readme_markdown = {
    is_disabled: false,
    value: {
        path: "README.md",
        status: FILE_STATUS_MODIFIED,
        lines_added: Option.fromValue("10"),
        lines_removed: Option.fromValue("0"),
    },
};
const index_js = {
    is_disabled: false,
    value: {
        path: "index.js",
        status: FILE_STATUS_MODIFIED,
        lines_added: Option.fromValue("2"),
        lines_removed: Option.fromValue("5"),
    },
};
const type_ts = {
    is_disabled: false,
    value: {
        path: "types.d.ts",
        status: FILE_STATUS_MODIFIED,
        lines_added: Option.fromValue("10"),
        lines_removed: Option.fromValue("1"),
    },
};
const items = [readme_markdown, index_js, type_ts];
const group = {
    label: "Files",
    empty_message: "",
    is_loading: false,
    items,
    footer_message: "",
};

describe("FilesFilter", () => {
    let filter: FilesFilter, lazybox: Lazybox;

    beforeEach(() => {
        filter = getFilesFilter(group, items);
        lazybox = {
            replaceDropdownContent: vi.fn(),
        } as unknown as Lazybox;
    });

    it("When the query contains only spaces, then it should not filter the items", () => {
        filter.filterFiles(lazybox, "");

        expect(lazybox.replaceDropdownContent).toHaveBeenCalledOnce();
        expect(lazybox.replaceDropdownContent).toHaveBeenCalledWith([
            {
                ...group,
                items,
            },
        ]);
    });

    it("When nothing matches the query, then all the items should be filtered out", () => {
        filter.filterFiles(lazybox, "banana.exe");

        expect(lazybox.replaceDropdownContent).toHaveBeenCalledOnce();
        expect(lazybox.replaceDropdownContent).toHaveBeenCalledWith([
            {
                ...group,
                items: [],
            },
        ]);
    });

    it("Given one search term, Then it should filter the items using it", () => {
        filter.filterFiles(lazybox, "README");

        expect(lazybox.replaceDropdownContent).toHaveBeenCalledOnce();
        expect(lazybox.replaceDropdownContent).toHaveBeenCalledWith([
            {
                ...group,
                items: [readme_markdown],
            },
        ]);
    });
});
