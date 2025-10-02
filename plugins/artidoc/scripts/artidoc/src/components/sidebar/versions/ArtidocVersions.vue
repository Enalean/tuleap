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
  -
  -->

<template>
    <div class="tlp-alert-info" v-if="should_display_under_construction_message">
        <p>{{ $gettext("This feature of artidoc is under construction.") }}</p>
        <p>{{ $gettext("Data is fake in order to gather feedback about the feature.") }}</p>
        <button type="button" class="tlp-button-small tlp-button-primary" v-on:click="gotit()">
            {{ $gettext("Ok, got it") }}
        </button>
    </div>
    <div class="tlp-alert-danger" v-if="error">
        {{ error }}
    </div>
    <list-of-versions-skeleton v-if="is_loading_versions" />
    <section v-if="!error && !is_loading_versions">
        <div class="filter">
            <select class="tlp-select" v-model="display">
                <option v-bind:value="ALL_VERSIONS">{{ $gettext("All versions") }}</option>
                <option v-bind:value="NAMED_VERSIONS">{{ $gettext("Named versions") }}</option>
                <option v-bind:value="GROUP_BY_NAMED_VERSIONS">
                    {{ $gettext("Group by named versions") }}
                </option>
            </select>
        </div>
        <flat-list-of-versions v-if="display === ALL_VERSIONS" v-bind:versions="versions" />
        <flat-list-of-versions v-if="display === NAMED_VERSIONS" v-bind:versions="named_versions" />
        <grouped-by-named-list-of-versions
            v-if="display === GROUP_BY_NAMED_VERSIONS"
            v-bind:grouped_versions="grouped_versions"
        />
        <button
            class="tlp-button-mini tlp-button-primary load-more-versions"
            v-on:click="more"
            v-if="has_more_versions && !error"
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
    </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import type { Version } from "./fake-list-of-versions";
import { getVersions } from "./fake-list-of-versions";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID } from "@/project-id-injection-key";
import FlatListOfVersions from "@/components/sidebar/versions/FlatListOfVersions.vue";
import ListOfVersionsSkeleton from "@/components/sidebar/versions/ListOfVersionsSkeleton.vue";
import { groupVersionsByNamedVersion } from "@/components/sidebar/versions/group-versions-by-named-version";
import GroupedByNamedListOfVersions from "@/components/sidebar/versions/GroupedByNamedListOfVersions.vue";

const { $gettext } = useGettext();
const versions = ref<ReadonlyArray<Version>>([]);
const project_id = strictInject(PROJECT_ID);
const error = ref("");
const should_display_under_construction_message = ref(true);
let next: ReadonlyArray<Version> = [];
const has_more_versions = ref(true);
const is_loading_more_versions = ref(false);
const is_loading_versions = ref(true);

const ALL_VERSIONS = "all";
const NAMED_VERSIONS = "named";
const GROUP_BY_NAMED_VERSIONS = "group";

type Choices = typeof ALL_VERSIONS | typeof NAMED_VERSIONS | typeof GROUP_BY_NAMED_VERSIONS;

const display = ref<Choices>(ALL_VERSIONS);

const named_versions = computed(() => versions.value.filter((version) => version.title.isValue()));
const grouped_versions = computed(() => groupVersionsByNamedVersion(versions.value));

onMounted(() => {
    setTimeout(() => {
        getVersions(project_id).match(
            (fetched_versions: ReadonlyArray<Version>) => {
                is_loading_versions.value = false;
                versions.value = fetched_versions.slice(0, 100);
                next = fetched_versions.slice(100);
            },
            (fault) => {
                is_loading_versions.value = false;
                error.value = $gettext("An error occurred while getting versions: %{ error }", {
                    error: String(fault),
                });
            },
        );
    }, 1000);
});

function more(): void {
    is_loading_more_versions.value = true;
    setTimeout(() => {
        versions.value = [...versions.value, ...next];
        has_more_versions.value = false;
        is_loading_more_versions.value = false;
    }, 1000);
}

function gotit(): void {
    should_display_under_construction_message.value = false;
}
</script>

<style scoped lang="scss">
@use "@/themes/includes/viewport-breakpoint";

.tlp-alert-danger,
.tlp-alert-info {
    margin: 0;
    border-radius: 0;
}

section {
    height: var(--artidoc-sidebar-content-height);
    overflow: hidden auto;

    @media (max-width: viewport-breakpoint.$small-screen-size) {
        height: fit-content;
    }
}

.filter {
    margin: var(--tlp-medium-spacing);
}

.load-more-versions {
    margin: 0 var(--tlp-medium-spacing) var(--tlp-medium-spacing);
}
</style>
