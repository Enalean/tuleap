/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import * as tlp from "tlp";
import { Modal } from "tlp";
import CreateModal from "./CreateModal.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";
import { CampaignState } from "../../store/campaign/type";
import * as tracker_report_retriever from "../../helpers/Campaigns/tracker-reports-retriever";
import { BacklogItemState } from "../../store/backlog-item/type";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        createModal: jest.fn(),
    };
});

describe("CreateModal", () => {
    let local_vue: typeof Vue;

    beforeEach(async () => {
        local_vue = await createTestPlanLocalVue();
    });

    it("Display the modal when mounted", async () => {
        const modal_show = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return ({
                show: modal_show,
            } as unknown) as Modal;
        });

        const wrapper = shallowMount(CreateModal, {
            localVue: local_vue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_id: null,
                    } as RootState,
                }),
            },
        });
        // We need to wait for the loading state to be rendered and the tracker reports status to be resolved
        await wrapper.vm.$nextTick();

        expect(modal_show).toHaveBeenCalledTimes(1);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("creates the campaign, hides the modal and refresh the backlog items", async () => {
        const modal_hide = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return ({
                show: jest.fn(),
                hide: modal_hide,
            } as unknown) as Modal;
        });

        const $store = createStoreMock({
            state: {
                milestone_title: "Milestone Title",
                testdefinition_tracker_id: null,
                campaign: {} as CampaignState,
                backlog_item: {} as BacklogItemState,
            } as RootState,
        });

        const wrapper = shallowMount(CreateModal, {
            localVue: local_vue,
            mocks: {
                $store,
            },
        });

        wrapper.vm.$data.label = "My new campaign";
        wrapper.vm.$data.test_selector = "milestone";

        await wrapper.trigger("submit");

        expect($store.dispatch).toHaveBeenCalledWith("campaign/createCampaign", {
            label: "My new campaign",
            initial_tests: {
                test_selector: "milestone",
            },
        });
        expect($store.dispatch).toHaveBeenCalledWith("backlog_item/loadBacklogItems");
        expect(modal_hide).toHaveBeenCalledTimes(1);
    });

    it("sets an error message when the reports of the test definition tracker cannot be retrieved", async () => {
        const modal_hide = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return ({
                show: jest.fn(),
                hide: modal_hide,
            } as unknown) as Modal;
        });
        jest.spyOn(tracker_report_retriever, "getTrackerReports").mockRejectedValueOnce(
            new Error("Something bad happened")
        );

        const $store = createStoreMock({
            state: {
                milestone_title: "Milestone Title",
                testdefinition_tracker_id: 12,
            } as RootState,
        });

        const wrapper = shallowMount(CreateModal, {
            localVue: local_vue,
            mocks: {
                $store,
            },
        });
        await wrapper.vm.$nextTick();
        // We need to mock console.error otherwise vue-test-utils tell us something has gone wrong when mounting the
        // component because we re-throw the error which ends up failing the test
        const consoleErrorSpy = jest.spyOn(global.console, "error").mockImplementation();
        try {
            await wrapper.vm.$nextTick();
        } finally {
            expect(consoleErrorSpy).toHaveBeenCalled();
            consoleErrorSpy.mockRestore();
        }

        expect(wrapper.find("[data-test=new-campaign-error-message]").exists()).toBe(true);
    });
});
