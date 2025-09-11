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
import type { VueWrapper } from "@vue/test-utils";
import { CSRF_TOKEN, MINIMAL_RANK, PROJECT_ID } from "../injection-symbols";
import BaseSiteAdminEditModal from "./BaseSiteAdminEditModal.vue";
import InEditionCustomService from "./Service/InEditionCustomService.vue";
import EditableSystemService from "./Service/EditableSystemService.vue";
import { getGlobalTestOptions } from "../support/global-options-for-tests";

const service = {
    is_project_scope: true,
    label: "My custom service",
    icon_name: "my-icon",
    is_in_new_tab: false,
};

function createFakeButton(): HTMLButtonElement {
    const button = document.createElement("button");
    button.dataset.serviceJson = JSON.stringify(service);
    return button;
}

describe(`BaseSiteAdminEditModal`, () => {
    function createWrapper(): VueWrapper {
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
                provide: {
                    [PROJECT_ID.valueOf()]: 101,
                    [MINIMAL_RANK.valueOf()]: 10,
                    [CSRF_TOKEN.valueOf()]: { value: "csrf", name: "challenge" },
                },
            },
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
            const fake_button = createFakeButton();

            const exposed_wrapper = wrapper.vm.$.exposed as {
                show: (button: HTMLButtonElement) => void;
            };
            exposed_wrapper.show(fake_button);
            await wrapper.vm.$nextTick();

            const project_service = wrapper.findComponent(InEditionCustomService);
            expect(project_service.exists()).toBe(true);
        });

        it(`and it's a system service, it will instantiate the editable system service component`, async () => {
            const wrapper = createWrapper();
            service.is_project_scope = false;
            const fake_button = createFakeButton();
            const exposed_wrapper = wrapper.vm.$.exposed as {
                show: (button: HTMLButtonElement) => void;
            };
            exposed_wrapper.show(fake_button);
            await wrapper.vm.$nextTick();

            const system_service = wrapper.findComponent(EditableSystemService);
            expect(system_service.exists()).toBe(true);
        });
    });
});
