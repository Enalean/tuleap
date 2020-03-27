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
    <div class="tracker-creation-navigation">
        <div>
            <back-to-legacy />
        </div>

        <div class="tracker-creation-navigation-buttons">
            <router-link
                v-if="previousStepName"
                v-bind:to="{ name: previousStepName }"
                class="tracker-creation-previous-step-button"
                data-test="button-back"
            >
                <i class="fa fa-long-arrow-left"></i>
                <span class="tracker-creation-previous-step-button-text" v-translate>Back</span>
            </router-link>

            <button
                v-if="nextStepName"
                v-on:click="goToNextStepIfGood"
                class="tlp-button-primary tlp-button-large tracker-creation-next-step-button"
                type="button"
                v-bind:class="{ 'tlp-button-disabled': !is_ready_for_step_2 }"
                v-bind:disabled="!is_ready_for_step_2"
                data-test="button-next"
            >
                <translate>Next</translate>
                <i
                    class="fa tlp-button-icon-right"
                    v-bind:class="{
                        'fa-long-arrow-right ': !is_parsing_a_xml_file,
                        'fa-circle-o-notch fa-spin': is_parsing_a_xml_file,
                    }"
                ></i>
            </button>
            <button
                v-else
                class="tlp-button-primary tlp-button-large tracker-creation-submit-button"
                type="submit"
                data-test="button-create-my-tracker"
                v-bind:class="{
                    'tlp-button-disabled': !is_ready_to_submit || has_form_been_submitted,
                }"
                v-bind:disabled="!is_ready_to_submit || has_form_been_submitted"
                v-on:click="setCreationFormHasBeenSubmitted"
            >
                <translate>Create my tracker</translate>
                <i
                    class="tlp-button-icon-right fa"
                    v-bind:class="{
                        'fa-circle-o-notch fa-spin': has_form_been_submitted,
                        'fa-arrow-circle-o-right': !has_form_been_submitted,
                    }"
                ></i>
            </button>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Getter, State, Mutation } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import BackToLegacy from "./BackToLegacy.vue";
@Component({
    components: { BackToLegacy },
})
export default class StepNavigationButtons extends Vue {
    @Prop({ required: false })
    readonly nextStepName!: string;

    @Prop({ required: false })
    readonly previousStepName!: string;

    @Getter
    readonly is_ready_for_step_2!: boolean;

    @Getter
    readonly is_ready_to_submit!: boolean;

    @State
    readonly has_form_been_submitted!: boolean;

    @Mutation
    readonly setCreationFormHasBeenSubmitted!: () => void;

    @State
    readonly is_parsing_a_xml_file!: boolean;

    goToNextStepIfGood(): void {
        if (this.is_ready_for_step_2) {
            this.$router.push({ name: this.nextStepName });
        }
    }
}
</script>
