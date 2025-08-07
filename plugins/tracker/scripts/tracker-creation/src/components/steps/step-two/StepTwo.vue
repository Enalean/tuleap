<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <step-layout previous_step_name="step-1">
        <template v-slot:step_info>
            <step-two-info />
        </template>
        <template v-slot:interactive_content>
            <form
                ref="tracker_creation_form"
                method="post"
                id="tracker-creation-form"
                v-bind:enctype="form_enctype"
            >
                <field-csrf-token />
                <field-chosen-template />
                <div class="tracker-creation-form-element-group">
                    <field-name />
                    <field-tracker-color />
                </div>
                <field-shortname />
                <field-description />
                <field-tracker-template-id v-if="should_tracker_template_id_be_displayed" />
                <field-tracker-empty v-if="is_created_from_empty" />
                <field-from-jira v-if="is_created_from_jira" />
            </form>
        </template>
    </step-layout>
</template>
<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, computed } from "vue";
import { useState, useStore, useGetters, useMutations } from "vuex-composition-helpers";
import StepLayout from "../layout/StepLayout.vue";
import StepTwoInfo from "./StepTwoInfo.vue";
import FieldChosenTemplate from "./creation-fields/FieldChosenTemplate.vue";
import FieldName from "./creation-fields/FieldName.vue";
import FieldShortname from "./creation-fields/FieldShortname.vue";
import FieldDescription from "./creation-fields/FieldDescription.vue";
import FieldTrackerTemplateId from "./creation-fields/FieldTrackerTemplateId.vue";
import FieldCsrfToken from "./creation-fields/FieldCSRFToken.vue";
import FieldTrackerEmpty from "./creation-fields/FieldTrackerEmpty.vue";
import FieldTrackerColor from "./creation-fields/FieldTrackerColor.vue";
import FieldFromJira from "./creation-fields/FieldFromJira.vue";
import type { State } from "../../../store/type";

const { has_form_been_submitted } = useState<Pick<State, "has_form_been_submitted">>([
    "has_form_been_submitted",
]);

const {
    is_created_from_empty,
    is_a_duplication,
    is_a_xml_import,
    is_created_from_default_template,
    is_created_from_jira,
    is_a_duplication_of_a_tracker_from_another_project,
} = useGetters([
    "is_created_from_empty",
    "is_a_duplication",
    "is_a_xml_import",
    "is_created_from_default_template",
    "is_created_from_jira",
    "is_a_duplication_of_a_tracker_from_another_project",
]);

const {
    initTrackerNameWithTheSelectedTemplateName,
    initTrackerNameWithTheSelectedProjectTrackerTemplateName,
    cancelCreationFormSubmition,
    reinitTrackerToBeCreatedData,
} = useMutations([
    "initTrackerNameWithTheSelectedTemplateName",
    "initTrackerNameWithTheSelectedProjectTrackerTemplateName",
    "cancelCreationFormSubmition",
    "reinitTrackerToBeCreatedData",
]);

const store = useStore();

const tracker_creation_form = ref<HTMLFormElement>();

watch(
    has_form_been_submitted,
    () => {
        submitTheForm(has_form_been_submitted.value);
    },
    { deep: true },
);

function submitTheForm(current_value: boolean): void {
    if (current_value && tracker_creation_form.value?.checkValidity()) {
        tracker_creation_form.value.submit();
    } else {
        cancelCreationFormSubmition();
        tracker_creation_form.value?.reportValidity();
    }
}

onMounted(() => {
    if (is_created_from_default_template.value) {
        initTrackerNameWithTheSelectedTemplateName();
    } else if (is_a_duplication.value) {
        initTrackerNameWithTheSelectedTemplateName();
    } else if (is_created_from_empty.value) {
        reinitTrackerToBeCreatedData();
    } else if (is_a_xml_import.value) {
        if (!(tracker_creation_form.value instanceof Element)) {
            return;
        }

        tracker_creation_form.value.appendChild(store.state.selected_xml_file_input);
    } else if (is_a_duplication_of_a_tracker_from_another_project.value) {
        initTrackerNameWithTheSelectedProjectTrackerTemplateName();
    }

    window.addEventListener("beforeunload", beforeUnload);

    const previous_error = document.getElementById("feedback");
    if (previous_error instanceof HTMLElement) {
        const parent = previous_error.parentNode;
        if (parent instanceof HTMLElement) {
            parent.removeChild(previous_error);
        }
    }
});

onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", beforeUnload);
});

function beforeUnload(event: Event): void {
    if (!has_form_been_submitted.value) {
        event.preventDefault();
    }
}

const form_enctype = computed((): string => {
    if (is_a_xml_import.value) {
        return "multipart/form-data";
    }

    return "application/x-www-form-urlencoded";
});

const should_tracker_template_id_be_displayed = computed((): boolean => {
    return (
        is_created_from_default_template.value ||
        is_a_duplication.value ||
        is_a_duplication_of_a_tracker_from_another_project.value
    );
});
</script>
