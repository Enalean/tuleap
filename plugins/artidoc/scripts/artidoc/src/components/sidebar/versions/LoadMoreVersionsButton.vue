<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <button
        class="tlp-button-mini tlp-button-primary load-more-versions"
        v-on:click="loadMore"
        v-if="has_more_versions && !has_versions_loading_error"
        v-bind:disabled="is_loading_more_versions"
    >
        <i
            class="tlp-button-icon"
            v-bind:class="
                is_loading_more_versions
                    ? 'fa-solid fa-circle-notch fa-spin'
                    : 'fa-solid fa-arrow-down'
            "
            aria-hidden="true"
        ></i>
        {{ $gettext("Load more versions") }}
    </button>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
const { $gettext } = useGettext();

const is_loading_more_versions = ref(false);

const props = defineProps<{
    has_versions_loading_error: boolean;
    has_more_versions: boolean;
    load_more_callback: () => Promise<void>;
}>();

const loadMore = (): Promise<void> => {
    is_loading_more_versions.value = true;

    return props.load_more_callback().finally(() => {
        is_loading_more_versions.value = false;
    });
};
</script>

<style scoped lang="scss">
.load-more-versions {
    margin: 0 var(--tlp-medium-spacing) var(--tlp-medium-spacing);
}
</style>
