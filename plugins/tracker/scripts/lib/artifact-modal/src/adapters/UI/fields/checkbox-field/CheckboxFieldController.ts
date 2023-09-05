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

import { CheckboxFieldPresenter } from "./CheckboxFieldPresenter";
import type { CheckboxFieldType } from "./CheckboxFieldType";

export interface CheckboxFieldControllerType {
    buildPresenter: () => CheckboxFieldPresenter;
    setCheckboxValue: (
        value_id: number,
        value_index: number,
        is_checked: boolean,
    ) => CheckboxFieldPresenter;
}

export const CheckboxFieldController = (
    field: CheckboxFieldType,
    bind_value_ids: Array<number | null>,
    is_field_disabled: boolean,
): CheckboxFieldControllerType => ({
    buildPresenter(): CheckboxFieldPresenter {
        return CheckboxFieldPresenter.fromField(field, bind_value_ids, is_field_disabled);
    },
    setCheckboxValue(
        value_id: number,
        value_index: number,
        is_checked: boolean,
    ): CheckboxFieldPresenter {
        bind_value_ids[value_index] = is_checked ? value_id : null;
        return this.buildPresenter();
    },
});
