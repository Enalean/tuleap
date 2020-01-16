/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import { State } from "../../../store/type";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import ProjectInformationInputPrivacyList from "./ProjectInformationInputPrivacyList.vue";
import * as tlp from "tlp";
jest.mock("tlp");

describe("ProjectInformationInputPrivacyList", () => {
    let factory: Wrapper<ProjectInformationInputPrivacyList>;

    beforeEach(async () => {
        const state = {
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: false
        } as State;

        const store_options = { state };

        const store = createStoreMock(store_options);

        factory = shallowMount(ProjectInformationInputPrivacyList, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        });

        jest.spyOn(tlp, "createPopover");
    });
    describe("The text displayed in the tooltip -", () => {
        it("Spawns the ProjectInformationInputPrivacyList component", () => {
            const wrapper = factory;
            expect(wrapper.contains(ProjectInformationInputPrivacyList)).toBe(true);

            const popover_content: HTMLSpanElement = wrapper.find(
                "[data-test=project-information-input-privacy-text]"
            ).element;

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
            );
        });

        it("changes the tooltip text and send the 'private-wo-restr' value when the project privacy is private", () => {
            const wrapper = factory;
            const popover_content: HTMLSpanElement = wrapper.find(
                "[data-test=project-information-input-privacy-text]"
            ).element;

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
            );

            (wrapper.find("[data-test=private-wo-restr]")
                .element as HTMLOptionElement).selected = true;

            wrapper.find("[data-test=project-information-input-privacy-list]").trigger("change");

            expect(wrapper.emitted().input).toEqual([["private-wo-restr"]]);

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to private. Only project members can access its content. Restricted users are not allowed in this project."
            );
        });

        it("changes the tooltip text and send the 'private' value when the project privacy is private without restricted", () => {
            const wrapper = factory;
            const popover_content: HTMLSpanElement = wrapper.find(
                "[data-test=project-information-input-privacy-text]"
            ).element;

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
            );

            (wrapper.find("[data-test=private]").element as HTMLOptionElement).selected = true;

            wrapper.find("[data-test=project-information-input-privacy-list]").trigger("change");

            expect(wrapper.emitted().input).toEqual([["private"]]);

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to private including restricted. Only project members can access its content. Restricted users are allowed in this project."
            );
        });

        it("changes the tooltip text and send the 'unrestricted' value when the project privacy is Public incl. restricted", () => {
            const wrapper = factory;
            const popover_content: HTMLSpanElement = wrapper.find(
                "[data-test=project-information-input-privacy-text]"
            ).element;

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
            );

            (wrapper.find("[data-test=unrestricted]").element as HTMLOptionElement).selected = true;

            wrapper.find("[data-test=project-information-input-privacy-list]").trigger("change");

            expect(wrapper.emitted().input).toEqual([["unrestricted"]]);

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public including restricted. By default, its content is available to all authenticated users. Please note that more restrictive permissions might exist on some items."
            );
        });

        it("changes the tooltip text and send the 'public' value when the project privacy is Public", () => {
            const wrapper = factory;
            const popover_content: HTMLSpanElement = wrapper.find(
                "[data-test=project-information-input-privacy-text]"
            ).element;

            (wrapper.find("[data-test=public]").element as HTMLOptionElement).selected = true;

            wrapper.find("[data-test=project-information-input-privacy-list]").trigger("change");

            expect(wrapper.emitted().input).toEqual([["public"]]);

            expect(popover_content.innerHTML.trim()).toEqual(
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
            );
        });
    });
    describe("The selected default project visibility when the component is mounted -", () => {
        it("Should select the  'Public' by default", async () => {
            const state: State = {
                project_default_visibility: "public"
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store }
            });

            expect((wrapper.find("[data-test=public]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });
        it("Should select the  'Public incl. restricted' by default", async () => {
            const state = {
                project_default_visibility: "unrestricted"
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store }
            });

            expect(
                (wrapper.find("[data-test=unrestricted]").element as HTMLOptionElement).selected
            ).toBe(true);
        });

        it("Should select the  'Private' by default", async () => {
            const state = {
                project_default_visibility: "private-wo-restr"
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store }
            });

            expect(
                (wrapper.find("[data-test=private-wo-restr]").element as HTMLOptionElement).selected
            ).toBe(true);
        });

        it("Should select the  'Private incl. restricted' by default", async () => {
            const state = {
                project_default_visibility: "private"
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store }
            });

            expect(
                (wrapper.find("[data-test=private]").element as HTMLOptionElement).selected
            ).toBe(true);
        });
    });
});
