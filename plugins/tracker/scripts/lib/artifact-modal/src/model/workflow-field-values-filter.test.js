/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { enforceWorkflowTransitions } from "./workflow-field-values-filter.js";

describe("workflow-field-values-filter", () => {
    describe("enforceWorkflowTransitions() -", () => {
        it(`Given a selected value, a selectbox field
                and a collection representing the workflow transitions
                when I enforce the workflow transitions
                then the field's values will be only the available transitions value`, () => {
            const field = {
                field_id: 764,
                permissions: ["read", "update", "create"],
                type: "sb",
                values: [{ id: 448 }, { id: 6 }, { id: 23 }, { id: 908 }, { id: 71 }],
            };
            const workflow = {
                field_id: 764,
                is_used: "1",
                transitions: [
                    {
                        from_id: 448,
                        to_id: 6,
                    },
                    {
                        from_id: 448,
                        to_id: 23,
                    },
                    {
                        from_id: 908,
                        to_id: 71,
                    },
                ],
            };

            enforceWorkflowTransitions(448, field, workflow);

            expect(field.values).toContainEqual({ id: 448 });
            expect(field.values).toContainEqual({ id: 6 });
            expect(field.values).toContainEqual({ id: 23 });
            expect(field.values).not.toContainEqual({ id: 908 });
            expect(field.values).not.toContainEqual({ id: 71 });
            expect(field.has_transitions).toBeTruthy();
        });
    });
});
