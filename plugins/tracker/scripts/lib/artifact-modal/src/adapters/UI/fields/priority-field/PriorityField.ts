/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";

export type HostElement = PriorityField & HTMLElement;

interface FieldPriorityType {
    readonly label: string;
    readonly value: number;
}

export interface PriorityField {
    readonly field: FieldPriorityType;
}

export const renderPriorityField = (host: PriorityField): UpdateFunction<PriorityField> => html`
    <div class="tlp-property">
        <label class="tlp-label" data-test="priority-field-label">${host.field.label}</label>
        <p data-test="priority-field-value">${host.field.value}</p>
    </div>
`;

export const PriorityField = define<PriorityField>({
    tag: "tuleap-artifact-modal-priority-field",
    field: (host, field) => field,
    render: renderPriorityField,
});
