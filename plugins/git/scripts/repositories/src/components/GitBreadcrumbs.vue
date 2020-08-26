<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        <div class="breadcrumb-privacy-icon-container">
            <span id="breadcrumb-project-privacy-icon">
                <i
                    class="fa breadcrumb-project-privacy-icon"
                    v-bind:class="project_privacy_icon"
                    ref="popover_icon"
                ></i>
            </span>

            <section class="tlp-popover" ref="popover_content">
                <div class="tlp-popover-arrow"></div>
                <div class="tlp-popover-header">
                    <h1 class="tlp-popover-title">{{ project_public_name }}</h1>
                </div>
                <div class="tlp-popover-body">
                    <p class="current-project-nav-title-popover-description">
                        {{ privacy.project_privacy }}
                    </p>
                </div>
            </section>
        </div>
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="project_url" class="breadcrumb-link">
                    {{ project_public_name }}
                </a>
            </div>
            <div class="breadcrumb-switchable breadcrumb-item">
                <a
                    class="breadcrumb-link"
                    v-bind:href="repository_list_url"
                    v-bind:title="repositories_title"
                >
                    <i class="breadcrumb-link-icon fa fa-fw fa-tlp-versioning-git"></i>
                    <translate>Git repositories</translate>
                </a>
                <div class="breadcrumb-switch-menu-container">
                    <nav class="breadcrumb-switch-menu">
                        <span class="breadcrumb-dropdown-item" v-if="is_admin">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="repository_admin_url"
                                v-bind:title="administration_title"
                            >
                                <i class="fa fa-cog fa-fw"></i>
                                <translate>Administration</translate>
                            </a>
                        </span>
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="repository_fork_url"
                                v-bind:title="fork_title"
                            >
                                <i class="fa fa-code-fork fa-fw"></i>
                                <translate>Fork repositories</translate>
                            </a>
                        </span>
                    </nav>
                </div>
            </div>
        </nav>
    </div>
</template>
<script>
import {
    getAdministrationUrl,
    getForkRepositoriesUrl,
    getRepositoryListUrl,
    getProjectUrl,
    getProjectPublicName,
    getPrivacy,
} from "../breadcrumb-presenter.js";
import { getUserIsAdmin } from "../repository-list-presenter.js";
import { getProjectPrivacyIcon } from "../../../../../../src/scripts/project/privacy/project-privacy-helper";
import { createPopover } from "tlp";

export default {
    name: "GitBreadcrumbs",
    computed: {
        repositories_title() {
            return this.$gettext("Repository list");
        },
        administration_title() {
            return this.$gettext("Administration");
        },
        fork_title() {
            return this.$gettext("Fork repositories");
        },
        repository_list_url() {
            return getRepositoryListUrl();
        },
        repository_admin_url() {
            return getAdministrationUrl();
        },
        repository_fork_url() {
            return getForkRepositoriesUrl();
        },
        is_admin() {
            return getUserIsAdmin();
        },
        project_url() {
            return getProjectUrl();
        },
        project_public_name() {
            return getProjectPublicName();
        },
        privacy() {
            return getPrivacy();
        },
        project_privacy_icon() {
            return getProjectPrivacyIcon(this.privacy);
        },
    },
    mounted() {
        createPopover(this.$refs.popover_icon, this.$refs.popover_content, {
            anchor: this.$refs.popover_icon,
            placement: "bottom-start",
        });
    },
};
</script>
