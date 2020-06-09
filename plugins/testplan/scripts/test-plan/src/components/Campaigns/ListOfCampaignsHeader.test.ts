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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";
import { Campaign } from "../../type";
import { CampaignState } from "../../store/campaign/type";
import ListOfCampaignsHeader from "./ListOfCampaignsHeader.vue";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";

describe("ListOfCampaignsHeader", () => {
    async function createWrapper(
        user_can_create_campaign: boolean,
        campaign: CampaignState,
        show_create_modal = jest.fn()
    ): Promise<Wrapper<ListOfCampaignsHeader>> {
        return shallowMount(ListOfCampaignsHeader, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        user_can_create_campaign,
                        campaign,
                    } as RootState,
                }),
            },
            propsData: {
                showCreateModal: show_create_modal,
            },
        });
    }

    it("Displays new campaign button when there are campaigns", async () => {
        const wrapper = await createWrapper(true, {
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [{ id: 1 }] as Campaign[],
        });

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(true);
    });

    it(`Does not display new campaign button when there is no campaign,
        because it is displayed elsewhere (empty state)`, async () => {
        const wrapper = await createWrapper(true, {
            has_refreshing_error: false,
            is_loading: true,
            has_loading_error: false,
            campaigns: [] as Campaign[],
        });

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(false);
    });

    it(`Does not display new campaign button when there is an error`, async () => {
        const wrapper = await createWrapper(true, {
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: true,
            campaigns: [{ id: 1 }] as Campaign[],
        });

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(false);
    });

    it(`Does not display new campaign button when the user cannot create new ones`, async () => {
        const wrapper = await createWrapper(false, {
            has_refreshing_error: false,
            is_loading: false,
            has_loading_error: false,
            campaigns: [{ id: 1 }] as Campaign[],
        });

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(false);
    });

    it(`On click on the button, it calls showCreateModal`, async () => {
        const show_create_modal = jest.fn();

        const wrapper = await createWrapper(
            true,
            {
                has_refreshing_error: false,
                is_loading: true,
                has_loading_error: false,
                campaigns: [{ id: 1 }] as Campaign[],
            },
            show_create_modal
        );

        await wrapper.find("[data-test=new-campaign]").trigger("click");

        expect(show_create_modal).toHaveBeenCalled();
    });
});
