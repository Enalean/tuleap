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
    <div class="tracker-creation-next-step-button">
        <router-link
            v-if="previousStepName"
            v-bind:to="{ name: previousStepName }"
            class="tracker-creation-previous-step-button"
            data-test="button-back"
        >
            <translate>Back</translate>
            <i class="fa fa-long-arrow-left"></i>
        </router-link>
        <button
            v-if="nextStepName"
            v-on:click="goToNextStepIfGood"
            class="tlp-button-primary"
            type="button"
            v-bind:class="{ 'tlp-button-disabled': !is_ready_for_step_2 }"
            v-bind:disabled="!is_ready_for_step_2"
            data-test="button-next"
        >
            <translate>Next</translate>
            <i class="fa fa-long-arrow-right tlp-button-icon-right"></i>
        </button>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Getter } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class StepNavigationButtons extends Vue {
    @Prop({ required: false })
    readonly nextStepName!: string;

    @Prop({ required: false })
    readonly previousStepName!: string;

    @Getter
    readonly is_ready_for_step_2!: boolean;

    goToNextStepIfGood(): void {
        if (this.is_ready_for_step_2) {
            this.$router.push({ name: this.nextStepName });
        }
    }
}
</script>
