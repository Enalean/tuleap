/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CreateNewVersionEmptyModal from "./CreateNewVersionEmptyModal.vue";
import { TYPE_EMPTY, TYPE_FILE, TYPE_LINK } from "../../../../constants";
import * as tlp_modal from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("CreateNewVersionEmptyModal", () => {
    let factory;
    const create_new_version = vi.fn();
    const reset_error_modal = vi.fn();

    beforeEach(() => {
        factory = (props): VueWrapper<CreateNewVersionEmptyModal> => {
            return shallowMount(CreateNewVersionEmptyModal, {
                props: { ...props },
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            error: {
                                state: {
                                    has_modal_error: false,
                                },
                                mutations: {
                                    resetModalError: reset_error_modal,
                                },
                                namespaced: true,
                            },
                            configuration: {
                                state: {
                                    project_id: 101,
                                },
                                namespaced: true,
                            },
                        },
                        actions: {
                            createNewVersionFromEmpty: create_new_version,
                        },
                    }),
                },
            });
        };

        vi.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
        });
    });

    it("Default type for creation of new link version of an empty document is file", () => {
        const wrapper = factory({
            item: {
                id: 10,
                type: TYPE_EMPTY,
                link_properties: {},
                embedded_properties: {},
                file_properties: {},
            },
        });

        expect(wrapper.vm.new_item_version.type).toBe(TYPE_FILE);
    });
    it("should create a new link version from an empty document", () => {
        const wrapper = factory({
            item: {
                id: 10,
                type: TYPE_EMPTY,
                link_properties: {},
                embedded_properties: {},
                file_properties: {},
            },
        });
        wrapper.setData({
            new_item_version: {
                type: TYPE_LINK,
            },
        });
        wrapper.get("form").trigger("submit.prevent");
        expect(wrapper.vm.is_loading).toBe(true);
        expect(wrapper.vm.new_item_version.type).toBe(TYPE_LINK);
    });
});
