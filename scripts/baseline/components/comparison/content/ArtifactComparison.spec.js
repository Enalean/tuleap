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
 */

import { create } from "../../../support/factories";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../support/local-vue";
import ArtifactComparison from "./ArtifactComparison.vue";
import { createStoreMock } from "../../../support/store-wrapper.spec-helper";
import store_options from "../../../store";
import BaselineMaximumDepthReachedMessage from "../../common/BaselineDepthLimitReachedMessage.vue";

describe("ArtifactComparison", () => {
    let wrapper;
    let fetchLinkedArtifacts;

    beforeEach(async () => {
        fetchLinkedArtifacts = jasmine.createSpy("fetchLinkedArtifacts");

        wrapper = shallowMount(ArtifactComparison, {
            localVue,
            propsData: {
                reference: create("artifact", { linked_artifact_ids: [1] }),
                compared_to: create("artifact", { linked_artifact_ids: [] }),
                current_depth: 1000
            },
            mocks: {
                $store: createStoreMock(store_options)
            }
        });
        await wrapper.vm.$nextTick();
    });

    describe("when artifacts has linked artifact", () => {
        it("shows information message", () => {
            expect(wrapper.contains(BaselineMaximumDepthReachedMessage)).toBeTruthy();
        });

        it("does not call fetchLinkedArtifacts", () => {
            expect(fetchLinkedArtifacts).not.toHaveBeenCalled();
        });
    });

    describe("when artifacts does not have linked artifact", () => {
        beforeEach(async () => {
            wrapper.setProps({
                reference: create("artifact", { linked_artifact_ids: [] }),
                compared_to: create("artifact", { linked_artifact_ids: [] })
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show information message", () => {
            expect(wrapper.contains(BaselineMaximumDepthReachedMessage)).toBeFalsy();
        });
    });
});
