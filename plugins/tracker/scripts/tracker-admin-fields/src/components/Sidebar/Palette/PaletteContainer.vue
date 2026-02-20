<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <sidebar-container>
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    {{ $gettext("Add fields") }}
                </h1>
            </div>
            <div class="tlp-pane-section">
                <div class="tlp-form-element">
                    <input
                        type="search"
                        class="tlp-search"
                        v-bind:placeholder="$gettext('Field...')"
                        v-model="search"
                    />
                </div>
                <palette-category
                    v-for="category of matching"
                    v-bind:key="category.label"
                    v-bind:category="category"
                    v-bind:search="search"
                />
            </div>
        </div>
    </sidebar-container>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import SidebarContainer from "../SidebarContainer.vue";
import type { CategoryOfPaletteFields } from "./type";
import PaletteCategory from "./PaletteCategory.vue";

const { $gettext } = useGettext();

const categories: ReadonlyArray<CategoryOfPaletteFields> = [
    {
        label: $gettext("Fields"),
        fields: [
            {
                label: $gettext("String"),
                icon: "fa-solid fa-t",
            },
            {
                label: $gettext("Text"),
                icon: "fa-solid fa-t",
            },
            {
                label: $gettext("Integer"),
                icon: "fa-solid fa-3",
            },
            {
                label: $gettext("Float"),
                icon: "fa-solid fa-3",
            },
            {
                label: $gettext("Date"),
                icon: "fa-solid fa-calendar-days",
            },
            {
                label: $gettext("Selectbox"),
                icon: "fa-solid fa-list",
            },
            {
                label: $gettext("Multi selectbox"),
                icon: "fa-solid fa-list",
            },
            {
                label: $gettext("Open list"),
                icon: "fa-solid fa-list",
            },
            {
                label: $gettext("Radio"),
                icon: "fa-regular fa-circle-dot",
            },
            {
                label: $gettext("Checkbox"),
                icon: "fa-regular fa-square-check",
            },
            {
                label: $gettext("File upload"),
                icon: "fa-solid fa-upload",
            },
            {
                label: $gettext("Artifact link"),
                icon: "fa-solid fa-link",
            },
            {
                label: $gettext("Permissions on artifact"),
                icon: "fa-solid fa-lock",
            },
            {
                label: $gettext("Shared field"),
                icon: "fa-solid fa-shapes",
            },
            {
                label: $gettext("Last update by"),
                icon: "fa-solid fa-user",
            },
            {
                label: $gettext("Last update date"),
                icon: "fa-solid fa-calendar-days",
            },
            {
                label: $gettext("Submitted by"),
                icon: "fa-solid fa-user",
            },
            {
                label: $gettext("Submitted on"),
                icon: "fa-solid fa-calendar-days",
            },
            {
                label: $gettext("Artifact id"),
                icon: "fa-solid fa-hashtag",
            },
            {
                label: $gettext("Per tracker id"),
                icon: "fa-solid fa-hashtag",
            },
            {
                label: $gettext("Cross references"),
                icon: "fa-solid fa-arrows-turn-to-dots",
            },
            {
                label: $gettext("Computed value"),
                icon: "fa-solid fa-calculator",
            },
            {
                label: $gettext("Rank"),
                icon: "fa-solid fa-arrow-up-short-wide",
            },
        ].toSorted((a, b) => a.label.localeCompare(b.label)),
    },
    {
        label: $gettext("Layout & structure"),
        fields: [
            {
                label: $gettext("Fieldset"),
                icon: "fa-regular fa-square",
            },
            {
                label: $gettext("Separator"),
                icon: "fa-solid fa-minus",
            },
            {
                label: $gettext("Static text"),
                icon: "fa-solid fa-align-left",
            },
        ].toSorted((a, b) => a.label.localeCompare(b.label)),
    },
];

const search = ref("");
const matching = computed(() =>
    search.value.trim() === ""
        ? categories
        : categories.filter((category) =>
              category.fields.some((field) =>
                  field.label.toLowerCase().includes(search.value.trim().toLowerCase()),
              ),
          ),
);
</script>

<style scoped lang="scss">
.tlp-card {
    display: flex;
    gap: var(--tlp-small-spacing);
    cursor: move;
}

.tlp-pane-section {
    overflow-y: scroll;
}
</style>
