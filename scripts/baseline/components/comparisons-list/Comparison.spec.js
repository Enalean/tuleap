/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import { createLocalVue, shallowMount } from "@vue/test-utils";
import Comparison from "./Comparison.vue";
import { create } from "../../support/factories";
import { createStoreMock } from "../../support/store-wrapper.spec-helper";
import store_options from "../../store/store_options";
import ArtifactLink from "../common/ArtifactLink.vue";
import UserBadge from "../common/UserBadge.vue";
import GettextPlugin from "vue-gettext";

describe("Comparison", () => {
    let $store;
    let $router;
    let localVue;

    let wrapper;

    const base_baseline_artifact = create("artifact");
    const author = create("user");

    beforeEach(() => {
        $store = createStoreMock(store_options);
        $router = { push: jasmine.createSpy("$router.push") };
        $store.getters.findBaselineById = jasmine.createSpy("findBaselineById");
        $store.getters.findArtifactById = jasmine.createSpy("findArtifactById");
        $store.getters.findTrackerById = jasmine.createSpy("findTrackerById");
        $store.getters.findUserById = jasmine.createSpy("findUserById");

        localVue = createLocalVue();
        localVue.use(GettextPlugin, {
            translations: {},
            silent: true
        });
    });

    beforeEach(() => {
        $store.getters.findBaselineById
            .withArgs(11)
            .and.returnValue(create("baseline", { artifact_id: 22 }));
        $store.getters.findBaselineById.withArgs(12).and.returnValue(create("baseline"));
        $store.getters.findArtifactById.withArgs(22).and.returnValue(base_baseline_artifact);
        $store.getters.findTrackerById.and.returnValue(create("tracker"));
        $store.getters.findUserById.withArgs(9).and.returnValue(author);

        wrapper = shallowMount(Comparison, {
            propsData: {
                comparison: create("comparison", {
                    id: 1,
                    base_baseline_id: 11,
                    compared_to_baseline_id: 12,
                    author_id: 9
                })
            },
            localVue,
            mocks: { $store, $router }
        });
    });

    it("shows base baseline as milestone", () => {
        expect(wrapper.find(ArtifactLink).vm.artifact).toEqual(base_baseline_artifact);
    });

    it("shows author", () => {
        expect(wrapper.find(UserBadge).vm.user).toEqual(author);
    });
});
