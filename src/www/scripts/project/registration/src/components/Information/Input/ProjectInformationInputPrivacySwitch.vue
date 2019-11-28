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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-form-element">
        <label class="tlp-label" for="project-information-input-privacy-switch-label">
            <span v-translate>Privacy</span>
            <span class="tlp-tooltip tlp-tooltip-top" v-bind:data-tlp-tooltip="translated_tooltip" data-test="project-information-input-privacy-tooltip">
                <i class="fa fa-question-circle project-information-input-privacy-icon" aria-hidden="true"></i>
            </span>
        </label>
        <div class="tlp-switch">
            <input type="checkbox"
                   id="project-information-input-privacy-switch-label"
                   class="tlp-switch-checkbox project-information-input-privacy-switch-checkbox"
                   v-model="is_checked"
                   v-on:click="$emit('input', ! is_checked)"
                   data-test="project-information-input-privacy-switch"
            >
            <label for="project-information-input-privacy-switch-label"
                   class="tlp-switch-button project-information-input-privacy-switch-button" aria-hidden>
            </label>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";

@Component
export default class ProjectInformationInputPrivacySwitch extends Vue {
    is_checked = false;

    get translated_tooltip(): string {
        if (this.is_checked) {
            return this.$gettext(
                "Project privacy set to private. Only project members can access its content. Restricted users are not allowed in this project."
            );
        }
        return this.$gettext(
            "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
        );
    }
}
</script>
