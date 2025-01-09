<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-card-xref-label">
        <a
            class="taskboard-card-xref"
            v-bind:href="card.artifact_html_uri"
            v-bind:class="additional_classnames"
            data-not-drag-handle="true"
            data-test="xref"
        >
            {{ card.xref }}
        </a>
        <span class="taskboard-card-label" v-if="!card.is_in_edit_mode">{{ label }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Card } from "../../../../../type";

const props = defineProps<{
    card: Card;
    label: string;
}>();

const additional_classnames = computed((): string => {
    if (props.card.background_color) {
        return `tlp-swatch-${props.card.background_color}`;
    }

    return `tlp-swatch-${props.card.color}`;
});
</script>
