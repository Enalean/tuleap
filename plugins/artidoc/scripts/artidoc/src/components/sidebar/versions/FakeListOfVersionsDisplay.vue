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
    <flat-list-of-versions v-if="display === FAKE_DATA_ALL_VERSIONS" v-bind:versions="versions" />
    <flat-list-of-versions
        v-if="display === FAKE_DATA_NAMED_VERSIONS"
        v-bind:versions="named_versions"
    />
    <grouped-by-named-list-of-versions
        v-if="display === FAKE_DATA_GROUP_BY_NAMED_VERSIONS"
        v-bind:grouped_versions="grouped_versions"
    />
    <load-more-versions-button
        v-bind:has_versions_loading_error="versions_loading_error.length > 0"
        v-bind:has_more_versions="has_more_versions"
        v-bind:load_more_callback="more"
    />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";
import { getVersions } from "@/components/sidebar/versions/fake-list-of-versions";
import { groupVersionsByNamedVersion } from "@/components/sidebar/versions/group-versions-by-named-version";
import type { VersionsDisplayChoices } from "@/components/sidebar/versions/versions-display";
import {
    FAKE_DATA_ALL_VERSIONS,
    FAKE_DATA_GROUP_BY_NAMED_VERSIONS,
    FAKE_DATA_NAMED_VERSIONS,
} from "@/components/sidebar/versions/versions-display";
import { PROJECT_ID } from "@/project-id-injection-key";
import FlatListOfVersions from "@/components/sidebar/versions/FlatListOfVersions.vue";
import GroupedByNamedListOfVersions from "@/components/sidebar/versions/GroupedByNamedListOfVersions.vue";
import LoadMoreVersionsButton from "@/components/sidebar/versions/LoadMoreVersionsButton.vue";
import {
    IS_LOADING_VERSION,
    VERSIONS_LOADING_ERROR,
} from "@/components/sidebar/versions/load-versions-injection-keys";

defineProps<{
    display: VersionsDisplayChoices;
}>();

const { $gettext } = useGettext();
const project_id = strictInject(PROJECT_ID);

const is_loading_versions = strictInject(IS_LOADING_VERSION);
const versions_loading_error = strictInject(VERSIONS_LOADING_ERROR);

const has_more_versions = ref(true);

const versions = ref<ReadonlyArray<Version>>([]);
const named_versions = computed(() => versions.value.filter((version) => version.title.isValue()));
const grouped_versions = computed(() => groupVersionsByNamedVersion(versions.value));

let next: ReadonlyArray<Version> = [];

onMounted(() => {
    is_loading_versions.value = true;

    setTimeout(() => {
        getVersions(project_id).match(
            (fetched_versions: ReadonlyArray<Version>) => {
                is_loading_versions.value = false;
                versions.value = fetched_versions.slice(0, 100);
                next = fetched_versions.slice(100);
            },
            (fault) => {
                is_loading_versions.value = false;
                versions_loading_error.value = $gettext(
                    "An error occurred while getting versions: %{ error }",
                    {
                        error: String(fault),
                    },
                );
            },
        );
    }, 1000);
});

const more = (): Promise<void> =>
    new Promise((resolve) => {
        setTimeout(() => {
            versions.value = [...versions.value, ...next];
            has_more_versions.value = false;
            resolve();
        }, 1000);
    });
</script>
