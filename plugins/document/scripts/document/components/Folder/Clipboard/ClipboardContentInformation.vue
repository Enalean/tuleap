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
        <i class="fa fa-info-circle document-clipboard-content-information-icon"></i>
        <translate
            key="cut-information"
            v-bind:translate-params="{ title: item_title }"
            v-if="operation_type === CLIPBOARD_OPERATION_CUT"
        >
            You are currently moving "%{ title }". You can paste it in a folder you are allowed to
            write into using the folder action drop-down. You also cannot move the item somewhere
            where the name is already used by another item.
        </translate>
        <translate
            key="copy-information"
            v-bind:translate-params="{ title: item_title }"
            v-else-if="operation_type === CLIPBOARD_OPERATION_COPY"
        >
            You are currently copying "%{ title }". You can paste it in a folder you are allowed to
            write into using the folder action drop-down.
        </translate>
    </p>
    <p class="tlp-text-info" v-else-if="item_title !== null && pasting_in_progress">
        <i class="fa fa-spin fa-circle-o-notch document-clipboard-content-information-icon"></i>
        <translate
            key="cut-in-progress-information"
            v-bind:translate-params="{ title: item_title }"
            v-if="operation_type === CLIPBOARD_OPERATION_CUT"
        >
            "%{ title }" is being moved…
        </translate>
        <translate
            key="copy-in-progress-information"
            v-bind:translate-params="{ title: item_title }"
            v-else-if="operation_type === CLIPBOARD_OPERATION_COPY"
        >
            "%{ title }" is being copied…
        </translate>
    </p>
</template>
<script>
import { mapState } from "vuex";
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants.js";

export default {
    name: "ClipboardContentInformation",
    data: () => {
        return {
            CLIPBOARD_OPERATION_CUT,
            CLIPBOARD_OPERATION_COPY,
        };
    },
    computed: {
        ...mapState("clipboard", ["item_title", "operation_type", "pasting_in_progress"]),
    },
};
</script>
