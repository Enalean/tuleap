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
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { DISPLAY_TYPE_COLUMN } from "@/sections/readonly-fields/AvailableReadonlyFields";

export const ConfigurationFieldStub = {
    build: (): ConfigurationField => {
        return {
            field_id: 123,
            label: "String Field",
            type: "string",
            display_type: DISPLAY_TYPE_COLUMN,
            can_display_type_be_changed: true,
        };
    },

    withFieldId: (field_id: number): ConfigurationField => ({
        ...ConfigurationFieldStub.build(),
        field_id,
    }),
    withLabel: (label: string): ConfigurationField => ({
        ...ConfigurationFieldStub.build(),
        label,
    }),
    withFixedDisplayType: (): ConfigurationField => ({
        ...ConfigurationFieldStub.build(),
        can_display_type_be_changed: false,
    }),
};
