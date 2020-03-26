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

import EventBus from "../../../helpers/event-bus";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import AgreementModal from "./AgreementModal.vue";
import * as rest_querier from "../../../api/rest-querier";
import * as tlp from "tlp";
import { Modal } from "tlp";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        modal: jest.fn(),
    };
});

describe("AgreementModal -", () => {
    it("Load policy agreement content and display it in the modal", async () => {
        const modal_show = jest.fn();

        jest.spyOn(tlp, "modal").mockImplementation(() => {
            return ({
                show: modal_show,
            } as unknown) as Modal;
        });

        const get_term_of_service = jest
            .spyOn(rest_querier, "getTermOfService")
            .mockReturnValue(Promise.resolve("My custom tos"));

        shallowMount(AgreementModal, {
            localVue: await createProjectRegistrationLocalVue(),
        });

        EventBus.$emit("show-agreement");

        expect(get_term_of_service).toHaveBeenCalled();
    });
});
