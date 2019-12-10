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
        <label class="tlp-label" for="project-information-input-privacy-list-label">
            <span v-translate>Privacy</span>
            <span class="tlp-tooltip tlp-tooltip-top"
                  v-bind:data-tlp-tooltip="translated_tooltip"
                  data-test="project-information-input-privacy-tooltip"
            >
                <i class="fa fa-question-circle project-information-input-privacy-icon" aria-hidden="true"></i>
            </span>
        </label>
        <select id="project-information-input-privacy-list-label"
                class="tlp-select"
                name="privacy"
                v-on:change="$emit('input', selected_visibility)"
                data-test="project-information-input-privacy-list"
                v-model="selected_visibility"
        >
            <option value="unrestricted"
                    v-bind:selected="is_public_included_restricted_selected"
                    v-translate
                    data-test="unrestricted"
            >
                Public incl. restricted
            </option>
            <option value="public"
                    v-bind:selected="is_public_selected"
                    data-test="public"
                    v-translate
            >
                Public
            </option>
            <option value="private"
                    v-bind:selected="is_private_selected"
                    data-test="private"
                    v-translate
            >
                Private incl. restricted
            </option>
            <option value="private-wo-restr"
                    v-bind:selected="is_private_without_restricted_selected"
                    data-test="private-wo-restr"
                    v-translate
            >
                Private
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED
} from "../../../constant";
import { State } from "vuex-class";

@Component
export default class ProjectInformationInputPrivacyList extends Vue {
    @State
    project_default_visibility!: string;

    selected_visibility = ACCESS_PRIVATE;

    mounted(): void {
        this.selected_visibility = this.project_default_visibility;
    }

    get is_public_selected(): boolean {
        return this.selected_visibility === ACCESS_PUBLIC;
    }

    get is_public_included_restricted_selected(): boolean {
        return this.selected_visibility === ACCESS_PUBLIC_UNRESTRICTED;
    }

    get is_private_selected(): boolean {
        return this.selected_visibility === ACCESS_PRIVATE;
    }

    get is_private_without_restricted_selected(): boolean {
        return this.selected_visibility === ACCESS_PRIVATE_WO_RESTRICTED;
    }

    get translated_tooltip(): string {
        switch (this.selected_visibility) {
            case ACCESS_PUBLIC:
                return this.$gettext(
                    "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
                );
            case ACCESS_PUBLIC_UNRESTRICTED:
                return this.$gettext(
                    "Project privacy set to public including restricted. By default, its content is available to all authenticated users. Please note that more restrictive permissions might exist on some items."
                );
            case ACCESS_PRIVATE:
                return this.$gettext(
                    "Project privacy set to private. Only project members can access its content. Restricted users are not allowed in this project."
                );
            case ACCESS_PRIVATE_WO_RESTRICTED:
                return this.$gettext(
                    "Project privacy set to private including restricted. Only project members can access its content. Restricted users are allowed in this project."
                );
            default:
                throw new Error("Unable to retrieve the selected visibility type");
        }
    }
}
</script>
