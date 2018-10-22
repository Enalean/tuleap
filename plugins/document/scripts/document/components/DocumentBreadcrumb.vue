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
        <div v-bind:class="get_breadcrumb_class">
            <a class="breadcrumb-link"
               v-bind:href="document_tree_url"
               v-bind:title="document_tree_title"
            >
                <i class="breadcrumb-link-icon fa fa-folder-open"></i>
                <translate>Documents</translate>
            </a>
            <nav class="breadcrumb-switch-menu" v-if="is_admin">
                <span class="breadcrumb-dropdown-item">
                    <a class="breadcrumb-dropdown-link"
                       v-bind:href="document_administration_url"
                       v-bind:title="document_administration_title">
                       <i class="fa fa-cog fa-fw"></i> <translate>Administration</translate>
                    </a>
                </span>
            </nav>
        </div>
    </nav>
</template>

<script>
import { mapState } from "vuex";
export default {
    name: "DocumentBreadcrumb",
    computed: {
        ...mapState(["project_name", "project_id", "is_user_administrator"]),
        document_tree_url() {
            return "/plugins/document/" + this.project_name;
        },
        document_tree_title() {
            return this.$gettext("Project documentation");
        },
        document_administration_url() {
            return "/plugins/docman/?group_id=" + this.project_id + "&action=admin";
        },
        document_administration_title() {
            return this.$gettext("Administration");
        },
        is_admin() {
            return this.is_user_administrator;
        },
        get_breadcrumb_class() {
            if (this.is_user_administrator === true) {
                return "breadcrumb-switchable breadcrumb-item";
            }

            return "breadcrumb-item";
        }
    }
};
</script>
