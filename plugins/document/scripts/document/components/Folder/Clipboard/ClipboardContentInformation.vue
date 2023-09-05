<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <p class="tlp-text-info" v-if="has_item_in_clipboard && !clipboard.pasting_in_progress">
        <i class="fa-solid fa-circle-info document-clipboard-content-information-icon"></i>
        <template v-if="clipboard.operation_type === CLIPBOARD_OPERATION_CUT"
            >{{ moving_title }}
        </template>
        <template v-else-if="clipboard.operation_type === CLIPBOARD_OPERATION_COPY">
            {{ copying_title }}
        </template>
    </p>

    <p class="tlp-text-info" v-else-if="has_item_in_clipboard && clipboard.pasting_in_progress">
        <i class="fa-solid fa-spin fa-circle-notch document-clipboard-content-information-icon"></i>
        <template v-if="clipboard.operation_type === CLIPBOARD_OPERATION_CUT">
            {{ item_being_moved_title }}
        </template>
        <template v-else-if="clipboard.operation_type === CLIPBOARD_OPERATION_COPY">
            {{ item_being_copied_title }}
        </template>
    </p>
</template>

<script setup lang="ts">
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { useClipboardStore } from "../../../stores/clipboard";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { useStore } from "vuex";

const store = useStore();

const { project_id, user_id } = useNamespacedState<
    Pick<ConfigurationState, "project_id" | "user_id">
>("configuration", ["project_id", "user_id"]);
const clipboard = useClipboardStore(store, project_id.value, user_id.value);

const { interpolate, $gettext } = useGettext();

const has_item_in_clipboard = computed((): boolean => {
    return clipboard.item_title !== null && clipboard.item_title !== "";
});

const moving_title = computed((): string => {
    return interpolate(
        $gettext(
            'You are currently moving "%{ title }". You can paste it in a folder you are allowed to write into using the folder action drop-down. You also cannot move the item somewhere where the name is already used by another item.',
        ),
        { title: clipboard.item_title },
    );
});

const copying_title = computed((): string => {
    return interpolate(
        $gettext(
            'You are currently copying "%{ title }". You can paste it in a folder you are allowed to write into using the folder action drop-down.',
        ),
        { title: clipboard.item_title },
    );
});
const item_being_moved_title = computed((): string => {
    return interpolate($gettext('"%{ title }" is being moved…'), {
        title: clipboard.item_title,
    });
});
const item_being_copied_title = computed((): string => {
    return interpolate($gettext('"%{ title }" is being copied…'), {
        title: clipboard.item_title,
    });
});
</script>
