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
  -
  -->

<template>
    <div
        class="switch-to-projects-project"
        v-on:keydown="changeFocus"
        data-test="switch-to-projects-project"
    >
        <a
            v-bind:href="project.project_uri"
            class="switch-to-projects-project-link"
            ref="project_link"
            data-test="project-link"
        >
            <i
                class="fas fa-fw switch-to-projects-project-icon"
                v-bind:class="project_privacy_icon"
            ></i>
            <span class="switch-to-projects-project-label">
                <span v-if="project.icon" class="switch-to-projects-project-label-icon">
                    {{ project.icon }}
                </span>
                <highlight-matching-text>
                    {{ project.project_name }}
                </highlight-matching-text>
            </span>
        </a>
        <quick-link
            v-for="link of project.quick_links"
            v-bind:key="link.html_url"
            v-bind:link="link"
            class="switch-to-projects-project-admin-icon"
            data-test="switch-to-projects-project-admin-icon"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Project } from "../../../type";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import { getProjectPrivacyIcon } from "@tuleap/project-privacy-helper";
import { sprintf } from "sprintf-js";
import { useSwitchToStore } from "../../../stores";
import HighlightMatchingText from "../HighlightMatchingText.vue";
import type { ItemDefinition } from "../../../type";
import QuickLink from "../QuickLink.vue";

@Component({
    components: { QuickLink, HighlightMatchingText },
})
export default class ProjectLink extends Vue {
    @Prop({ required: true })
    private readonly project!: Project;

    get programmatically_focused_element(): Project | ItemDefinition | QuickLink | null {
        return useSwitchToStore().programmatically_focused_element;
    }

    @Watch("programmatically_focused_element")
    forceFocus(): void {
        if (this.programmatically_focused_element !== this.project) {
            return;
        }

        const link = this.$refs.project_link;
        if (link instanceof HTMLAnchorElement) {
            link.focus();
        }
    }

    changeFocus(event: KeyboardEvent): void {
        switch (event.key) {
            case "ArrowUp":
            case "ArrowRight":
            case "ArrowDown":
            case "ArrowLeft":
                event.preventDefault();
                useSwitchToStore().changeFocusFromProject({
                    project: this.project,
                    key: event.key,
                });
                break;
            default:
        }
    }

    goToAdmin(event: Event): void {
        event.preventDefault();

        window.location.href = this.project.project_config_uri;
    }

    get project_privacy_icon(): string {
        const privacy: ProjectPrivacy = {
            project_is_public: this.project.is_public,
            project_is_private: this.project.is_private,
            project_is_private_incl_restricted: this.project.is_private_incl_restricted,
            project_is_public_incl_restricted: this.project.is_public_incl_restricted,
            are_restricted_users_allowed: useSwitchToStore().are_restricted_users_allowed,
            explanation_text: "",
            privacy_title: "",
        };

        return getProjectPrivacyIcon(privacy);
    }

    get admin_title(): string {
        return sprintf(
            this.$gettext("Go to project administration of %s"),
            this.project.project_name
        );
    }
}
</script>
