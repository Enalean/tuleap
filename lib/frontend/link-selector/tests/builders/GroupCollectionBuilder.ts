/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { HTMLTemplateResult } from "lit/html.js";
import { html } from "lit/html.js";
import type { GroupCollection } from "../../src";

export const GroupCollectionBuilder = {
    withEmptyGroup: (): GroupCollection => [
        {
            label: "",
            empty_message: "irrelevant",
            items: [],
        },
    ],

    withSingleGroup: (): GroupCollection => [
        {
            label: "",
            empty_message: "irrelevant",
            items: [
                { value: "100", template: buildTemplateResult("None") },
                { value: "value_0", template: buildTemplateResult("Value 0") },
                { value: "value_1", template: buildTemplateResult("Value 1") },
                { value: "value_2", template: buildTemplateResult("Value 2") },
                { value: "value_3", template: buildTemplateResult("Value 3") },
            ],
        },
    ],

    withTwoGroups: (): GroupCollection => [
        {
            label: "Group 1",
            empty_message: "irrelevant",
            items: [
                { value: "value_0", template: buildTemplateResult("Value 0") },
                { value: "value_1", template: buildTemplateResult("Value 1") },
                { value: "value_2", template: buildTemplateResult("Value 2") },
            ],
        },
        {
            label: "Group 2",
            empty_message: "irrelevant",
            items: [
                { value: "value_3", template: buildTemplateResult("Value 3") },
                { value: "value_4", template: buildTemplateResult("Value 4") },
                { value: "value_5", template: buildTemplateResult("Value 5") },
            ],
        },
    ],
};

function buildTemplateResult(value: string): HTMLTemplateResult {
    return html`
        ${value}
    `;
}
