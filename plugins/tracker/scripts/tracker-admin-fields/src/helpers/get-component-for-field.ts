/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import { type Component } from "vue";
import ContainerColumn from "../components/FormElements/ContainerColumn.vue";
import { CONTAINER_COLUMN, CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import ContainerFieldset from "../components/FormElements/ContainerFieldset.vue";
import BaseField from "../components/FormElements/BaseField.vue";

export function getComponentForField(field: StructureFields): Component {
    if (field.type === CONTAINER_COLUMN) {
        return ContainerColumn;
    } else if (field.type === CONTAINER_FIELDSET) {
        return ContainerFieldset;
    }

    return BaseField;
}
