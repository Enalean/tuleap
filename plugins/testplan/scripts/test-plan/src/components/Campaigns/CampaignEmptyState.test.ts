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
import CampaignEmptyState from "./CampaignEmptyState.vue";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";

describe("CampaignEmptyState", () => {
    async function createWrapper(
        user_can_create_campaign: boolean,
        show_create_modal = jest.fn()
    ): Promise<Wrapper<CampaignEmptyState>> {
        return shallowMount(CampaignEmptyState, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        user_can_create_campaign,
                    } as RootState,
                }),
            },
            propsData: {
                showCreateModal: show_create_modal,
            },
        });
    }

    it("Displays empty state with new campaign creation button", async () => {
        const wrapper = await createWrapper(true);

        expect(wrapper.element).toMatchSnapshot();
        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(true);
    });

    it("Does not show the new campaign creation button in the empty state when the user cannot create new ones", async () => {
        const wrapper = await createWrapper(false);

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(false);
    });

    it(`On click on the button, it calls showCreateModal`, async () => {
        const show_create_modal = jest.fn();

        const wrapper = await createWrapper(true, show_create_modal);

        await wrapper.find("[data-test=new-campaign]").trigger("click");

        expect(show_create_modal).toHaveBeenCalled();
    });
});
