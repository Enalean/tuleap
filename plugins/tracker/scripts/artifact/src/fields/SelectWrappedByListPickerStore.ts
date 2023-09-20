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

type SelectIdentifier = string;

export interface SelectWrappedByListPickerStoreType {
    isWrapped(id: SelectIdentifier): boolean;
    add(id: SelectIdentifier): void;
}

export const SelectWrappedByListPickerStore = (): SelectWrappedByListPickerStoreType => {
    const identifiers_of_selects_wrapped_by_list_picker = new Set();
    return {
        isWrapped: (id: SelectIdentifier): boolean =>
            identifiers_of_selects_wrapped_by_list_picker.has(id),

        add(id: SelectIdentifier): void {
            identifiers_of_selects_wrapped_by_list_picker.add(id);
        },
    };
};
