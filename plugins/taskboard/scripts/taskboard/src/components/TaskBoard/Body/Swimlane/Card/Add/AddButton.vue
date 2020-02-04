<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -
  -->

<template>
    <button
        type="button"
        class="taskboard-add-in-place-button tlp-button-primary tlp-button-outline"
        v-bind:class="button_class"
        v-bind:data-tlp-tooltip="title"
        v-bind:aria-label="title"
        v-on:click="$emit('click')"
        data-test="add-in-place-button"
    >
        <i class="fa" v-bind:class="icon_class" aria-hidden="true"></i>
        <span v-if="label">{{ label }}</span>
    </button>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class AddButton extends Vue {
    @Prop({ required: true })
    readonly label!: string;

    get title(): string {
        return this.label || this.$gettext("Add new card");
    }

    get icon_class(): string {
        return this.label !== "" ? "fa-tlp-hierarchy-plus tlp-button-icon" : "fa-plus";
    }

    get button_class(): string {
        return this.label !== ""
            ? "tlp-button-small taskboard-add-in-place-button-with-label"
            : "tlp-tooltip tlp-tooltip-top";
    }
}
</script>
