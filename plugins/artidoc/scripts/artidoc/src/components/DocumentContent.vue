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

<style lang="scss" scoped>
ol {
    padding: 0;
    counter-reset: item-without-dot;
}

$section-horizontal-padding: calc(var(--tlp-jumbo-spacing) + var(--tlp-large-spacing));

li {
    position: relative;
    margin: 0 0 var(--tlp-medium-spacing);
    padding: var(--tlp-medium-spacing) var(--tlp-jumbo-spacing) var(--tlp-medium-spacing)
        $section-horizontal-padding;
    counter-increment: item-without-dot;

    &:first-child {
        padding-top: var(--tlp-large-spacing);
    }

    &:last-child {
        margin: 0;
    }

    &:has(.ck-editor) {
        --ck-color-base-background: var(--tlp-main-color-lighter-90);

        background: var(--ck-color-base-background);
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
