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
    <ul v-if="!error">
        <li v-for="version in versions" v-bind:key="version.id">
            <i class="fa-solid fa-circle disc" aria-hidden="true"></i>
            <version-entry v-bind:version="version" />
        </li>
    </ul>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import type { Version } from "./fake-list-of-versions";
import { getVersions } from "./fake-list-of-versions";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID } from "@/project-id-injection-key";
import VersionEntry from "./VersionEntry.vue";

const { $gettext } = useGettext();
const versions = ref<ReadonlyArray<Version>>([]);
const project_id = strictInject(PROJECT_ID);
const error = ref("");
const should_display_under_construction_message = ref(true);

onMounted(() => {
    getVersions(project_id).match(
        (fetched_versions: ReadonlyArray<Version>) => {
            versions.value = fetched_versions;
        },
        (fault) => {
            error.value = $gettext("An error occurred while getting versions: %{ error }", {
                error: String(fault),
            });
        },
    );
});

function gotit(): void {
    should_display_under_construction_message.value = false;
}
</script>

<style scoped lang="scss">
@use "@/themes/includes/viewport-breakpoint";

$border-width: 5px;
$timeline-width: 4px;
$timeline-whitespace: var(--tlp-small-spacing);
$disc-width: 12px;
$timeline-offset-left: calc($timeline-whitespace + 0.5 * $disc-width - 0.5 * $timeline-width);

.tlp-alert-danger,
.tlp-alert-info {
    margin: 0;
    border-radius: 0;
}

ul {
    --timeline-color: var(--tlp-main-color-lighter-80);

    position: relative;
    height: var(--artidoc-sidebar-content-height);
    padding: 0 0 var(--tlp-medium-spacing);
    overflow: hidden auto;
    list-style-type: none;
    color: var(--tlp-dimmed-color);

    @media (max-width: viewport-breakpoint.$small-screen-size) {
        height: fit-content;
    }

    &::after {
        content: "specimen";
        position: absolute;
        top: 20vh;
        transform: rotate(310deg);
        opacity: 0.7;
        color: var(--tlp-main-color-transparent-90);
        font-size: 5rem;
        font-weight: 600;
    }
}

li {
    display: flex;
    position: relative;
    align-items: baseline;
    padding: calc(var(--tlp-small-spacing) / 2) var(--tlp-medium-spacing)
        calc(var(--tlp-small-spacing) / 2) #{$timeline-whitespace};
    border-left: #{$border-width} solid transparent;
    gap: var(--tlp-small-spacing);

    &:first-child {
        border-left-color: var(--tlp-main-color);
        background: var(--tlp-main-color-hover-background);
    }

    &:hover {
        background: var(--tlp-main-color-hover-background);
    }

    &::before {
        content: "";
        position: absolute;
        top: 0;
        left: #{$timeline-offset-left};
        width: #{$timeline-width};
        height: 100%;
        background: var(--timeline-color);
    }

    &:first-child::before {
        top: var(--tlp-medium-spacing);
        height: calc(100% - var(--tlp-medium-spacing));
    }

    &:last-child::before {
        height: var(--tlp-medium-spacing);
    }
}

.disc {
    // Make sure that the disc is above the timeline line
    z-index: 1;
    color: var(--timeline-color);
    font-size: #{$disc-width};
}
</style>
