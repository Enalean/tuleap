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
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/index.js";
import { restore, rewire$getBaselineArtifactsByIds } from "../../api/rest-querier";
import { create } from "../../support/factories";
import BaselineArtifact from "./BaselineArtifact.vue";
import BaselineDepthLimitReachedMessage from "../common/BaselineDepthLimitReachedMessage.vue";
import BaselineArtifacts from "../baseline-page/BaselineArtifacts.vue";

describe("BaselineArtifact", () => {
    const artifact_selector = '[data-test-type="artifact"]';
    const artifact_fields_selector = '[data-test-type="artifact-fields"]';
    const artifact_description_selector = '[data-test-type="artifact-description"]';
    const artifact_status_selector = '[data-test-type="artifact-status"]';

    let getBaselineArtifactsByIds;
    let wrapper;

    beforeEach(async () => {
        const linked_artifact = create("artifact", { title: "Story" });
        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        getBaselineArtifactsByIds.and.returnValue(Promise.resolve([linked_artifact]));
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);

        wrapper = mount(BaselineArtifact, {
            propsData: {
                baseline_id: 1,
                artifact: create("artifact", {
                    title: "Epic",
                    linked_artifact_ids: [linked_artifact.id]
                }),
                current_depth: 1
            },
            localVue,
            mocks: {
                $store: createStoreMock(store_options)
            }
        });
        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    describe("when artifact has description", () => {
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

    describe("when artifact has status", () => {
        beforeEach(() => {
            wrapper.setProps({ artifact: create("artifact", { status: "my status" }) });
        });
        it("shows artifact status", () => {
            expect(wrapper.contains(artifact_status_selector)).toBeTruthy();
        });
    });

    describe("when artifact has no status", () => {
        beforeEach(async () => {
            wrapper.setProps({
                artifact: create("artifact", { status: null })
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show status", () => {
            expect(
                wrapper.find(artifact_fields_selector).contains(artifact_status_selector)
            ).toBeFalsy();
        });
    });

    it("shows linked artifacts", () => {
        expect(wrapper.find(artifact_selector).contains(artifact_selector)).toBeTruthy();
    });

    describe("when artifacts tree has reached depth limit", () => {
        beforeEach(async () => {
            getBaselineArtifactsByIds.calls.reset();
            wrapper.setProps({ current_depth: 10000 });
            await wrapper.vm.$nextTick();
        });

        it("shows information message", () => {
            expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeTruthy();
        });

        it("does not show linked artifact", () => {
            expect(wrapper.contains(BaselineArtifacts)).toBeFalsy();
        });

        it("does not call fetchLinkedArtifacts", () => {
            expect(getBaselineArtifactsByIds).not.toHaveBeenCalled();
        });

        describe("when artifacts doest not have linked artifact", () => {
            beforeEach(async () => {
                wrapper.setProps({ artifact: create("artifact"), linked_artifact_ids: [] });
                await wrapper.vm.$nextTick();
            });

            it("does not show information message", () => {
                expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
            });

            it("does not call fetchLinkedArtifacts", () => {
                expect(getBaselineArtifactsByIds).not.toHaveBeenCalled();
            });
        });
    });
});
