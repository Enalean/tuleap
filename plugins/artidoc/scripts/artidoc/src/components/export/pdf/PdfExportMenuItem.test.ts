/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { PDF_TEMPLATES } from "@/pdf-templates-injection-key";
import { createGettext } from "vue3-gettext";

vi.mock("@tuleap/tlp-dropdown");

describe("PdfExportMenuItem", () => {
    it("should display one menuitem if one template", () => {
        mockStrictInject([
            [
                PDF_TEMPLATES,
                [
                    {
                        id: "abc",
                        label: "Blue template",
                        description: "",
                        style: "body { color: blue }",
                    },
                ],
            ],
        ]);

        const wrapper = shallowMount(PdfExportMenuItem, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findAll("[role=menuitem]")).toHaveLength(1);
    });

    it("should display three menuitem if two template (one for each template + one for the submenu)", () => {
        mockStrictInject([
            [
                PDF_TEMPLATES,
                [
                    {
                        id: "abc",
                        label: "Blue template",
                        description: "",
                        style: "body { color: blue }",
                    },
                    {
                        id: "def",
                        label: "Red template",
                        description: "",
                        style: "body { color: red }",
                    },
                ],
            ],
        ]);

        const wrapper = shallowMount(PdfExportMenuItem, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findAll("[role=menuitem]")).toHaveLength(3);
    });
});
