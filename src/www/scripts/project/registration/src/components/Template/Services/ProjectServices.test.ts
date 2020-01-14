/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import ProjectServices from "./ProjectServices.vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { ServiceData, TemplateData } from "../../../type";
import * as rest_querier from "../../../api/rest-querier";

describe("ProjectServices", () => {
    let wrapper: Wrapper<ProjectServices>;

    beforeEach(async () => {
        const project: TemplateData = {
            id: "101",
            title: "test propject"
        } as TemplateData;
        wrapper = shallowMount(ProjectServices, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { project }
        });
    });

    it("Display a link", () => {
        expect(wrapper.find("[data-test=project-service-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=project-service-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=project-service-error]").exists()).toBeFalsy();
    });

    it("Displays a spinner when services are loading", () => {
        wrapper.vm.$data.is_loading = true;

        expect(wrapper.find("[data-test=project-service-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=project-service-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=project-service-error]").exists()).toBeFalsy();
    });

    it("Displays a error when error is thrown", () => {
        wrapper.vm.$data.has_error = true;

        expect(wrapper.find("[data-test=project-service-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=project-service-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=project-service-error]").exists()).toBeTruthy();
    });

    it("Uses cache and not relaod services", () => {
        const service_a: ServiceData = {
            id: "101",
            label: "Document",
            icon: "fa-folder"
        } as ServiceData;

        const service_b: ServiceData = {
            id: "102",
            label: "Taskboard",
            icon: "fa-gird"
        } as ServiceData;

        wrapper.vm.$data.services = [service_a, service_b];

        const load_services = jest.spyOn(rest_querier, "getServices");
        expect(load_services).not.toHaveBeenCalled();

        expect(wrapper.find("[data-test=project-service-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=project-modal-services-list101]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=project-modal-services-list102]").exists()).toBeTruthy();
    });
});
