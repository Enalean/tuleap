<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="tlp-form-element tlp-form-element-append forumml-post-new-thread-cc-component">
        <input
            type="email"
            class="tlp-input"
            v-bind:id="'forumml-cc-' + index"
            name="ccs[]"
            placeholder="jdoe@example.com"
            v-bind:value="cc"
            v-on:input="$emit('input', $event.target.value)"
            ref="input"
            required
        />
        <button
            type="button"
            class="tlp-append tlp-button-primary tlp-button-outline"
            v-on:click="$emit('removeCc')"
            data-test="delete-cc"
        >
            <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class CcInput extends Vue {
    @Prop({ required: true })
    readonly cc!: string;

    @Prop({ required: true })
    readonly index!: number;

    mounted(): void {
        this.$refs.input.focus();
    }
}
</script>
