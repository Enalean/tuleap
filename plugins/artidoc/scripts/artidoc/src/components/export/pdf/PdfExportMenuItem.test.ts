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
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import PrinterVersion from "@/components/print/PrinterVersion.vue";

vi.mock("@tuleap/tlp-dropdown");

describe("PdfExportMenuItem", () => {
    it("should display disabled menuitem if user is anonymous", () => {
        mockStrictInject([
            [IS_USER_ANONYMOUS, true],
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

        const button = wrapper.findAll("[role=menuitem]");
        expect(button).toHaveLength(1);
        expect(button[0].attributes("disabled")).toBeDefined();
        expect(button[0].attributes("title")).toBe(
            "Please log in in order to be able to export as PDF",
        );
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
    });

    it.each([[null], [[]]])(
        "should display disabled menuitem if no template defined: %s",
        (templates) => {
            mockStrictInject([
                [IS_USER_ANONYMOUS, false],
                [PDF_TEMPLATES, templates],
            ]);

            const wrapper = shallowMount(PdfExportMenuItem, {
                global: { plugins: [createGettext({ silent: true })] },
            });

            const button = wrapper.findAll("[role=menuitem]");
            expect(button).toHaveLength(1);
            expect(button[0].attributes("disabled")).toBeDefined();
            expect(button[0].attributes("title")).toBe(
                "No template was defined for export, please contact site administrator",
            );
            expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
        },
    );

    it("should display one menuitem if one template", () => {
        mockStrictInject([
            [IS_USER_ANONYMOUS, false],
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
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(true);
    });

    it("should display three menuitem if two template (one for each template + one for the submenu)", () => {
        mockStrictInject([
            [IS_USER_ANONYMOUS, false],
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
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(true);
    });
});
