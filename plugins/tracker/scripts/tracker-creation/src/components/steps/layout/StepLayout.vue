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
    <div class="tracker-creation-step">
        <div class="tracker-creation-starting-point-illustration">
            <step-layout-svg-illustration />
        </div>
        <div class="tracker-creation-step-content">
            <div class="tracker-creation-step-info">
                <h1 v-translate>Create a new tracker</h1>
                <slot name="step_info"></slot>
            </div>
            <div class="tracker-creation-step-interactive-content">
                <h3 data-test="platform-template-name">{{ platform_template_name }}</h3>
                <slot name="interactive_content"></slot>
                <h3 v-translate>For advanced users</h3>
                <slot name="interactive_content_advanced"></slot>
            </div>
            <step-navigation-buttons
                v-bind:previous-step-name="previousStepName"
                v-bind:next-step-name="nextStepName"
            />
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import StepLayoutSvgIllustration from "./StepLayoutSvgIllustration.vue";
import StepNavigationButtons from "./StepNavigationButtons.vue";
import { sprintf } from "sprintf-js";
import { State } from "vuex-class";

@Component({
    components: {
        StepLayoutSvgIllustration,
        StepNavigationButtons
    }
})
export default class StepLayout extends Vue {
    @Prop({ required: false })
    readonly nextStepName!: string;

    @Prop({ required: false })
    readonly previousStepName!: string;

    @State
    company_name!: string;

    get platform_template_name(): string {
        if (this.company_name === "Tuleap") {
            return this.$gettext("Custom templates");
        }
        return sprintf(this.$gettext("%s templates"), this.company_name);
    }
}
</script>
