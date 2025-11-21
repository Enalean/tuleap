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
    <section v-bind:class="{ 'use-fake-versions': use_fake_versions }">
        <div class="tlp-alert-info" v-if="should_display_under_construction_message">
            <p>{{ $gettext("This feature of artidoc is under construction.") }}</p>
            <p v-if="use_fake_versions">
                {{ $gettext("Data is fake in order to gather feedback about the feature.") }}
            </p>
            <button type="button" class="tlp-button-small tlp-button-primary" v-on:click="gotit()">
                {{ $gettext("Ok, got it") }}
            </button>
        </div>
        <div class="tlp-alert-danger" v-if="error">
            {{ error }}
        </div>
        <list-of-versions-skeleton v-if="is_loading_versions" />
        <template v-if="!error && !is_loading_versions">
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend">
                    <i
                        class="fa-solid fa-filter"
                        role="img"
                        v-bind:title="show_title"
                        id="show-label"
                    ></i>
                </span>
                <select class="tlp-select" v-model="display" aria-labelledby="show-label">
                    <optgroup v-bind:label="$gettext('Fake data')">
                        <option v-bind:value="FAKE_DATA_ALL_VERSIONS">
                            {{ $gettext("All versions") }}
                        </option>
                        <option v-bind:value="FAKE_DATA_NAMED_VERSIONS">
                            {{ $gettext("Named versions") }}
                        </option>
                        <option v-bind:value="FAKE_DATA_GROUP_BY_NAMED_VERSIONS">
                            {{ $gettext("Group by named versions") }}
                        </option>
                    </optgroup>
                    <optgroup v-bind:label="$gettext('Artifact history')">
                        <option v-bind:value="ALL_VERSIONS">
                            {{ $gettext("All versions") }}
                        </option>
                    </optgroup>
                </select>
            </div>
        </template>
        <fake-list-of-versions-display v-if="use_fake_versions" v-bind:display="display" />
        <list-of-versions-display v-else />
    </section>
</template>

<script setup lang="ts">
import { ref, provide, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import ListOfVersionsSkeleton from "@/components/sidebar/versions/ListOfVersionsSkeleton.vue";
import type { VersionsDisplayChoices } from "@/components/sidebar/versions/versions-display";
import {
    FAKE_DATA_ALL_VERSIONS,
    FAKE_DATA_GROUP_BY_NAMED_VERSIONS,
    FAKE_DATA_NAMED_VERSIONS,
    ALL_VERSIONS,
} from "@/components/sidebar/versions/versions-display";
import FakeListOfVersionsDisplay from "@/components/sidebar/versions/FakeListOfVersionsDisplay.vue";
import ListOfVersionsDisplay from "@/components/sidebar/versions/ListOfVersionsDisplay.vue";
import {
    IS_LOADING_VERSION,
    VERSIONS_LOADING_ERROR,
} from "@/components/sidebar/versions/load-versions-injection-keys";
import { USE_FAKE_VERSIONS } from "@/use-fake-versions-injection-key";
import { CURRENT_VERSION_DISPLAYED } from "@/components/current-version-displayed";

const { $gettext } = useGettext();

const error = ref("");
const should_display_under_construction_message = ref(true);
const is_loading_versions = ref(true);
const show_title = $gettext("Change display of versions");
const display = ref<VersionsDisplayChoices>(FAKE_DATA_ALL_VERSIONS);
const use_fake_versions = strictInject(USE_FAKE_VERSIONS);
const versions_display = strictInject(CURRENT_VERSION_DISPLAYED);

provide(IS_LOADING_VERSION, is_loading_versions);
provide(VERSIONS_LOADING_ERROR, error);

watch(display, () => {
    use_fake_versions.value = display.value !== ALL_VERSIONS;
    versions_display.switchToLatestVersion();
});

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

.tlp-form-element {
    margin: var(--tlp-medium-spacing);
}

select {
    order: 2;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
</style>
