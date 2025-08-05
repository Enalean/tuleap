/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import BaseSiteAdminEditModal from "./BaseSiteAdminEditModal.vue";
import InEditionCustomService from "./Service/InEditionCustomService.vue";
import EditableSystemService from "./Service/EditableSystemService.vue";
import { getGlobalTestOptions } from "../support/global-options-for-tests.js";

function createFakeButton(service) {
    return {
        dataset: {
            serviceJson: JSON.stringify(service),
        },
    };
}

describe(`BaseSiteAdminEditModal`, () => {
    let props;
    beforeEach(() => {
        props = {
            project_id: "101",
            minimal_rank: 10,
            csrf_token: "csrf",
            csrf_token_name: "challenge",
            allowed_icons: {},
        };
    });

    function createWrapper() {
        return shallowMount(BaseSiteAdminEditModal, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    "edit-modal": {
                        template: `<div><slot name="content"/></div>`,
                        methods: {
                            show: jest.fn(),
                        },
                    },
                },
            },
            props,
        });
    }

    it(`When the modal is not shown, it does not instantiate service components`, () => {
        const wrapper = createWrapper();
        const project_service = wrapper.findComponent(InEditionCustomService);
        const system_service = wrapper.findComponent(EditableSystemService);
        expect(project_service.exists()).toBe(false);
        expect(system_service.exists()).toBe(false);
    });

    describe(`when the show() method is called`, () => {
        it(`and it's a custom service, it will instantiate the custom service component`, async () => {
            const wrapper = createWrapper();
            const fake_button = createFakeButton({ is_project_scope: true });
            wrapper.vm.show(fake_button);
            await wrapper.vm.$nextTick();

            const project_service = wrapper.findComponent(InEditionCustomService);
            expect(project_service.exists()).toBe(true);
        });

        it(`and it's a system service, it will instantiate the editable system service component`, async () => {
            const wrapper = createWrapper();
            const fake_button = createFakeButton({ is_project_scope: false });
            wrapper.vm.show(fake_button);
            await wrapper.vm.$nextTick();

            const system_service = wrapper.findComponent(EditableSystemService);
            expect(system_service.exists()).toBe(true);
        });
    });
});
