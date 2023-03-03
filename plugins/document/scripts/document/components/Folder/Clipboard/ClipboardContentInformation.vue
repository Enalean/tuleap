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
    <p class="tlp-text-info" v-if="item_title !== null && !pasting_in_progress">
        <i class="fa-solid fa-circle-info document-clipboard-content-information-icon"></i>
        <template v-if="operation_type === CLIPBOARD_OPERATION_CUT">{{ moving_title }}</template>
        <template v-else-if="operation_type === CLIPBOARD_OPERATION_COPY">
            {{ copying_title }}
        </template>
    </p>
    <p class="tlp-text-info" v-else-if="item_title !== null && pasting_in_progress">
        <i class="fa-solid fa-spin fa-circle-notch document-clipboard-content-information-icon"></i>
        <template v-if="operation_type === CLIPBOARD_OPERATION_CUT">
            {{ item_being_moved_title }}
        </template>
        <template v-else-if="operation_type === CLIPBOARD_OPERATION_COPY">
            {{ item_being_copied_title }}
        </template>
    </p>
</template>

<script setup lang="ts">
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants";
import { useState } from "vuex-composition-helpers";
import type { ClipboardState } from "../../../store/clipboard/module";
import { computed } from "vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const { interpolate, $gettext } = useGettext();

const { pasting_in_progress, item_title, operation_type } = useState<
    Pick<ClipboardState, "pasting_in_progress" | "item_title" | "operation_type">
>("clipboard", ["pasting_in_progress", "item_title", "operation_type"]);

const moving_title = computed((): string => {
    return interpolate(
        $gettext(
            'You are currently moving "%{ title }". You can paste it in a folder you are allowed to write into using the folder action drop-down. You also cannot move the item somewhere where the name is already used by another item.'
        ),
        {
            title: item_title.value,
        }
    );
});

const copying_title = computed((): string => {
    return interpolate(
        $gettext(
            'You are currently copying "%{ title }". You can paste it in a folder you are allowed to write into using the folder action drop-down.'
        ),
        {
            title: item_title.value,
        }
    );
});
const item_being_moved_title = computed((): string => {
    return interpolate($gettext('"%{ title }" is being moved…'), {
        title: item_title.value,
    });
});
const item_being_copied_title = computed((): string => {
    return interpolate($gettext('"%{ title }" is being copied…'), {
        title: item_title.value,
    });
});
</script>
