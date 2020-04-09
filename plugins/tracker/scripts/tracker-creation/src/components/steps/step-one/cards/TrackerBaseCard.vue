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
    <div
        class="tracker-creation-template-card"
        v-bind:class="{
            'tracker-creation-template-card-active': is_option_active,
        }"
    >
        <label class="tlp-card tlp-card-selectable tracker-creation-template-card-label">
            <input
                type="radio"
                class="tracker-creation-template-card-radio-button"
                name="selected-option"
                v-bind:data-test="`selected-option-${optionName}`"
                v-on:change="setActiveOption(optionName)"
            />
            <slot name="content" v-bind:is-option-active="is_option_active"></slot>
        </label>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { State, Mutation } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import { CreationOptions } from "../../../../store/type";

@Component
export default class TrackerBaseCard extends Vue {
    @State
    readonly active_option!: CreationOptions | string;

    @Mutation
    readonly setActiveOption!: (option: CreationOptions | string) => void;

    @Prop({ required: true })
    readonly optionName!: CreationOptions | string;

    get is_option_active(): boolean {
        return this.active_option === this.optionName;
    }
}
</script>
