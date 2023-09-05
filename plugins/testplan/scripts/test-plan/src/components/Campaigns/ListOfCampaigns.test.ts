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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Campaign } from "../../type";
import type { CampaignState } from "../../store/campaign/type";
import ListOfCampaigns from "./ListOfCampaigns.vue";
import CampaignSkeleton from "./CampaignSkeleton.vue";
import CampaignCard from "./CampaignCard.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("ListOfCampaigns", () => {
    const load_campaigns_spy = jest.fn();

    function createWrapper(
        campaign_state: CampaignState,
    ): VueWrapper<InstanceType<typeof ListOfCampaigns>> {
        load_campaigns_spy.mockReset();
        const campaign_module = {
            namespaced: true,
            state: campaign_state,
            actions: {
                loadCampaigns: load_campaigns_spy,
            },
        };

        return shallowMount(ListOfCampaigns, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        campaign: campaign_module,
                    },
                }),
            },
        });
    }

    it("Displays skeletons while loading", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(true);
    });

    it("Does not display skeletons when not loading", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(false);
    });

    it("Does not display any cards when there is no campaign", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignCard).exists()).toBe(false);
    });

    it("Displays a card for each campaign", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [{ id: 1 }, { id: 2 }] as Campaign[],
        });

        expect(wrapper.findAllComponents(CampaignCard)).toHaveLength(2);
    });

    it("Displays skeletons even if there are campaigns to show loading indication", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [{ id: 1 }, { id: 2 }] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(true);
    });

    it("Loads automatically the campaigns", async () => {
        await createWrapper({
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(load_campaigns_spy).toHaveBeenCalled();
    });

    it("Displays empty state when there is no campaign", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("[data-test=async-empty-state]").exists()).toBe(true);
    });

    it("Does not display empty state when there is no campaign but it is still loading", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("campaign-empty-state-stub").exists()).toBe(false);
    });

    it("Does not display empty state when there are campaigns", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [{ id: 1 }] as Campaign[],
        });

        expect(wrapper.find("campaign-empty-state-stub").exists()).toBe(false);
    });

    it("Does not display empty state when there is an error", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: true,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("campaign-empty-state-stub").exists()).toBe(false);
    });

    it("Displays error state when there is an error", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: true,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("[data-test=async-error-state]").exists()).toBe(true);
    });

    it("Does not display error state when there is no error", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("campaign-error-state-stub").exists()).toBe(false);
    });
});
