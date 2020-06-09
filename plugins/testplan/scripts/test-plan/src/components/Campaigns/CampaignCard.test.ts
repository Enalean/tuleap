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
import CampaignCard from "./CampaignCard.vue";
import { Campaign } from "../../type";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";

describe("CampaignCard", () => {
    it("Displays a campaign as a card", async () => {
        const wrapper = shallowMount(CampaignCard, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_id: 74,
                        project_id: 102,
                    } as RootState,
                }),
            },
            propsData: {
                campaign: {
                    id: 470,
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 1,
                    nb_of_passed: 10,
                } as Campaign,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a being refreshed campaign", async () => {
        const wrapper = shallowMount(CampaignCard, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_id: 74,
                        project_id: 102,
                    } as RootState,
                }),
            },
            propsData: {
                campaign: {
                    id: 470,
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 1,
                    nb_of_passed: 10,
                    is_being_refreshed: true,
                    is_just_refreshed: false,
                    is_error: false,
                } as Campaign,
            },
        });

        expect(wrapper.classes("test-plan-campaign-is-error")).toBe(false);
        expect(wrapper.classes("test-plan-campaign-is-being-refreshed")).toBe(true);
        expect(wrapper.classes("test-plan-campaign-is-just-refreshed")).toBe(false);
    });

    it("Displays a just refreshed campaign", async () => {
        const wrapper = shallowMount(CampaignCard, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_id: 74,
                        project_id: 102,
                    } as RootState,
                }),
            },
            propsData: {
                campaign: {
                    id: 470,
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 1,
                    nb_of_passed: 10,
                    is_being_refreshed: false,
                    is_just_refreshed: true,
                    is_error: false,
                } as Campaign,
            },
        });

        expect(wrapper.classes("test-plan-campaign-is-error")).toBe(false);
        expect(wrapper.classes("test-plan-campaign-is-being-refreshed")).toBe(false);
        expect(wrapper.classes("test-plan-campaign-is-just-refreshed")).toBe(true);
    });

    it("Displays a campaign in error", async () => {
        const wrapper = shallowMount(CampaignCard, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_id: 74,
                        project_id: 102,
                    } as RootState,
                }),
            },
            propsData: {
                campaign: {
                    id: 470,
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 1,
                    nb_of_passed: 10,
                    is_being_refreshed: false,
                    is_just_refreshed: false,
                    is_error: true,
                } as Campaign,
            },
        });

        expect(wrapper.classes("test-plan-campaign-is-error")).toBe(true);
        expect(wrapper.classes("test-plan-campaign-is-being-refreshed")).toBe(false);
        expect(wrapper.classes("test-plan-campaign-is-just-refreshed")).toBe(false);
    });
});
