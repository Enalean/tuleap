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
    <select
        id="project-information-input-privacy-list-label"
        class="tlp-select"
        name="privacy"
        data-test="project-information-input-privacy-list"
        v-model="selected_visibility"
        required
    >
        <option
            v-if="are_restricted_users_allowed"
            value="unrestricted"
            v-bind:selected="is_public_included_restricted_selected"
            v-translate
            data-test="unrestricted"
        >
            Public incl. restricted
        </option>
        <option value="public" v-bind:selected="is_public_selected" data-test="public" v-translate>
            Public
        </option>
        <option
            value="private"
            v-bind:selected="is_private_selected"
            data-test="private"
            v-translate
        >
            Private
        </option>
        <option
            value="private-wo-restr"
            v-if="are_restricted_users_allowed"
            v-bind:selected="is_private_without_restricted_selected"
            data-test="private-wo-restr"
            v-translate
        >
            Private without restricted
        </option>
    </select>
</template>

<script lang="ts">
import Vue from "vue";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import { Component, Watch } from "vue-property-decorator";
import EventBus from "../../../helpers/event-bus";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../../../constant";
import { namespace } from "vuex-class";
const configuration = namespace("configuration");

@Component
export default class ProjectInformationInputPrivacyList extends Vue {
    @configuration.State
    project_default_visibility!: string;

    @configuration.State
    are_restricted_users_allowed!: boolean;

    private list_picker_instance: ListPicker | null = null;

    selected_visibility = ACCESS_PRIVATE;

    async mounted(): Promise<void> {
        this.selected_visibility = this.project_default_visibility;

        if (!(this.$el instanceof HTMLSelectElement)) {
            throw new Error("Element is supposed to be a select element");
        }

        this.list_picker_instance = await createListPicker(this.$el, {
            items_template_formatter: (html_processor, value_id, item_label) => {
                const description = this.translatedVisibilityDetails(value_id);
                const template = html_processor`<div>
                    <span class="project-information-input-privacy-list-option-label">${item_label}</span>
                    <p class="project-information-input-privacy-list-option-description">${description}</p>
                </div>`;
                return Promise.resolve(template);
            },
        });
    }

    destroy(): void {
        if (this.list_picker_instance !== null) {
            this.list_picker_instance.destroy();
        }
    }

    get is_public_included_restricted_selected(): boolean {
        return this.selected_visibility === ACCESS_PUBLIC_UNRESTRICTED;
    }

    get is_public_selected(): boolean {
        return this.selected_visibility === ACCESS_PUBLIC;
    }

    get is_private_selected(): boolean {
        return this.selected_visibility === ACCESS_PRIVATE;
    }

    get is_private_without_restricted_selected(): boolean {
        return this.selected_visibility === ACCESS_PRIVATE_WO_RESTRICTED;
    }

    translatedVisibilityDetails(visibility: string): string {
        switch (visibility) {
            case ACCESS_PUBLIC_UNRESTRICTED:
                return this.$gettext(
                    "Project content is available to all authenticated users, including restricted users. Please note that more restrictive permissions might exist on some items."
                );
            case ACCESS_PUBLIC:
                return this.$gettext(
                    "Project content is available to all authenticated users. Please note that more restrictive permissions might exist on some items."
                );
            case ACCESS_PRIVATE:
                if (this.are_restricted_users_allowed) {
                    return this.$gettext(
                        "Only project members can access project content. Restricted users can be added to the project."
                    );
                }
                return this.$gettext("Only project members can access project content.");
            case ACCESS_PRIVATE_WO_RESTRICTED:
                return this.$gettext(
                    "Only project members can access project content. Restricted users can NOT be added in this project."
                );
            default:
                throw new Error("Unable to retrieve the selected visibility type");
        }
    }

    @Watch("selected_visibility")
    updateProjectVisibility(visibility: string): void {
        EventBus.$emit("update-project-visibility", { new_visibility: visibility });
    }
}
</script>
