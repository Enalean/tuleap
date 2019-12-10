<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="`input-${field.group_desc_id}`">
            {{ field.desc_name }}<i class="fa fa-asterisk" v-if="isRequired"/>
        </label>
        <input type="text"
               class="tlp-input"
               v-bind:placeholder="field.desc_description"
               v-bind:id="`input-${field.group_desc_id}`"
               v-if="field.desc_type ==='line'"
               v-bind:required="isRequired"
               v-on:input="updateField(field.group_desc_id, $event.target.value)">
        <textarea class="tlp-textarea"
                  v-bind:placeholder="field.desc_description"
                  v-bind:id="`textaarea-${field.group_desc_id}`"
                  v-bind:required="isRequired"
                  v-else-if="field.desc_type ==='text'"
                  v-on:input="updateField(field.group_desc_id, $event.target.value)"
                  data-test="project-field-text"
        ></textarea>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { FieldData } from "../../../type";
import EventBus from "../../../helpers/event-bus";

@Component
export default class FieldList extends Vue {
    @Prop({ required: true })
    readonly field!: FieldData;

    updateField(field_id: number, value: string): void {
        EventBus.$emit("update-field-list", { field_id: field_id, value: value });
    }

    get isRequired(): boolean {
        return this.field.desc_required === "1";
    }
}
</script>
