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
    <nav class="breadcrumb">
        <div class="breadcrumb-switchable breadcrumb-item">
            <a
                class="breadcrumb-link"
                v-bind:href="repository_list_url"
                v-bind:title="repositories_title"
            >
                <i class="breadcrumb-link-icon fa fa-fw fa-tlp-versioning-git"></i>
                <translate>Git repositories</translate>
            </a>
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
    </nav>
</template>
<script>
import {
    getAdministrationUrl,
    getForkRepositoriesUrl,
    getRepositoryListUrl,
} from "../breadcrumb-presenter.js";
import { getUserIsAdmin } from "../repository-list-presenter.js";

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
    },
};
</script>
