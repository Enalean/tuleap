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

import { getJSON, uri } from "@tuleap/fetch-result";
import type { BuilderData, SubmitData, TriggerField, TriggeringField } from "./type";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";
import { getTriggeringFieldFactory } from "./triggering-field-factory";
import { displayExistingTriggers } from "./display-triggers";
import type { GetText } from "@tuleap/gettext";

export function initializeTriggers(container: HTMLElement, gettext_provider: GetText): void {
    displayExistingTriggers(container);

    const tracker_id = parseInt(getAttributeOrThrow(container, "data-tracker-id"), 10);
    const form = selectOrThrow(document, "#triggers_form", HTMLFormElement);
    const no_children_info = selectOrThrow(document, "#triggers_no_children");
    const select_field = selectOrThrow(document, "#trigger_target_field_name", HTMLSelectElement);
    const select_value = selectOrThrow(document, "#trigger_target_field_value", HTMLSelectElement);
    const factory = getTriggeringFieldFactory(gettext_provider);
    const add_condition_button = selectOrThrow(
        document,
        "#trigger_add_condition",
        HTMLButtonElement,
    );
    const add_trigger_button = selectOrThrow(document, "#add_new_trigger", HTMLButtonElement);
    const add_trigger_cancel_button = selectOrThrow(
        document,
        "#trigger_add_cancel",
        HTMLButtonElement,
    );
    const submit_button = selectOrThrow(document, "#trigger_submit_new", HTMLButtonElement);
    const add_trigger_container = selectOrThrow(document, "#trigger_create_new");

    getJSON<BuilderData>(
        uri`/plugins/tracker/?tracker=${tracker_id}&func=admin-get-triggers-rules-builder-data`,
    ).map((builder_data: BuilderData): void => {
        if (Object.keys(builder_data.triggers).length === 0) {
            showNoChildrenMessage(form, no_children_info);
        } else {
            showAddLink(form);
        }
        populateTargetFields(builder_data, select_field, select_value);
        addFirstTriggeringField(factory.addTriggeringField(builder_data));
        add_trigger_button.addEventListener("click", () => {
            add_trigger_button.classList.add("add-new-trigger-hidden");
            add_trigger_container.hidden = false;
        });
        add_trigger_cancel_button.addEventListener("click", () => {
            add_trigger_button.classList.remove("add-new-trigger-hidden");
            add_trigger_container.hidden = true;
            factory.reset();
            add_trigger_cancel_button.form?.reset();
        });
        add_condition_button.addEventListener("click", () => {
            const triggering_field = factory.addTriggeringField(builder_data);

            triggering_field.activateDeleteButton(factory);
            triggering_field.makeOperatorDynamic();
        });
        submit_button.addEventListener("click", async () => {
            const trigger_data = toJSON();

            if (!trigger_data) {
                //eslint-disable-next-line no-alert
                alert(container.getAttribute("data-save-missing-data"));
                return;
            }

            const form_data = new FormData(form);
            form_data.set("trigger_data", JSON.stringify(trigger_data));

            const response = await fetch(form.action, {
                method: "post",
                body: form_data,
            });

            if (response.status !== 200) {
                //eslint-disable-next-line no-alert
                alert(await response.text());
                return;
            }
            window.location.reload();

            function toJSON(): SubmitData | null {
                const select_condition = selectOrThrow(
                    document,
                    "#trigger_condition_quantity",
                    HTMLSelectElement,
                );
                const triggering_fields_as_JSON = factory.getTriggeringFieldsAsJSON();
                const target_as_JSON = getTarget();

                if (target_as_JSON === null || triggering_fields_as_JSON === null) {
                    return null;
                }

                return {
                    target: target_as_JSON,
                    condition: select_condition.value,
                    triggering_fields: triggering_fields_as_JSON,
                };

                function getTarget(): SubmitData["target"] | null {
                    const field_id = select_field.value,
                        field_value_id = select_value.value,
                        field_label = select_field.options[select_field.selectedIndex].textContent,
                        field_value_label =
                            select_value.options[select_value.selectedIndex].textContent;

                    if (field_id === "" || field_value_id === "") {
                        return null;
                    }

                    return {
                        field_id: field_id,
                        field_value_id: field_value_id,
                        field_label: field_label,
                        field_value_label: field_value_label,
                    };
                }
            }
        });
    });
}

function showNoChildrenMessage(form: HTMLElement, no_children_info: HTMLElement): void {
    form.hidden = true;
    no_children_info.hidden = false;
}

function showAddLink(form: HTMLElement): void {
    form.hidden = false;
}

function populateTargetFields(
    builder_data: BuilderData,
    select_field: HTMLSelectElement,
    select_value: HTMLSelectElement,
): void {
    Object.values(builder_data.targets).forEach((field) => {
        const option = document.createElement("option");
        option.value = String(field.id);
        option.textContent = field.name;

        select_field.appendChild(option);
    });

    makeTargetFieldValuesDynamic(builder_data, select_field, select_value);
}
function makeTargetFieldValuesDynamic(
    builder_data: BuilderData,
    select_field: HTMLSelectElement,
    select_value: HTMLSelectElement,
): void {
    select_field.addEventListener("change", () => {
        const field_id: number = parseInt(select_field.value, 10);

        removeExistingValues();

        if (typeof builder_data.targets[field_id] === "undefined") {
            return;
        }

        populateTargetFieldValues(builder_data.targets[field_id], select_value);
    });
}

function removeExistingValues(): void {
    document.querySelectorAll(".trigger-target-field-value").forEach(function (field_value) {
        field_value.remove();
    });
}

function populateTargetFieldValues(
    field_values: TriggerField,
    select_value: HTMLSelectElement,
): void {
    field_values.values.forEach(function (field_value) {
        const option = document.createElement("option");
        option.classList.add("trigger-target-field-value");
        option.value = String(field_value.id);
        option.textContent = field_value.label;

        select_value.appendChild(option);
    });
}

function addFirstTriggeringField(triggering_field: TriggeringField): void {
    triggering_field.removeDeleteButton();
    triggering_field.addConditionSelector();
    triggering_field.makeOperatorDynamic();
}
