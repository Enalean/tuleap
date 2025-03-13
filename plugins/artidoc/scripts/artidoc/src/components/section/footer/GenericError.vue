<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="tlp-alert-danger">
        <p>
            {{ $gettext("An error occurred while trying to update the section.") }}
        </p>

        <div class="tlp-property">
            <label class="tlp-label">{{ $gettext("Error details") }}</label>
            <blockquote>{{ error_message }}</blockquote>
        </div>

        <template v-if="is_artifact">
            <p>
                {{
                    $gettext(
                        "You can open the corresponding artifact in a new tab to fix the situation.",
                    )
                }}
            </p>

            <div class="alert-error-buttons">
                <a v-bind:href="href" target="_blank" rel="noreferrer" class="tlp-button-danger">
                    <i
                        class="fa-solid fa-arrow-up-right-from-square tlp-button-icon"
                        aria-hidden="true"
                    ></i>
                    {{ $gettext("Open artifact") }}
                </a>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import { computed } from "vue";

const props = defineProps<{
    error_message: string;
    section: ReactiveStoredArtidocSection;
}>();

const { $gettext } = useGettext();

const is_artifact = computed(() => isArtifactSection(props.section.value));

const href = computed(() =>
    isArtifactSection(props.section.value)
        ? "/plugins/tracker/?aid=" + encodeURIComponent(props.section.value.artifact.id)
        : "",
);
</script>

<style scoped lang="scss">
.alert-error-buttons {
    margin: var(--tlp-small-spacing) 0 0;
}

p:has(+ blockquote) {
    margin: 0;
}
</style>
