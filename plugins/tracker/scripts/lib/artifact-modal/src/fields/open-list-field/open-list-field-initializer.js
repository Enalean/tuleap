/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

// eslint-disable-next-line you-dont-need-lodash-underscore/uniq
import { uniq } from "lodash";

export { formatDefaultValue, formatExistingValue };

function formatDefaultValue(field) {
    const { field_id, type, permissions, default_value, bindings } = field;
    const value = {
        bind_value_objects: default_value ? [].concat(field.default_value) : [],
    };

    return {
        field_id,
        type,
        permissions,
        bindings,
        value,
    };
}

function formatExistingValue(field, artifact_value) {
    const { field_id, type, permissions, bindings } = field;
    const value = {
        bind_value_objects: uniq(artifact_value.bind_value_objects, (item) => {
            if (item.is_anonymous) {
                return item.email;
            }
            return item.id;
        }),
    };

    return {
        field_id,
        type,
        permissions,
        bindings,
        value,
    };
}
