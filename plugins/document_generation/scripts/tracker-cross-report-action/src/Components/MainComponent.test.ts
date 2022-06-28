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
import MainComponent from "./MainComponent.vue";
import { mount } from "@vue/test-utils";

describe("MainComponent", () => {
    it("mounts modal content", () => {
        const main_content = document.createElement("div");
        main_content.id = "main-content";
        document.body.append(main_content);

        mount(MainComponent, {
            global: {
                stubs: {
                    ModalContent: true,
                },
            },
            props: {
                properties: {} as GlobalExportProperties,
            },
        });

        expect(main_content.innerHTML).not.toBe("");
    });
});
