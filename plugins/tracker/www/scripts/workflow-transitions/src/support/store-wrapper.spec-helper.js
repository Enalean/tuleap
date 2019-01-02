/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

import Vuex from "vuex";

/**
 * Mock all properties which are functions.
 *
 * @param object which properties have to be mocked.
 * @returns {{}} New object with copied properties or mock if properties are functions.
 */
function mockAllFunctions(object) {
    return Object.keys(object).reduce(function(mocked_object, key) {
        if (typeof object[key] === "function") {
            mocked_object[key] = jasmine.createSpy(key);
        } else {
            mocked_object[key] = object[key];
        }
        return mocked_object;
    }, {});
}

/**
 * Mock all given store modules.
 *
 * @param modules Object where each property represents a module
 * @returns {{}} New objects with mocked modules.
 */
function mockModules(modules) {
    return Object.keys(modules).reduce(function(mocked_modules, module) {
        mocked_modules[module] = mockStoreOptions(modules[module]);
        return mocked_modules;
    }, {});
}

/**
 * Mock all actions, mutations, getters of a given store option.
 * If present, modules are handled with actions, mutations and getters also mocked.
 *
 * @param store_options
 * @returns {{}} New store options with mocked actions, mutations and getters.
 */
function mockStoreOptions(store_options) {
    const mocked_options = {
        ...store_options,
        actions: mockAllFunctions(store_options.actions),
        mutations: mockAllFunctions(store_options.mutations),
        getters: mockAllFunctions(store_options.getters)
    };
    if (store_options.modules) {
        mocked_options.modules = mockModules(store_options.modules);
    }
    return mocked_options;
}

/**
 * Create a Vuex Store with all actions, mutations and getters mocked.
 * Modules are handled with actions, mutations and getters also mocked.
 *
 * @param store_options
 * @param custom_state
 * @returns {{store: Store<any>}} Store wrapper. Clean Vuex store is available through store property.
 */
export function createStoreWrapper(store_options, custom_state = {}) {
    const mocked_store_options = mockStoreOptions(store_options);

    const state = Object.assign({}, store_options.state, custom_state);
    const actions = mocked_store_options.actions;
    const mutations = mocked_store_options.mutations;
    const getters = mocked_store_options.getters;
    const modules = mocked_store_options.modules;

    return {
        state,
        actions,
        mutations,
        getters,
        modules,
        store: new Vuex.Store({
            state,
            actions,
            mutations,
            getters,
            modules
        })
    };
}
