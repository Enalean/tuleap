/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { GlobalExportProperties } from "../type";

const downloadXLSXDocument = jest.fn();
jest.mock("../export-document", () => {
    return {
        downloadXLSXDocument: downloadXLSXDocument,
    };
});

import Main from "./Main.vue";
import { shallowMount } from "@vue/test-utils";

describe("Main", () => {
    beforeEach(() => {
        downloadXLSXDocument.mockReset();
    });

    it("starts document export", async () => {
        const wrapper = shallowMount(Main, {
            global: {
                stubs: {
                    teleport: true,
                },
            },
            props: {
                properties: {} as GlobalExportProperties,
            },
        });

        const download_button = wrapper.find("[data-test=download-button]");

        await download_button.trigger("click");

        expect(downloadXLSXDocument).toHaveBeenCalled();
    });
});
