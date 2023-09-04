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

import { describe, it, vi } from "vitest";
import type { GlobalExportProperties } from "../type";
import * as export_document from "../export-document";
import ModalContent from "./ModalContent.vue";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "./global-options-for-test";

describe("ModalContent", () => {
    it("starts document export", () =>
        new Promise((done) => {
            vi.spyOn(export_document, "downloadXLSXDocument").mockImplementation(
                (): Promise<void> => {
                    return new Promise(done);
                },
            );
            const wrapper = shallowMount(ModalContent, {
                global: getGlobalTestOptions(),
                props: {
                    properties: {
                        current_project_id: 963,
                        current_tracker_id: 147,
                        current_tracker_name: "Name",
                        current_report_id: 130,
                        current_tracker_artifact_link_types: [],
                    } as GlobalExportProperties,
                },
            });

            const download_button = wrapper.find("[data-test=download-button]");

            download_button.trigger("click");
        }));
});
