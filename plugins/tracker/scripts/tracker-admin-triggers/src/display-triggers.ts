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

import { selectOrThrow } from "@tuleap/dom";
import type { ExistingTriggers } from "./type";

export function displayExistingTriggers(container: HTMLElement): void {
    const existing_triggers_table = selectOrThrow(container, "tbody");
    const triggering_field_template = detachElement(
        selectOrThrow(existing_triggers_table, ".trigger_description_triggering_field"),
    );
    const trigger_template = detachElement(selectOrThrow(existing_triggers_table, "tr"));
    trigger_template.hidden = false;

    const existing: ExistingTriggers = JSON.parse(container.getAttribute("data-existing") || "[]");
    existing.forEach(function (trigger) {
        displayTrigger(
            trigger,
            existing_triggers_table,
            triggering_field_template,
            trigger_template,
        );
    });
}

function detachElement(element: HTMLElement): HTMLElement {
    element.remove();

    return element;
}

function displayTrigger(
    trigger_as_JSON: ExistingTriggers[0],
    existing_triggers_table: HTMLElement,
    triggering_field_template: HTMLElement,
    trigger_template: HTMLElement,
): void {
    const trigger_id = trigger_as_JSON.id,
        trigger_element = addTriggerContainer(trigger_id);

    selectOrThrow(trigger_element, ".trigger_description_target_field_name").textContent =
        trigger_as_JSON.target.field_label;
    selectOrThrow(trigger_element, ".trigger_description_target_field_value").textContent =
        trigger_as_JSON.target.field_value_label;

    trigger_as_JSON.triggering_fields.forEach(function (triggering_field) {
        addTriggeringField(triggering_field, trigger_element);
    });
    removeFirstOperator(trigger_element);

    function addTriggerContainer(trigger_id: number): HTMLElement {
        existing_triggers_table.append(trigger_template.cloneNode(true));

        const trigger_element = selectOrThrow(existing_triggers_table, ".trigger_row:last-child");
        trigger_element.setAttribute("data-trigger-id", String(trigger_id));
        for (const form of trigger_element.getElementsByTagName("form")) {
            const input_trigger_id = document.createElement("input");
            input_trigger_id.setAttribute("type", "hidden");
            input_trigger_id.setAttribute("name", "trigger_id");
            input_trigger_id.setAttribute("value", String(trigger_id));
            form.appendChild(input_trigger_id);
        }

        return trigger_element;
    }

    function addTriggeringField(
        triggering_field: ExistingTriggers[0]["triggering_fields"][0],
        trigger_element: HTMLElement,
    ): void {
        const triggering_fields_list = selectOrThrow(
            trigger_element,
            ".trigger_description_triggering_fields",
        );
        triggering_fields_list.append(triggering_field_template.cloneNode(true));
        const triggering_field_element = selectOrThrow(
            triggering_fields_list,
            ".trigger_description_triggering_field:last-child",
        );

        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_field_operator",
        ).textContent = String(
            document
                .getElementById("triggers_existing")
                ?.getAttribute("data-operator-" + trigger_as_JSON.condition),
        );
        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_field_quantity",
        ).textContent = String(
            document
                .getElementById("triggers_existing")
                ?.getAttribute("data-children-" + trigger_as_JSON.condition + "-short"),
        );
        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_have_field",
        ).textContent = String(
            document
                .getElementById("triggers_existing")
                ?.getAttribute("data-have-field-" + trigger_as_JSON.condition),
        );
        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_field_tracker",
        ).textContent = triggering_field.tracker_name;
        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_field_field_name",
        ).textContent = triggering_field.field_label;
        selectOrThrow(
            triggering_field_element,
            ".trigger_description_triggering_field_field_value",
        ).textContent = triggering_field.field_value_label;

        if (triggering_fields_list.children.length > 1) {
            selectOrThrow(
                triggering_field_element,
                ".trigger_description_triggering_field_when",
            ).hidden = true;
        }
    }

    function removeFirstOperator(trigger_element: HTMLElement): void {
        selectOrThrow(
            trigger_element,
            ".trigger_description_triggering_field_operator",
        ).textContent = "";
    }
}
