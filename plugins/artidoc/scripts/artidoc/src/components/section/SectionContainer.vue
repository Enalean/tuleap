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
    <div class="artidoc-section-container" v-bind:class="additional_class">
        <section-content v-bind:section="section" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import SectionContent from "./SectionContent.vue";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { isArtifactSection, isPendingArtifactSection } from "@/helpers/artidoc-section.type";

const props = defineProps<{ section: ReactiveStoredArtidocSection }>();
const additional_class = computed(() => {
    const color = isArtifactSection(props.section.value)
        ? props.section.value.artifact.tracker.color
        : isPendingArtifactSection(props.section.value)
          ? props.section.value.tracker.color
          : "";

    return color !== "" ? `tlp-swatch-${color}` : "artidoc-section-container-without-border";
});
</script>

<style lang="scss">
@keyframes blink-section {
    0% {
        background: var(--tlp-info-color-transparent-90);
    }

    50% {
        background: var(--tlp-white-color);
    }

    100% {
        background: var(--tlp-info-color-transparent-90);
    }
}

@keyframes pulse-section {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.05);
    }

    100% {
        transform: scale(1);
    }
}
</style>

<style lang="scss" scoped>
@use "@/themes/includes/whitespace";
@use "@/themes/includes/viewport-breakpoint";

.artidoc-section-container {
    --tuleap-artidoc-section-background: var(--tlp-white-color);
    --border-width: 4px;

    padding: var(--tlp-medium-spacing) 0 var(--tlp-medium-spacing)
        calc(#{whitespace.$section-left-padding} - var(--border-width));
    transition: background-color 75ms ease-in-out;
    border-left: var(--border-width) solid var(--border-color);
    background: var(--tuleap-artidoc-section-background);

    &-without-border {
        --border-color: transparent;
    }

    &:has(.document-section-cancel-save-buttons) {
        --tuleap-artidoc-section-background: var(--tlp-main-color-lighter-90);

        &:has(.document-section-is-in-error) {
            --tuleap-artidoc-section-background: var(--tlp-danger-color-lighter-90);
        }
    }

    &:has(.document-section-is-being-saved) {
        animation: blink-section 1200ms ease-in-out alternate infinite;
    }

    &:has(.document-section-is-just-saved) {
        --tuleap-artidoc-section-background: var(--tlp-success-color-lighter-90);

        animation: pulse-section 500ms ease-in-out;
    }

    &:has(.document-section-is-just-refreshed) {
        --tuleap-artidoc-section-background: var(--tlp-info-color-lighter-90);

        animation: pulse-section 500ms ease-in-out;
    }

    &:has(.document-section-is-outdated) {
        --tuleap-artidoc-section-background: var(--tlp-alert-warning-background);
    }
}

@media screen and (min-width: #{viewport-breakpoint.$small-screen-size}) and (max-width: #{viewport-breakpoint.$medium-screen-size-when-document-sidebar-is-expanded}) {
    .artidoc-section-container {
        padding-right: var(--artidoc-sidebar-button-width);
    }
}
</style>
