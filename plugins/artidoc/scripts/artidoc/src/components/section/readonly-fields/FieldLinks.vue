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
    <table v-if="field.value.length > 0" class="tlp-table">
        <tbody class="artidoc-link-field-table-body">
            <tr
                class="artidoc-link-field-row document-artifact-link-row"
                v-for="link of sorted_linked_artifacts"
                v-bind:key="link.artifact_id"
                v-bind:title="link.title"
                v-bind:class="{ 'parent-artifact-link': isReverseChild(link.link_type) }"
                data-test="linked-artifact"
            >
                <td class="artidoc-link-field-link-type document-link-type">
                    {{ link.link_label }}
                </td>
                <td class="artidoc-link-field-xref document-xref">
                    <a
                        v-bind:href="link.html_uri"
                        class="artidoc-link-field-link document-artifact-link-row-anchor cross-reference"
                        ><span v-bind:class="getCrossRefBadgeClasses(link)"
                            >{{ link.tracker_shortname }} #{{ link.artifact_id }}</span
                        ><span class="artidoc-link-field-title">{{ link.title }}</span></a
                    >
                    <span
                        v-if="!isLinkedArtifactInCurrentProject(link)"
                        class="artidoc-link-field-artifact-project document-artifact-link-project"
                        >{{ getLabelAndIcon(link.project) }}</span
                    >
                </td>
                <td class="artidoc-link-field-status document-artifact-link-table-status-cell">
                    <span
                        v-if="link.status !== null"
                        v-bind:class="getStatusBadgeClasses(link.status)"
                        data-test="linked-artifact-status"
                        >{{ link.status.label }}</span
                    >
                </td>
            </tr>
        </tbody>
    </table>
    <p v-else class="tlp-property-empty" data-test="empty-state">
        {{ $gettext("Empty") }}
    </p>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type {
    LinkedArtifactProject,
    LinkedArtifactStatus,
    ReadonlyFieldLinkedArtifact,
    ReadonlyFieldLinks,
} from "@/sections/readonly-fields/ReadonlyFields";
import { PROJECT_ID } from "@/project-id-injection-key";
import {
    isReverseChild,
    sortLinkedArtifacts,
} from "@/components/section/readonly-fields/sort-linked-artifacts";

const { $gettext } = useGettext();
const current_project_id = strictInject(PROJECT_ID);

const props = defineProps<{
    field: ReadonlyFieldLinks;
}>();

const sorted_linked_artifacts = computed(() => sortLinkedArtifacts(props.field.value));

function getCrossRefBadgeClasses(link: ReadonlyFieldLinkedArtifact): string {
    return `cross-ref-badge document-cross-ref-badge tlp-swatch-${link.tracker_color}`;
}

function getStatusBadgeClasses(status: LinkedArtifactStatus): string {
    const badge_class =
        status.color !== ""
            ? `tlp-badge-${status.color} tlp-swatch-${status.color}`
            : "tlp-badge-secondary";
    return `tlp-badge-outline document-badge-outline ${badge_class}`;
}

function isLinkedArtifactInCurrentProject(link: ReadonlyFieldLinkedArtifact): boolean {
    return link.project.id === current_project_id;
}

function getLabelAndIcon(project: LinkedArtifactProject): string {
    if (project.icon === "") {
        return project.label;
    }
    return project.icon + " " + project.label;
}
</script>
<style scoped lang="scss">
.tlp-table {
    border-collapse: collapse;
}

.tlp-table > .artidoc-link-field-table-body {
    background: transparent;

    > .artidoc-link-field-row:not(:hover, :focus-within) > td {
        background: transparent;
    }

    > .artidoc-link-field-row {
        border: 1px solid var(--tlp-neutral-normal-color);

        &.parent-artifact-link:has(+ :not(.parent-artifact-link)) {
            border-bottom-width: 3px;
        }
    }
}

.artidoc-link-field-link-type,
.artidoc-link-field-status {
    width: 1px;
    white-space: nowrap;
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

.artidoc-link-field-status {
    text-align: right;
}

.artidoc-link-field-artifact-project {
    margin: 2px 0 0;
    color: var(--tlp-dimmed-color);
    font-size: 0.65rem;
}
</style>
