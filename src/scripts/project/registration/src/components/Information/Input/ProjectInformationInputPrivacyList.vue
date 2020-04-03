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
        class="tlp-select tlp-select-large"
        name="privacy"
        data-test="project-information-input-privacy-list"
        v-model="selected_visibility"
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
            v-if="are_restricted_users_allowed"
            value="private"
            v-bind:selected="is_private_selected"
            data-test="private"
            v-translate
        >
            Private incl. restricted
        </option>
        <option
            value="private-wo-restr"
            v-bind:selected="is_private_without_restricted_selected"
            data-test="private-wo-restr"
            v-translate
        >
            Private
        </option>
    </select>
</template>

<script lang="ts">
import Vue from "vue";
import {
    DataFormat,
    GroupedDataFormat,
    LoadingData,
    IdTextPair,
    Options,
    select2,
    Select2Plugin,
} from "tlp";
import { VisibilityForVisibilitySelector } from "./type";
import { sanitize } from "dompurify";
import { render } from "mustache";
import { Component } from "vue-property-decorator";
import EventBus from "../../../helpers/event-bus";
import $ from "jquery";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../../../constant";
import { State } from "vuex-class";

@Component
export default class ProjectInformationInputPrivacyList extends Vue {
    @State
    project_default_visibility!: string;

    @State
    are_restricted_users_allowed!: boolean;

    @State
    can_user_choose_project_visibility!: boolean;

    select2_visibility_select: Select2Plugin | null = null;

    selected_visibility = ACCESS_PRIVATE_WO_RESTRICTED;

    mounted(): void {
        this.selected_visibility = this.project_default_visibility;

        const configuration: Options = {
            minimumResultsForSearch: Infinity,
            templateResult: this.formatVisibilityOption,
            escapeMarkup: sanitize,
        };

        setTimeout(() => {
            if (!this.can_user_choose_project_visibility) {
                return;
            }

            this.select2_visibility_select = select2(this.$el, configuration);

            $(this.$el).on("change", () => {
                this.updateProjectVisibility();
            });
        }, 10);
    }

    destroyed(): void {
        if (this.select2_visibility_select !== null) {
            $(this.$el).off().select2("destroy");
        }
    }

    formatVisibilityOption(visibility: DataFormat | GroupedDataFormat | LoadingData): string {
        if (!this.isForVisibilitySelector(visibility)) {
            return "";
        }

        return render(
            `<div>
                <span class="project-information-input-privacy-list-option-label">{{ label }}</span>
                <p class="project-information-input-privacy-list-option-description">{{ description }}</p>
            </div>`,
            {
                label: visibility.text,
                description: this.translatedVisibilityDetails(visibility.element.value),
            }
        );
    }

    isForVisibilitySelector(
        visibility: IdTextPair | DataFormat | GroupedDataFormat | LoadingData
    ): visibility is VisibilityForVisibilitySelector {
        // This is a trick to fool TypeScript so that we can have description on project visibility.
        // Default types definition of select2 forces us to have only "DataFormat" (basically: id, text) whereas
        // we can deal with values with more attribute (for example: description).
        //
        // The chosen solution is to rely on visibility-defined type guards of TypeScript.
        return "element" in visibility;
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
                return this.$gettext(
                    "Only project members can access project content. Restricted users are allowed in this project."
                );
            case ACCESS_PRIVATE_WO_RESTRICTED:
                if (this.are_restricted_users_allowed) {
                    return this.$gettext(
                        "Only project members can access project content. Restricted users are NOT allowed in this project."
                    );
                }
                return this.$gettext("Only project members can access project content.");
            default:
                throw new Error("Unable to retrieve the selected visibility type");
        }
    }

    updateProjectVisibility(): void {
        const visibility: string | number | string[] | undefined = $(this.$el).val();
        EventBus.$emit("update-project-visibility", { new_visibility: visibility });
    }
}
</script>
