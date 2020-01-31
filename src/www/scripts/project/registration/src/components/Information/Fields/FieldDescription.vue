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
        <label class="tlp-label" for="field_description">
            <span v-translate>Description</span>
            <i class="fa fa-asterisk" v-if="is_description_required" />
        </label>

        <textarea
            type="text"
            class="tlp-textarea tlp-textarea-large"
            id="field_description"
            name="description"
            v-bind:placeholder="$gettext('My useful project description')"
            v-bind:required="is_description_required"
            v-on:input="$emit('input', $event.target.value)"
            data-test="project-description"
        />
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { State } from "vuex-class";

@Component
export default class FieldDescription extends Vue {
    @State
    is_description_required!: boolean;
}
</script>
