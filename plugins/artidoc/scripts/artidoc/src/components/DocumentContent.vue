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
    <ol>
        <li
            v-for="section in sections"
            v-bind:key="section.artifact.id"
            v-bind:id="`${section.artifact.id}`"
        >
            <section-content v-bind:section="section" />
        </li>
    </ol>
</template>

<script setup lang="ts">
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import SectionContent from "@/components/SectionContent.vue";

const { sections } = useInjectSectionsStore();
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
ol {
    padding: 0;
    counter-reset: item-without-dot;
}

$section-horizontal-padding: calc(var(--tlp-jumbo-spacing) + var(--tlp-large-spacing));

li {
    --tuleap-artidoc-section-background: var(--tlp-white-color);
    --ck-color-base-background: var(--tuleap-artidoc-section-background);

    position: relative;
    margin: 0 0 var(--tlp-medium-spacing);
    padding: var(--tlp-medium-spacing) var(--tlp-jumbo-spacing) var(--tlp-medium-spacing)
        $section-horizontal-padding;
    transition: background-color 75ms ease-in-out;
    background: var(--tuleap-artidoc-section-background);
    counter-increment: item-without-dot;

    &:first-child {
        padding-top: var(--tlp-large-spacing);
    }

    &:last-child {
        margin: 0;
    }

    &:has(.ck-editor) {
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

    &:has(.document-section-is-outdated) {
        --tuleap-artidoc-section-background: var(--tlp-alert-warning-background);
    }
}

$section-number-padding-left: var(--tlp-small-spacing);
$section-number-padding-right: var(--tlp-medium-spacing);
$section-title-height: 57px;

li::before {
    content: counter(item-without-dot);
    position: absolute;
    left: 0;
    width: calc(
        #{$section-horizontal-padding} - #{$section-number-padding-left} - #{$section-number-padding-right}
    );
    padding: 0 $section-number-padding-right 0 $section-number-padding-left;
    color: var(--tlp-dimmed-color-lighter-50);
    font-style: italic;
    font-weight: 600;
    line-height: $section-title-height;
    text-align: right;
}
</style>
