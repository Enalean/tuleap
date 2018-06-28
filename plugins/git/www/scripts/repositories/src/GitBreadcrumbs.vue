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
                    v-bind:href="repositoryListUrl"
                    v-bind:title="repositories_title"
            >
                <i class="breadcrumb-link-icon fa fa-fw icon-fixed-width fa-table icon-table"></i>
                <translate>Git repositories</translate>
            </a>
            <nav class="breadcrumb-switch-menu">
                <span class="breadcrumb-dropdown-item">
                    <a class="breadcrumb-dropdown-link"
                       v-bind:href="repositoriesAdministrationUrl"
                       v-bind:title="administration_tilte">
                       <i class="fa fa-cog fa-fw"></i> <translate>Administration</translate>
                    </a>
                </span>
                <span class="breadcrumb-dropdown-item">
                    <a class="breadcrumb-dropdown-link"
                       v-bind:href="repositoriesForkUrl"
                       v-bind:title="fork_title">
                       <i class="fa fa-code-fork fa-fw"></i> <translate>Fork repositories</translate>
                    </a>
                </span>
            </nav>
        </div>
    </nav>
</template>
<script>
    export default {
        name: 'GitBreadcrumbs',
        props: {
            repositoriesAdministrationUrl: String,
            repositoryListUrl: String,
            repositoriesForkUrl: String
        },
        computed: {
            repositories_title() {
                return this.$gettext("Repository list");
            },
            administration_tilte() {
                return this.$gettext("Administration");
            },
            fork_title() {
                return this.$gettext("Fork repositories");
            }
        }
    }
</script>