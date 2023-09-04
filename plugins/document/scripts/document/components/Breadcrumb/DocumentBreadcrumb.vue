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
    <div class="breadcrumb-container document-breadcrumb">
        <breadcrumb-privacy
            v-bind:project_flags="project_flags"
            v-bind:privacy="privacy"
            v-bind:project_public_name="project_public_name"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="project_url" class="breadcrumb-link">
                    <span aria-hidden="true" data-test="project-icon">
                        {{ project_icon }}
                    </span>
                    {{ project_public_name }}
                </a>
            </div>
            <div v-bind:class="getBreadcrumbClass()">
                <router-link
                    v-bind:to="{ name: 'root_folder' }"
                    class="breadcrumb-link"
                    v-bind:title="`${$gettext('Project documentation')}`"
                    data-test="breadcrumb-project-documentation"
                >
                    <i class="breadcrumb-link-icon fa-regular fa-folder-open"></i>
                    {{ $gettext("Documents") }}
                </router-link>
                <div class="breadcrumb-switch-menu-container">
                    <nav class="breadcrumb-switch-menu" v-if="user_is_admin">
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="documentAdministrationUrl()"
                                v-bind:title="`${$gettext('Administration')}`"
                                data-test="breadcrumb-administrator-link"
                            >
                                <i class="fa-solid fa-gear fa-fw"></i>
                                {{ $gettext("Administration") }}
                            </a>
                        </span>
                    </nav>
                </div>
            </div>

            <span
                class="breadcrumb-item breadcrumb-item-disabled"
                v-if="isEllipsisDisplayed()"
                data-test="breadcrumb-ellipsis"
            >
                <span
                    class="breadcrumb-link"
                    v-bind:title="`${$gettext(
                        'Parent folders are not displayed to not clutter the interface',
                    )}`"
                >
                    ...
                </span>
            </span>
            <document-breadcrumb-element
                v-for="parent in currentFolderAscendantHierarchyToDisplay()"
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
                v-if="isCurrentDocumentDisplayed()"
                v-bind:current_document="currently_previewed_item"
                v-bind:parent_folder="current_folder"
                data-test="breadcrumb-current-document"
            />
        </nav>
    </div>
</template>

<script setup lang="ts">
import type { Item, State } from "../../type";
import DocumentBreadcrumbElement from "./DocumentBreadcrumbElement.vue";
import DocumentBreadcrumbDocument from "./DocumentBreadcrumbDocument.vue";
import { BreadcrumbPrivacy } from "@tuleap/vue3-breadcrumb-privacy";
import { useNamespacedState, useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import { ref } from "vue";

const {
    current_folder_ascendant_hierarchy,
    is_loading_ascendant_hierarchy,
    currently_previewed_item,
    current_folder,
} = useState<
    Pick<
        State,
        | "current_folder_ascendant_hierarchy"
        | "is_loading_ascendant_hierarchy"
        | "currently_previewed_item"
        | "current_folder"
    >
>([
    "current_folder_ascendant_hierarchy",
    "is_loading_ascendant_hierarchy",
    "currently_previewed_item",
    "current_folder",
]);

const {
    project_url,
    privacy,
    project_flags,
    project_id,
    project_public_name,
    user_is_admin,
    project_icon,
} = useNamespacedState<
    Pick<
        ConfigurationState,
        | "project_url"
        | "privacy"
        | "project_flags"
        | "project_id"
        | "project_public_name"
        | "user_is_admin"
        | "project_icon"
    >
>("configuration", [
    "project_url",
    "privacy",
    "project_flags",
    "project_id",
    "project_public_name",
    "user_is_admin",
    "project_icon",
]);

const max_nb_to_display = ref(5);

function documentAdministrationUrl(): string {
    return "/plugins/docman/?group_id=" + encodeURIComponent(project_id.value) + "&action=admin";
}

function getBreadcrumbClass(): string {
    if (user_is_admin.value) {
        return "breadcrumb-switchable breadcrumb-item";
    }

    return "breadcrumb-item";
}

function isEllipsisDisplayed(): boolean {
    if (is_loading_ascendant_hierarchy.value) {
        return false;
    }

    return current_folder_ascendant_hierarchy.value.length > max_nb_to_display.value;
}
function currentFolderAscendantHierarchyToDisplay(): Array<Item> {
    return current_folder_ascendant_hierarchy.value
        .filter((parent) => parent.parent_id !== 0)
        .slice(-max_nb_to_display.value);
}

function isCurrentDocumentDisplayed(): boolean {
    return currently_previewed_item.value !== null && current_folder.value !== null;
}
</script>
