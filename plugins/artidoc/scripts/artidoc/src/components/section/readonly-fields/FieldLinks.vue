<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <label class="tlp-label document-label">{{ field.label }}</label>
    <div
        class="artidoc-link-field-row"
        v-for="link of field.value"
        v-bind:key="link.artifact_id"
        v-bind:title="link.title"
        data-test="linked-artifact"
    >
        <span>{{ link.link_label }}</span>
        <span class="artidoc-link-field-xref">
            <a v-bind:href="link.html_uri" class="artidoc-link-field-link cross-reference"
                ><span v-bind:class="getCrossRefBadgeClasses(link)"
                    >{{ link.tracker_shortname }} #{{ link.artifact_id }}</span
                ><span class="artidoc-link-field-title">{{ link.title }}</span></a
            >
        </span>
        <span
            v-if="link.status !== null"
            v-bind:class="getStatusBadgeClasses(link.status)"
            data-test="linked-artifact-status"
        >
            {{ link.status.label }}
        </span>
    </div>
    <p v-if="field.value.length === 0" class="tlp-property-empty" data-test="empty-state">
        {{ $gettext("Empty") }}
    </p>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type {
    LinkedArtifactStatus,
    ReadonlyFieldLinkedArtifact,
    ReadonlyFieldLinks,
} from "@/sections/readonly-fields/ReadonlyFields";

const { $gettext } = useGettext();

defineProps<{
    field: ReadonlyFieldLinks;
}>();

function getCrossRefBadgeClasses(link: ReadonlyFieldLinkedArtifact): string {
    return `cross-ref-badge tlp-swatch-${link.tracker_color}`;
}

function getStatusBadgeClasses(status: LinkedArtifactStatus): string {
    const badge_class = status.color !== "" ? `tlp-badge-${status.color}` : "tlp-badge-secondary";
    return `tlp-badge-outline ${badge_class}`;
}
</script>
<style scoped lang="scss">
$link-row-padding: 8px;

.artidoc-link-field-row {
    display: flex;
    align-items: center;
    padding: $link-row-padding;
    gap: $link-row-padding;
}

.artidoc-link-field-xref {
    flex: auto;
}

.artidoc-link-field-link {
    display: flex;
    gap: 6px;
    align-items: center;
    color: var(--tlp-dark-color);
}

.artidoc-link-field-title {
    flex: 1;
    width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
