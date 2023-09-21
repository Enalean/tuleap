<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
  -->

<template>
    <div class="breadcrumb-container">
        <breadcrumb-privacy
            v-bind:project_flags="project_flags()"
            v-bind:privacy="privacy()"
            v-bind:project_public_name="project_public_name()"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="project_url()" class="breadcrumb-link">
                    {{ project_icon() }} {{ project_public_name() }}
                </a>
            </div>
            <div class="breadcrumb-switchable breadcrumb-item">
                <a
                    class="breadcrumb-link"
                    v-bind:href="repository_list_url()"
                    v-bind:title="$gettext('Repository list')"
                >
                    <translate>Git repositories</translate>
                </a>
                <div class="breadcrumb-switch-menu-container">
                    <nav class="breadcrumb-switch-menu">
                        <span class="breadcrumb-dropdown-item" v-if="is_admin()">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="repository_admin_url()"
                                v-bind:title="$gettext('Administration')"
                            >
                                <i class="fa fa-cog fa-fw"></i>
                                <translate>Administration</translate>
                            </a>
                        </span>
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="repository_fork_url()"
                                v-bind:title="$gettext('Fork repositories')"
                            >
                                <i class="fas fa-code-branch fa-fw"></i>
                                <translate>Fork repositories</translate>
                            </a>
                        </span>
                    </nav>
                </div>
            </div>
        </nav>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import {
    getAdministrationUrl,
    getForkRepositoriesUrl,
    getRepositoryListUrl,
    getProjectUrl,
    getProjectPublicName,
    getPrivacy,
    getProjectFlags,
    getProjectIcon,
} from "../breadcrumb-presenter";
import { getUserIsAdmin } from "../repository-list-presenter";
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

@Component({ components: { BreadcrumbPrivacy } })
export default class GitBreadcrumbs extends Vue {
    repository_list_url(): string {
        return getRepositoryListUrl();
    }
    repository_admin_url(): string {
        return getAdministrationUrl();
    }
    repository_fork_url(): string {
        return getForkRepositoriesUrl();
    }
    is_admin(): boolean {
        return getUserIsAdmin();
    }
    project_url(): string {
        return getProjectUrl();
    }
    project_public_name(): string {
        return getProjectPublicName();
    }
    privacy(): ProjectPrivacy {
        return getPrivacy();
    }
    project_flags(): Array<ProjectFlag> {
        return getProjectFlags();
    }
    project_icon() {
        return getProjectIcon();
    }
}
</script>
