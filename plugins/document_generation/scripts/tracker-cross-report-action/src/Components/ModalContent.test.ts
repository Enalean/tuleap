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

import ModalContent from "./ModalContent.vue";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";

describe("ModalContent", () => {
    beforeEach(() => {
        downloadXLSXDocument.mockReset();
    });

    it("starts document export", async () => {
        const wrapper = shallowMount(ModalContent, {
            global: {
                plugins: [createGettext({ silent: true })],
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
