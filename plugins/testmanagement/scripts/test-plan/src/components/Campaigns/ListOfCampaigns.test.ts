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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";
import { Campaign } from "../../type";
import { CampaignState } from "../../store/campaign/type";
import ListOfCampaigns from "./ListOfCampaigns.vue";
import CampaignSkeleton from "./CampaignSkeleton.vue";
import CampaignCard from "./CampaignCard.vue";

describe("ListOfCampaigns", () => {
    async function createWrapper(campaign: CampaignState): Promise<Wrapper<ListOfCampaigns>> {
        return shallowMount(ListOfCampaigns, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        campaign,
                    } as RootState,
                }),
            },
        });
    }

    it("Displays skeletons while loading", async () => {
        const wrapper = await createWrapper({
            is_loading: true,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(true);
    });

    it("Does not display skeletons when not loading", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(false);
    });

    it("Does not display any cards when there is no campaign", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignCard).exists()).toBe(false);
    });

    it("Displays a card for each campaign", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            campaigns: [{ id: 1 }, { id: 2 }] as Campaign[],
        });

        expect(wrapper.findAllComponents(CampaignCard).length).toBe(2);
    });

    it("Displays skeletons even if there are campaigns to show loading indication", async () => {
        const wrapper = await createWrapper({
            is_loading: true,
            campaigns: [{ id: 1 }, { id: 2 }] as Campaign[],
        });

        expect(wrapper.findComponent(CampaignSkeleton).exists()).toBe(true);
    });

    it("Loads automatically the campaigns", async () => {
        const $store = createStoreMock({
            state: {
                campaign: {
                    is_loading: true,
                    campaigns: [] as Campaign[],
                },
            } as RootState,
        });
        shallowMount(ListOfCampaigns, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store,
            },
        });

        expect($store.dispatch).toHaveBeenCalledWith("campaign/loadCampaigns");
    });
});
