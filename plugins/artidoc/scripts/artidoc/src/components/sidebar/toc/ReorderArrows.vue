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
    <button
        v-if="!is_first"
        v-bind:title="title_up"
        data-test="move-up"
        class="tlp-button-primary"
        type="button"
        v-on:click="up"
    >
        <i class="fa-solid fa-chevron-up" role="img"></i>
    </button>
    <span class="reorder-arrows-spacer"></span>
    <button
        v-if="!is_last"
        v-bind:title="title_down"
        data-test="move-down"
        class="tlp-button-primary"
        type="button"
        v-on:click="down"
    >
        <i class="fa-solid fa-chevron-down" role="img"></i>
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Fault } from "@tuleap/fault";
import type {
    InternalArtidocSectionId,
    ReactiveStoredArtidocSection,
} from "@/sections/SectionsCollection";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { SectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import type { SectionsStructurer } from "@/sections/reorder/SectionsStructurer";

const props = defineProps<{
    is_first: boolean;
    is_last: boolean;
    section: ReactiveStoredArtidocSection;
    sections_reorderer: SectionsReorderer;
    sections_structurer: SectionsStructurer;
}>();

const { $gettext } = useGettext();

const title_up = $gettext("Move up");
const title_down = $gettext("Move down");

const document_id = strictInject(DOCUMENT_ID);

const emit = defineEmits<{
    (event: "moved-section-up-or-down", sections: InternalArtidocSectionId[]): void;
    (event: "moving-section-up-or-down", sections: InternalArtidocSectionId[]): void;
    (event: "moved-section-up-or-down-fault", fault: Fault): void;
}>();

const dispatchMovedSection = (): void => {
    const children_ids = props.sections_structurer
        .getSectionChildren(props.section.value)
        .map((child) => child.value);
    emit("moved-section-up-or-down", [props.section.value, ...children_ids]);
};

const dispatchMovingSection = (): void => {
    const children_ids = props.sections_structurer
        .getSectionChildren(props.section.value)
        .map((child) => child.value);
    emit("moving-section-up-or-down", [props.section.value, ...children_ids]);
};

const dispatchFault = (fault: Fault): void => {
    emit("moved-section-up-or-down-fault", fault);
};

function up(event: Event): void {
    dispatchMovingSection();

    props.sections_reorderer.moveSectionUp(document_id, props.section).match(() => {
        if (event.target instanceof HTMLButtonElement) {
            event.target.focus();
        }

        dispatchMovedSection();
    }, dispatchFault);
}

function down(event: Event): void {
    dispatchMovingSection();

    props.sections_reorderer.moveSectionDown(document_id, props.section).match(() => {
        if (event.target instanceof HTMLButtonElement) {
            event.target.focus();
        }

        dispatchMovedSection();
    }, dispatchFault);
}
</script>

<style scoped lang="scss">
.reorder-arrows-spacer {
    flex: 1 1 auto;
}

$button-size: 16px;

button {
    width: $button-size;
    height: $button-size;
    padding: 0;
    border-radius: 50%;
    font-size: 0.625rem;
}
</style>
