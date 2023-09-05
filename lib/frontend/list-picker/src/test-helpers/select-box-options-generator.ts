/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

/*
 * For testing purpose only
 */

export function appendSimpleOptionsToSourceSelectBox(select: HTMLSelectElement): void {
    select.insertAdjacentHTML(
        "afterbegin",
        `
      <option value="100" data-item-id="list-picker-item-value-100">None</option>
      <option value="value_0" data-item-id="list-picker-item-value_0">Value 0</option>
      <option value="value_1" data-item-id="list-picker-item-value_1">Value 1</option>
      <option value="value_2" data-item-id="list-picker-item-value_2">Value 2</option>
      <option value="value_3" label="Value 3" data-item-id="list-picker-item-value_3"></option>
      <option
        value="value_colored"
        label="Value Colored"
        data-item-id="list-picker-item-value_colored"
        data-color-value="acid-green"
      ></option>
      <option
        value="peraltaj"
        label="Jack Peralta"
        data-item-id="list-picker-item-peraltaj"
        data-avatar-url="/url/to/jdoe/avatar.png"
      ></option>
      <option
        value="bad_colored"
        label="Bad Colored"
        data-item-id="list-picker-item-bad_colored"
        data-color-value="#ffffff"
      ></option>
    `,
    );
}

export function appendGroupedOptionsToSourceSelectBox(select: HTMLSelectElement): void {
    select.insertAdjacentHTML(
        "afterbegin",
        `
      <optgroup label="Group 1">
        <option value="value_0" data-item-id="list-picker-item-value0">Value 0</option>
        <option value="value_1" data-item-id="list-picker-item-value1">Value 1</option>
        <option value="value_2" data-item-id="list-picker-item-value2">Value 2</option>
      </optgroup>
      <optgroup label="Group 2">
        <option value="value_3" data-item-id="list-picker-item-value3">Value 3</option>
        <option value="value_4" data-item-id="list-picker-item-value4">Value 4</option>
        <option value="value_5" data-item-id="list-picker-item-value5" disabled="disabled">Value 5</option>
      </optgroup>
    `,
    );
}
