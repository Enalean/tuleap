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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue.js";

import RootFolder from "./RootFolder.vue";
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

describe("RootFolder", () => {
    let factory, state, store;

    beforeEach(() => {
        state = {
            current_folder: null,
        };

        const store_options = {
            state,
        };

        store = createStoreMock(store_options);

        factory = () => {
            return shallowMount(RootFolder, {
                localVue,
                mocks: { $store: store },
            });
        };
    });

    it(`Should load folder content at first load`, () => {
        store.state.current_folder = null;

        factory();

        expect(store.dispatch).toHaveBeenCalledWith("loadRootFolder");
        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
        expect(store.commit).toHaveBeenCalledWith("resetAscendantHierarchy");
    });

    it(`Should load root folder, if we are moving from a folder to root folder`, () => {
        store.state.current_folder = {
            id: 3,
            title: "root folder",
            parent_id: 42,
        };

        factory();

        expect(store.dispatch).toHaveBeenCalledWith("loadRootFolder");
        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
        expect(store.commit).toHaveBeenCalledWith("resetAscendantHierarchy");
    });

    it(`Should not load root folder, if app have already been launched`, () => {
        store.state.current_folder = {
            id: 3,
            title: "root folder",
            parent_id: 0,
        };

        factory();

        expect(store.dispatch).not.toHaveBeenCalledWith("loadRootFolder");
        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
        expect(store.commit).toHaveBeenCalledWith("resetAscendantHierarchy");
    });
});
