<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="tlp-form-element document-search-criterion document-search-criterion-text">
        <label class="tlp-label" v-bind:for="id">{{ label }}</label>
        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
            v-bind:data-test="id"
        />
    </div>
</template>

<script lang="ts">
import Component from "vue-class-component";
import Vue from "vue";
import { Prop } from "vue-property-decorator";

@Component
export default class CriterionText extends Vue {
    @Prop({ required: true })
    readonly name!: string;

    @Prop({ required: true })
    readonly value!: string;

    @Prop({ required: true })
    readonly label!: string;

    get id(): string {
        return "document-criterion-text-" + this.name;
    }
}
</script>
