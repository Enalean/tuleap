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

import { shallowMount } from "@vue/test-utils";
import AgreementModal from "./AgreementModal.vue";
import * as rest_querier from "../../../api/rest-querier";
import * as tlp from "tlp";
import type { Modal } from "tlp";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        createModal: jest.fn(),
    };
});

describe("AgreementModal -", () => {
    it("Load policy agreement content and display it in the modal", () => {
        const modal_show = jest.fn();

        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                show: modal_show,
            } as unknown as Modal;
        });

        const get_term_of_service = jest
            .spyOn(rest_querier, "getTermOfService")
            .mockReturnValue(Promise.resolve("My custom tos"));

        shallowMount(AgreementModal, {
            global: {
                ...getGlobalTestOptions(),
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
            },
        });

        emitter.emit("show-agreement");

        expect(get_term_of_service).toHaveBeenCalled();
    });
});
