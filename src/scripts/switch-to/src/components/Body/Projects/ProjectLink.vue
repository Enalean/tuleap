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
    <a v-bind:href="project.project_uri" class="switch-to-projects-project">
        <i class="fa fa-fw switch-to-projects-project-icon" v-bind:class="project_icon"></i>
        <span class="switch-to-projects-project-label">{{ project.project_name }}</span>
        <i
            class="fa fa-fw fa-cog switch-to-projects-project-admin-icon"
            v-if="project.is_current_user_admin"
            aria-hidden="true"
            v-on:click="goToAdmin"
        ></i>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Project } from "../../../type";
import {
    getProjectPrivacyIcon,
    ProjectPrivacy,
} from "../../../../../project/privacy/project-privacy-helper";
import { State } from "vuex-class";

@Component
export default class ProjectLink extends Vue {
    @Prop({ required: true })
    readonly project!: Project;

    @State
    readonly are_restricted_users_allowed!: boolean;

    goToAdmin(event: Event): void {
        event.preventDefault();

        window.location.href = this.project.project_config_uri;
    }

    get project_icon(): string {
        const privacy: ProjectPrivacy = {
            project_is_public: this.project.is_public,
            project_is_private: this.project.is_private,
            project_is_private_incl_restricted: this.project.is_private_incl_restricted,
            project_is_public_incl_restricted: this.project.is_public_incl_restricted,
            are_restricted_users_allowed: this.are_restricted_users_allowed,
            explanation_text: "",
        };

        return getProjectPrivacyIcon(privacy);
    }
}
</script>
