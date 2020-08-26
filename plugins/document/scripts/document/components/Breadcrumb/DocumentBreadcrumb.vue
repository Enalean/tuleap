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
            <div v-bind:class="get_breadcrumb_class">
                <router-link
                    v-bind:to="{ name: 'root_folder' }"
                    class="breadcrumb-link"
                    v-bind:title="document_tree_title"
                >
                    <i class="breadcrumb-link-icon fa fa-folder-open"></i>
                    <translate>Documents</translate>
                </router-link>
                <nav class="breadcrumb-switch-menu" v-if="is_admin">
                    <span class="breadcrumb-dropdown-item">
                        <a
                            class="breadcrumb-dropdown-link"
                            v-bind:href="document_administration_url"
                            v-bind:title="document_administration_title"
                            data-test="breadcrumb-administrator-link"
                        >
                            <i class="fa fa-cog fa-fw"></i>
                            <translate>Administration</translate>
                        </a>
                    </span>
                </nav>
            </div>

            <span
                class="breadcrumb-item breadcrumb-item-disabled"
                v-if="is_ellipsis_displayed"
                data-test="breadcrumb-ellipsis"
            >
                <span class="breadcrumb-link" v-bind:title="ellipsis_title">...</span>
            </span>
            <document-breadcrumb-element
                v-for="parent in current_folder_ascendant_hierarchy_to_display"
                v-bind:key="parent.id"
                v-bind:item="parent"
            />
            <span
                class="breadcrumb-item"
                v-if="is_loading_ascendant_hierarchy"
                data-test="document-breadcrumb-skeleton"
            >
                <a class="breadcrumb-link" href="#">
                    <span class="tlp-skeleton-text"></span>
                </a>
            </span>
            <document-breadcrumb-document
                v-if="is_current_document_displayed"
                v-bind:current_document="currently_previewed_item"
                v-bind:parent_folder="current_folder"
                data-test="breadcrumb-current-document"
            />
        </nav>
    </div>
</template>

<script>
import { mapState } from "vuex";
import DocumentBreadcrumbElement from "./DocumentBreadcrumbElement.vue";
import DocumentBreadcrumbDocument from "./DocumentBreadcrumbDocument.vue";
import { getProjectPrivacyIcon } from "../../../../../../src/scripts/project/privacy/project-privacy-helper";
import { createPopover } from "tlp";

export default {
    name: "DocumentBreadcrumb",
    components: { DocumentBreadcrumbElement, DocumentBreadcrumbDocument },
    data() {
        return {
            max_nb_to_display: 5,
        };
    },
    computed: {
        ...mapState([
            "project_id",
            "project_url",
            "project_public_name",
            "is_user_administrator",
            "current_folder_ascendant_hierarchy",
            "is_loading_ascendant_hierarchy",
            "currently_previewed_item",
            "current_folder",
            "privacy",
        ]),
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
        },
        is_ellipsis_displayed() {
            if (this.is_loading_ascendant_hierarchy) {
                return false;
            }

            return this.current_folder_ascendant_hierarchy.length > this.max_nb_to_display;
        },
        ellipsis_title() {
            return this.$gettext("Parent folders are not displayed to not clutter the interface");
        },
        current_folder_ascendant_hierarchy_to_display() {
            return this.current_folder_ascendant_hierarchy
                .filter((parent) => parent.parent_id !== 0)
                .slice(-this.max_nb_to_display);
        },
        is_current_document_displayed() {
            return this.currently_previewed_item !== null && this.current_folder !== null;
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
