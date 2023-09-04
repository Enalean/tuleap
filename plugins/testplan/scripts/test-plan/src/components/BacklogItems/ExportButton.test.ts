/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import ExportError from "./ExportError.vue";

jest.mock("@tuleap/tlp-dropdown");

const downloadXlsxExportDocument = jest.fn();
jest.mock("../../helpers/ExportAsSpreadsheet/download-export-document", () => {
    return {
        downloadExportDocument: downloadXlsxExportDocument,
    };
});

const downloadDocxExportDocument = jest.fn();
jest.mock("../../helpers/ExportAsDocument/download-export-document", () => {
    return {
        downloadExportDocument: downloadDocxExportDocument,
    };
});

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { RootState } from "../../store/type";
import ExportButton from "./ExportButton.vue";
import type { BacklogItemState } from "../../store/backlog-item/type";
import type { CampaignState } from "../../store/campaign/type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("ExportButton", () => {
    beforeEach(() => {
        downloadXlsxExportDocument.mockReset();
        downloadDocxExportDocument.mockReset();
    });

    function createWrapper(
        backlog_item: BacklogItemState,
        campaign: CampaignState,
        error_handler: (e: unknown) => void = (e): void => {
            throw e;
        },
    ): VueWrapper<InstanceType<typeof ExportButton>> {
        return shallowMount(ExportButton, {
            global: {
                ...getGlobalTestOptions(
                    {
                        state: {
                            project_name: "Project",
                            milestone_title: "My milestone",
                        } as RootState,
                        modules: {
                            campaign: {
                                namespaced: true,
                                state: campaign,
                            },
                            backlog_item: {
                                namespaced: true,
                                state: backlog_item,
                            },
                        },
                    },
                    error_handler,
                ),
            },
        });
    }

    it("Allows to download a report", async () => {
        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: false,
            } as CampaignState,
        );
        const export_button = wrapper.get("[data-test=testplan-export-button]");
        const xslx_button = wrapper.get("[data-test=testplan-export-xlsx-button]");

        expect(export_button.element.hasAttribute("disabled")).toBe(false);
        expect(
            export_button
                .get("[data-test=download-export-button-icon]")
                .element.classList.contains("fa-spin"),
        ).toBe(false);

        downloadXlsxExportDocument.mockImplementation((): void => {
            expect(
                export_button
                    .get("[data-test=download-export-button-icon]")
                    .element.classList.contains("fa-spin"),
            ).toBe(true);

            // This is here to make sure the user cannot spam-click the export button
            // Only one report should be generated at a time, subsequent clicks should do nothing
            xslx_button.trigger("click");

            expect(wrapper.findComponent(ExportError).exists()).toBe(false);
        });

        xslx_button.trigger("click");
    });

    it("Does not allow to download the report when the backlog items are not loaded", async () => {
        const wrapper = await createWrapper(
            {
                is_loading: true,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: false,
            } as CampaignState,
        );

        expect(
            wrapper.get("[data-test=testplan-export-button]").element.hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Does not allow to download the report when the campaigns are not loaded", async () => {
        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: true,
                has_loading_error: false,
            } as CampaignState,
        );

        expect(
            wrapper.get("[data-test=testplan-export-button]").element.hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Does not allow to download the report when the backlog items have a loading error", async () => {
        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: true,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: false,
            } as CampaignState,
        );

        expect(
            wrapper.get("[data-test=testplan-export-button]").element.hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Does not allow to download the report when the campaigns have a loading error", async () => {
        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: true,
            } as CampaignState,
        );

        expect(
            wrapper.get("[data-test=testplan-export-button]").element.hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Export button icon does not stay in loading mode in case of xlsx failure", async () => {
        const error = new Error("Something bad happened");
        downloadXlsxExportDocument.mockRejectedValue(error);
        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: false,
            } as CampaignState,
            (e): void => {
                expect(e).toBe(error);
            },
        );

        const download_button = wrapper.get("[data-test=testplan-export-xlsx-button]");

        await download_button.trigger("click");

        // Needs 5 ticks so the component can be rendered after the error in the async v-on handler
        for (let i = 0; i < 5; i++) {
            await nextTick();
        }

        expect(wrapper.get("[data-test=download-export-button-icon]").classes()).not.toContain(
            "fa-spin",
        );
        expect(wrapper.findComponent(ExportError).exists()).toBe(true);
    });

    it("Export button icon does not stay in loading mode in case of docx failure", async () => {
        const error = new Error("Something bad happened");
        downloadDocxExportDocument.mockRejectedValue(error);

        const wrapper = await createWrapper(
            {
                is_loading: false,
                has_loading_error: false,
            } as BacklogItemState,
            {
                is_loading: false,
                has_loading_error: false,
            } as CampaignState,
            (e): void => {
                expect(e).toBe(error);
            },
        );

        const download_button = wrapper.get("[data-test=testplan-export-docx-button]");

        await download_button.trigger("click");

        // Needs 5 ticks so the component can be rendered after the error in the async v-on handler
        for (let i = 0; i < 5; i++) {
            await nextTick();
        }

        expect(wrapper.get("[data-test=download-export-button-icon]").classes()).not.toContain(
            "fa-spin",
        );
        expect(wrapper.findComponent(ExportError).exists()).toBe(true);
    });
});
