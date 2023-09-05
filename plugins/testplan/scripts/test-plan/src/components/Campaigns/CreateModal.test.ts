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

import { shallowMount } from "@vue/test-utils";
import * as tlp from "tlp";
import type { Modal } from "tlp";
import CreateModal from "./CreateModal.vue";
import type { RootState } from "../../store/type";
import * as tracker_report_retriever from "../../helpers/Campaigns/tracker-reports-retriever";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { nextTick } from "vue";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        createModal: jest.fn(),
    };
});

describe("CreateModal", () => {
    it("Display the modal when mounted", async () => {
        const modal_show = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                show: modal_show,
            } as unknown as Modal;
        });

        const wrapper = shallowMount(CreateModal, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_id: null,
                    } as RootState,
                }),
            },
        });
        // We need to wait for the loading state to be rendered and the tracker reports status to be resolved
        await nextTick();

        expect(modal_show).toHaveBeenCalledTimes(1);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("creates the campaign, hides the modal and refresh the backlog items", async () => {
        const modal_hide = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                show: jest.fn(),
                hide: modal_hide,
            } as unknown as Modal;
        });

        const create_campaign_spy = jest.fn();
        const load_backlog_items_spy = jest.fn();

        const wrapper = shallowMount(CreateModal, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        milestone_title: "Milestone Title",
                        testdefinition_tracker_id: null,
                    } as RootState,
                    modules: {
                        campaign: {
                            namespaced: true,
                            state: {},
                            actions: {
                                createCampaign: create_campaign_spy,
                            },
                        },
                        backlog_item: {
                            namespaced: true,
                            state: {},
                            actions: {
                                loadBacklogItems: load_backlog_items_spy,
                            },
                        },
                    },
                }),
            },
        });

        // We need to wait for the loading state to be rendered and the tracker reports status to be resolved
        await nextTick();

        wrapper.find("[data-test=new-campaign-label]").setValue("My new campaign");

        await wrapper.trigger("submit");

        expect(create_campaign_spy).toHaveBeenCalledWith(expect.any(Object), {
            label: "My new campaign",
            initial_tests: {
                test_selector: "milestone",
            },
        });
        expect(load_backlog_items_spy).toHaveBeenCalled();
        expect(modal_hide).toHaveBeenCalledTimes(1);
    });

    it("sets an error message when the reports of the test definition tracker cannot be retrieved", async () => {
        const modal_hide = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                show: jest.fn(),
                hide: modal_hide,
            } as unknown as Modal;
        });
        const expected_error = new Error("Something bad happened");
        jest.spyOn(tracker_report_retriever, "getTrackerReports").mockRejectedValueOnce(
            expected_error,
        );

        const wrapper = shallowMount(CreateModal, {
            global: {
                ...getGlobalTestOptions(
                    {
                        state: {
                            milestone_title: "Milestone Title",
                            testdefinition_tracker_id: 12,
                        } as RootState,
                    },
                    (e): void => {
                        expect(e).toBe(expected_error);
                    },
                ),
            },
        });

        // We need to wait for the loading state to be rendered and then to fail to retrieve the tracker reports
        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.find("[data-test=new-campaign-error-message]").exists()).toBe(true);
    });
});
