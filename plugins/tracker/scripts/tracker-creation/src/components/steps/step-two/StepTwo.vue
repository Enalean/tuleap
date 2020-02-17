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
            <form v-on:submit="setFormHasBeenSubmitted" method="post" id="tracker-creation-form">
                <field-name />
                <field-shortname />
                <field-description />
                <field-tracker-template-id />
            </form>
        </template>
    </step-layout>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import StepLayout from "../layout/StepLayout.vue";
import StepTwoInfo from "./StepTwoInfo.vue";
import FieldName from "./creation-fields/FieldName.vue";
import FieldShortname from "./creation-fields/FieldShortname.vue";
import FieldDescription from "./creation-fields/FieldDescription.vue";
import FieldTrackerTemplateId from "./creation-fields/FieldTrackerTemplateId.vue";

@Component({
    components: {
        StepLayout,
        StepTwoInfo,
        FieldName,
        FieldShortname,
        FieldDescription,
        FieldTrackerTemplateId
    }
})
export default class StepTwo extends Vue {
    private has_form_been_submitted = false;

    mounted(): void {
        window.addEventListener("beforeunload", this.beforeUnload);
    }

    beforeDestroy(): void {
        window.removeEventListener("beforeunload", this.beforeUnload);
    }

    setFormHasBeenSubmitted(): void {
        this.has_form_been_submitted = true;
    }

    beforeUnload(event: Event): void {
        if (this.has_form_been_submitted) {
            delete event.returnValue;
        } else {
            event.preventDefault();
            event.returnValue = false;
        }
    }
}
</script>
