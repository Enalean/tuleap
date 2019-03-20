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

import { mount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { restore, rewire$getBaselineArtifactsByIds } from "../../api/rest-querier";
import { create } from "../../support/factories";
import BaselineArtifact from "./BaselineArtifact.vue";

describe("BaselineArtifact", () => {
    const artifact_selector = '[data-test-type="artifact"]';
    const artifact_fields_selector = '[data-test-type="artifact-fields"]';
    const artifact_description_selector = '[data-test-type="artifact-description"]';

    let getBaselineArtifactsByIds;
    let wrapper;

    beforeEach(async () => {
        const linked_artifact = create("artifact", { title: "Story" });
        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        getBaselineArtifactsByIds.and.returnValue(Promise.resolve([linked_artifact]));
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);

        wrapper = mount(BaselineArtifact, {
            localVue,
            propsData: {
                baseline_id: 1,
                artifact: create("artifact", { title: "Epic", linked_artifact_ids: [9] })
            }
        });
        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    describe("when artifacts has description", () => {
        beforeEach(() => {
            wrapper.setProps({ artifact: create("artifact", { description: "my description" }) });
        });
        it("shows artifact descriptions", () => {
            expect(wrapper.contains(artifact_description_selector)).toBeTruthy();
        });
    });

    describe("when artifact has no description", () => {
        beforeEach(async () => {
            wrapper.setProps({
                artifact: create("artifact", { description: null })
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show description", () => {
            expect(
                wrapper.find(artifact_fields_selector).contains(artifact_description_selector)
            ).toBeFalsy();
        });
    });

    it("shows linked artifacts", () => {
        expect(wrapper.find(artifact_selector).contains(artifact_selector)).toBeTruthy();
    });
});
