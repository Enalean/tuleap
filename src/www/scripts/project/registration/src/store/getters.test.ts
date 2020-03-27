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
import * as getters from "./getters";
import { State } from "./type";
import { TemplateData } from "../type";

describe("getters", () => {
    describe("is_template_selected", () => {
        it(`Should return false when there is no selected template`, () => {
            const state: State = {
                selected_tuleap_template: null,
                selected_company_template: null,
            } as State;
            expect(getters.is_template_selected(state)).toBe(false);
        });
        it(`Should return true when a tuleap template is choosen`, () => {
            const state: State = {
                selected_tuleap_template: {
                    title: "scrum",
                    description: "scrum desc",
                    id: "scrum_template",
                    glyph: "<svg></svg>",
                    is_built_in: true,
                } as TemplateData,
                selected_company_template: null,
            } as State;
            expect(getters.is_template_selected(state)).toBe(true);
        });
        it(`Should return true when a company template is choosen`, () => {
            const state: State = {
                selected_tuleap_template: null,
                selected_company_template: {
                    title: "scrum",
                    description: "scrum desc",
                    id: "10",
                    glyph: "<svg></svg>",
                    is_built_in: false,
                } as TemplateData,
            } as State;
            expect(getters.is_template_selected(state)).toBe(true);
        });
    });

    describe("has_error", () => {
        it(`Should return false when no error message is stored`, () => {
            const state: State = {
                error: null,
            } as State;
            expect(getters.has_error(state)).toBe(false);
        });
        it(`Should return true when a template is choosen`, () => {
            const state: State = {
                error: "Ho snap!",
            } as State;
            expect(getters.has_error(state)).toBe(true);
        });
    });
});
