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

export interface ComputedFieldValue {
    readonly field_id: number;
    readonly is_autocomputed: boolean;
    readonly manual_value: number | string;
}

type ComputedFieldAutoComputedValue = Pick<ComputedFieldValue, "field_id" | "is_autocomputed">;
type ComputedFieldManualValue = Pick<ComputedFieldValue, "field_id" | "manual_value">;

export function formatComputedFieldValue(
    field_value: ComputedFieldValue | undefined,
): ComputedFieldAutoComputedValue | ComputedFieldManualValue | null {
    if (field_value === undefined) {
        return null;
    }

    const is_autocomputed = field_value.is_autocomputed;
    if (!is_autocomputed && field_value.manual_value === "") {
        return null;
    }

    if (is_autocomputed) {
        const { field_id } = field_value;
        return { field_id, is_autocomputed };
    }

    const { field_id, manual_value } = field_value;
    return { field_id, manual_value };
}
