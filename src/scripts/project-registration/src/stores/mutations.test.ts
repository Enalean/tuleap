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

import type { TemplateData } from "../type";
import { createPinia, setActivePinia } from "pinia";
import { useStore } from "./root";

describe("mutation", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    describe("setSelectedTemplate()", () => {
        it(`stores the tuleap template and make sure the company template is null`, () => {
            const store = useStore();
            store.selected_tuleap_template = null;
            store.selected_company_template = {
                title: "Whole lot company",
                description: "I have got whole lot",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false,
            } as TemplateData;

            const selected_template = {
                title: "scrum template",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            };
            store.setSelectedTemplate(selected_template);
            expect(store.selected_tuleap_template).toStrictEqual(selected_template);
            expect(store.selected_company_template).toBeNull();
        });

        it(`stores the company template and make sure the tuleap template is null`, () => {
            const store = useStore();
            store.selected_tuleap_template = {
                title: "scrum template",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            } as TemplateData;
            store.selected_company_template = null;

            const selected_template = {
                title: "Whole lot company",
                description: "I have got whole lot",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false,
            };
            store.setSelectedTemplate(selected_template);
            expect(store.selected_company_template).toStrictEqual(selected_template);
            expect(store.selected_tuleap_template).toBeNull();
        });
    });
    describe("resetProjectCreationError() -", () => {
        it("reset the project creation error", () => {
            const store = useStore();
            store.error = "It does not work :(";

            store.resetProjectCreationError();
            expect(store.error).toBeNull();
        });
    });
});
