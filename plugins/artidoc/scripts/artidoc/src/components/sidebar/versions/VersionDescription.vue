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
    <p
        class="description-preview"
        v-bind:class="{ 'description-overflowing': is_overflowing }"
        v-if="version.description.isValue()"
        ref="preview"
    >
        {{ version.description.unwrapOr("") }}
    </p>
    <details v-if="version.description.isValue()">
        <summary>
            <span class="summary-open">{{ $gettext("Expand") }}</span>
            <span class="summary-closed">{{ $gettext("Collapse") }}</span>
        </summary>
        <p class="description">
            {{ version.description.unwrapOr("") }}
        </p>
    </details>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { Version } from "./fake-list-of-versions";

defineProps<{ version: Version }>();

const { $gettext } = useGettext();

const is_overflowing = ref(true);
const preview = ref<HTMLElement | null>(null);
let preview_absolute_width = 0;

function detectOverflowing(): void {
    if (preview.value === null) {
        return;
    }

    // tolerance is here to avoid flickering when the description is about to overflow
    // this means that there is a slight chance that a description ends up with a useless expand/collapse
    const tolerance = 1;
    is_overflowing.value = preview_absolute_width > preview.value.clientWidth - tolerance;
}

function computeDescriptionAbsoluteWidth(): void {
    if (preview.value === null) {
        return;
    }

    // naive implementation would be to just check if clientWidth is lesser than scrollWidth
    // but as soon as the element is not overflowing we remove the class that allows overflow.
    // We need to create an element (outside of the dom to not pollute it) to compute the width.
    const ctx = document.createElement("canvas").getContext("2d");
    if (ctx === null) {
        return;
    }

    const { fontSize, fontFamily } = getComputedStyle(preview.value);
    ctx.font = `${fontSize} ${fontFamily}`;

    preview_absolute_width = ctx.measureText(preview.value.innerText).width;
}

const observer = new ResizeObserver(detectOverflowing);

onMounted(() => {
    if (preview.value === null) {
        return;
    }

    computeDescriptionAbsoluteWidth();
    observer.observe(preview.value);
    detectOverflowing();
});

onBeforeUnmount(() => {
    observer.disconnect();
});
</script>

<style scoped lang="scss">
details {
    display: flex;
    flex-direction: column;
}

summary {
    list-style-position: inside;
    color: var(--tlp-main-color);
    font-size: 0.75rem;
    cursor: pointer;

    &::marker {
        content: "\f107"; // fa-angle-down
        font-family: "Font Awesome 6 Free";
        font-size: 12px;
        font-weight: 900;
    }

    &:hover,
    &:focus {
        > .summary-open,
        > .summary-closed {
            text-decoration: underline;
        }
    }
}

.summary-open,
.summary-closed {
    margin: 0 0 0 calc(var(--tlp-small-spacing) / 2);
}

.summary-closed {
    display: none;
}

details[open] {
    > summary {
        order: 1;

        &::marker {
            content: "\f106"; // fa-angle-up
        }

        > .summary-open {
            display: none;
        }

        > .summary-closed {
            display: revert;
        }
    }
}

.description,
.description-preview {
    margin: 0;
    font-size: 0.75rem;
    line-height: normal;
}

.description-preview {
    &.description-overflowing {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        &:has(+ details[open]) {
            display: none;
        }
    }

    &:not(.description-overflowing) + details {
        display: none;
    }
}
</style>
