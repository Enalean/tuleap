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
    <div class="taskboard-card-label-editor">
        <textarea
            class="tlp-textarea taskboard-card-label-input-mirror"
            v-bind:value="value"
            rows="1"
            ref="mirror"
        ></textarea>
        <textarea
            class="tlp-textarea taskboard-card-label-input"
            v-bind:value="value"
            v-on:input="onInputEmit"
            v-on:keydown.enter="enter"
            v-on:keyup="keyup"
            v-bind:rows="rows"
            v-bind:placeholder="$gettext('Card label…')"
            v-bind:aria-label="$gettext('Set card label')"
            v-bind:readonly="readonly"
            ref="textarea_ref"
            data-test="label-editor"
            data-navigation="add-form"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { autoFocusAutoSelect } from "../../../../../../../helpers/autofocus-autoselect";

const LINE_HEIGHT_IN_PX = 18;
const TOP_AND_BOTTOM_PADDING_IN_PX = 16;

withDefaults(
    defineProps<{
        value: string;
        readonly: boolean;
    }>(),
    {
        value: "",
        readonly: false,
    },
);

const emit = defineEmits<{
    (e: "save"): void;
    (e: "input", event: string): void;
}>();

const textarea_ref = ref();
const mirror = ref();
const rows = ref(1);

onMounted((): void => {
    setTimeout(computeRows, 10);
    autoFocusAutoSelect(textarea_ref.value);
});

function enter(event: KeyboardEvent): void {
    if (!event.shiftKey) {
        emit("save");
    }
}

function keyup(): void {
    computeRows();
}

function computeRows(): void {
    rows.value = Math.ceil(
        (mirror.value.scrollHeight - TOP_AND_BOTTOM_PADDING_IN_PX) / LINE_HEIGHT_IN_PX,
    );
}

function onInputEmit($event: Event): void {
    if (!($event.target instanceof HTMLTextAreaElement)) {
        return;
    }

    emit("input", $event.target.value);
}
</script>
