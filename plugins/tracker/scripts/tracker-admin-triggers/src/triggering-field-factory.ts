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

import { getTriggeringField } from "./triggering-field";
import type { BuilderData, SubmitData, TriggeringField, TriggeringFieldFactory } from "./type";
import type { GetText } from "@tuleap/gettext";

export function getTriggeringFieldFactory(gettext_provider: GetText): TriggeringFieldFactory {
    const triggering_fields: Array<TriggeringField> = [];
    let counter = 0;

    function removeTriggeringField(id: number): void {
        triggering_fields[id].remove();
        delete triggering_fields[id];
    }

    return {
        triggering_fields,
        addTriggeringField(builder_data: BuilderData): TriggeringField {
            const triggering_field = getTriggeringField(counter, builder_data, gettext_provider);

            triggering_fields[counter] = triggering_field;
            counter++;

            return triggering_field;
        },
        removeTriggeringField,
        reset(): void {
            triggering_fields.forEach((triggering_field) => {
                if (triggering_field.isInitialCondition()) {
                    triggering_field.removeAllOptions();
                } else {
                    removeTriggeringField(triggering_field.id);
                }
            });
        },
        getTriggeringFieldsAsJSON(): SubmitData["triggering_fields"] | null {
            const triggering_fields_as_JSON = triggering_fields.reduce(
                (triggering_fields_as_JSON: SubmitData["triggering_fields"], triggering_field) => {
                    const json = triggering_field.toJSON();

                    if (json !== null) {
                        return [...triggering_fields_as_JSON, json];
                    }

                    return triggering_fields_as_JSON;
                },
                [],
            );
            if (triggering_fields.length !== triggering_fields_as_JSON.length) {
                return null;
            }

            return triggering_fields_as_JSON;
        },
    };
}
