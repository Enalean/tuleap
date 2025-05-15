<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa-fw document-folder-content-icon" v-bind:class="ICON_EMBEDDED"></i>
        <a v-bind:href="document_link_url" class="document-folder-subitem-link" draggable="false">
            {{ item.title
            }}<i
                class="fas document-action-icon"
                v-bind:class="ACTION_ICON_EMBEDDED"
                aria-hidden="true"
            ></i>
        </a>
        <span class="tlp-badge-warning document-badge-corrupted" v-if="is_corrupted">
            {{ $gettext("Corrupted") }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { ICON_EMBEDDED, ACTION_ICON_EMBEDDED } from "../../../constants";
import FakeCaret from "./FakeCaret.vue";
import type { Embedded, Folder } from "../../../type";
import { useState } from "vuex-composition-helpers";
import { computed } from "vue";
import { useRouter } from "../../../helpers/use-router";

const router = useRouter();

const props = defineProps<{ item: Embedded }>();

const { current_folder } = useState<{ current_folder: Folder }>(["current_folder"]);

const is_corrupted = computed((): boolean => {
    return (
        !("embedded_file_properties" in props.item) || props.item.embedded_file_properties === null
    );
});
const document_link_url = computed((): string => {
    const { href } = router.resolve({
        name: "item",
        params: {
            folder_id: String(current_folder.value.id),
            item_id: String(props.item.id),
        },
    });

    return href;
});
</script>
