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
import PaletteCategory from "./PaletteCategory.vue";
import { UNUSED_FIELDS } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { getCategories } from "../../../helpers/get-categories";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;

const unused_fields = strictInject(UNUSED_FIELDS);

const categories = ref(getCategories(unused_fields, gettext_provider));

const search = ref("");
const matching = computed(() =>
    search.value.trim() === ""
        ? categories.value
        : categories.value.filter((category) =>
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
