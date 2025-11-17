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
    <flat-list-of-versions v-bind:versions="versions" />
    <load-more-versions-button
        v-if="versions.length > 0"
        v-bind:has_versions_loading_error="versions_loading_error.length > 0"
        v-bind:has_more_versions="has_more_versions"
        v-bind:load_more_callback="more"
    />
    <p class="empty-state-text" v-else>
        {{ $gettext("This artidoc has no versions yet") }}
    </p>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PaginatedVersions } from "@/components/sidebar/versions/VersionsLoader";
import { getVersionsLoader } from "@/components/sidebar/versions/VersionsLoader";
import LoadMoreVersionsButton from "@/components/sidebar/versions/LoadMoreVersionsButton.vue";
import FlatListOfVersions from "@/components/sidebar/versions/FlatListOfVersions.vue";
import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";
import {
    IS_LOADING_VERSION,
    VERSIONS_LOADING_ERROR,
} from "@/components/sidebar/versions/load-versions-injection-keys";
import { DOCUMENT_ID } from "@/document-id-injection-key";

const { $gettext } = useGettext();
const is_loading_versions = strictInject(IS_LOADING_VERSION);
const versions_loading_error = strictInject(VERSIONS_LOADING_ERROR);

const has_more_versions = ref(false);
const versions = ref<ReadonlyArray<Version>>([]);
const versions_loader = getVersionsLoader(strictInject(DOCUMENT_ID));

onMounted(() => {
    is_loading_versions.value = true;

    versions_loader.loadNextBatchOfVersions().match(
        (paginated_versions: PaginatedVersions) => {
            is_loading_versions.value = false;
            has_more_versions.value = paginated_versions.has_more;
            versions.value = [...versions.value, ...paginated_versions.versions];
        },
        (fault) => {
            is_loading_versions.value = false;
            has_more_versions.value = false;
            versions_loading_error.value = $gettext(
                "An error occurred while getting versions: %{ error }",
                {
                    error: String(fault),
                },
            );
        },
    );
});

const more = (): Promise<void> => {
    return new Promise((resolve) => {
        versions_loader.loadNextBatchOfVersions().match(
            (paginated_versions: PaginatedVersions) => {
                has_more_versions.value = paginated_versions.has_more;
                versions.value = [...versions.value, ...paginated_versions.versions];
                resolve();
            },
            (fault) => {
                has_more_versions.value = false;
                versions_loading_error.value = $gettext(
                    "An error occurred while getting versions: %{ error }",
                    {
                        error: String(fault),
                    },
                );
                resolve();
            },
        );
    });
};
</script>
