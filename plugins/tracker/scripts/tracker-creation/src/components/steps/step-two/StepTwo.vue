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
    <step-layout previous-step-name="step-1">
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
                <field-tracker-template-id
                    v-if="is_a_duplication || is_a_duplication_of_a_tracker_from_another_project"
                />
                <field-tracker-empty v-if="is_created_from_empty" />
            </form>
        </template>
    </step-layout>
</template>
<script lang="ts">
import Vue from "vue";
import { Mutation, State, Getter } from "vuex-class";
import { Component, Watch, Ref } from "vue-property-decorator";
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

@Component({
    components: {
        FieldTrackerEmpty,
        StepLayout,
        StepTwoInfo,
        FieldName,
        FieldShortname,
        FieldDescription,
        FieldTrackerTemplateId,
        FieldCsrfToken,
        FieldChosenTemplate,
        FieldTrackerColor,
    },
})
export default class StepTwo extends Vue {
    @State
    readonly has_form_been_submitted!: boolean;

    @Getter
    readonly is_created_from_empty!: boolean;

    @Getter
    readonly is_a_duplication!: boolean;

    @Getter
    readonly is_a_xml_import!: boolean;

    @Getter
    readonly is_a_duplication_of_a_tracker_from_another_project!: boolean;

    @Mutation
    readonly initTrackerNameWithTheSelectedTemplateName!: () => void;

    @Mutation
    readonly initTrackerNameWithTheSelectedProjectTrackerTemplateName!: () => void;

    @Mutation
    readonly cancelCreationFormSubmition!: () => void;

    @Mutation
    readonly reinitTrackerToBeCreatedData!: () => void;

    @State
    readonly selected_xml_file_input!: HTMLInputElement;

    @Ref("tracker_creation_form")
    readonly creation_form!: HTMLFormElement;

    @Watch("has_form_been_submitted", { deep: true })
    submitTheForm(current_value: boolean): void {
        if (current_value === true && this.creation_form.checkValidity()) {
            this.creation_form.submit();
        } else {
            this.cancelCreationFormSubmition();
            this.creation_form.reportValidity();
        }
    }

    mounted(): void {
        if (this.is_a_duplication) {
            this.initTrackerNameWithTheSelectedTemplateName();
        } else if (this.is_created_from_empty) {
            this.reinitTrackerToBeCreatedData();
        } else if (this.is_a_xml_import) {
            const form = this.$refs.tracker_creation_form;

            if (!(form instanceof Element)) {
                return;
            }

            form.appendChild(this.selected_xml_file_input);
        } else if (this.is_a_duplication_of_a_tracker_from_another_project) {
            this.initTrackerNameWithTheSelectedProjectTrackerTemplateName();
        }

        window.addEventListener("beforeunload", this.beforeUnload);

        const previous_error = document.getElementById("feedback");
        if (previous_error instanceof HTMLElement) {
            const parent = previous_error.parentNode;
            if (parent instanceof HTMLElement) {
                parent.removeChild(previous_error);
            }
        }
    }

    beforeDestroy(): void {
        window.removeEventListener("beforeunload", this.beforeUnload);
    }

    beforeUnload(event: Event): void {
        if (this.has_form_been_submitted) {
            delete event.returnValue;
        } else {
            event.preventDefault();
            event.returnValue = false;
        }
    }

    get form_enctype(): string {
        if (this.is_a_xml_import) {
            return "multipart/form-data";
        }

        return "application/x-www-form-urlencoded";
    }
}
</script>
