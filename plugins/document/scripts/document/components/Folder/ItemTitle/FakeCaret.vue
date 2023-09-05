<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->
<template>
    <i
        class="fa-solid fa-fw document-folder-toggle document-folder-content-fake-caret"
        v-if="can_be_displayed()"
    ></i>
</template>
<script setup lang="ts">
import { isFolder } from "../../../helpers/type-check-helper";
import type { Item, State } from "../../../type";
import { useState } from "vuex-composition-helpers";

const props = defineProps<{ item: Item }>();

const { folder_content, current_folder } = useState<
    Pick<State, "folder_content" | "current_folder">
>(["folder_content", "current_folder"]);

function is_item_in_current_folder(): boolean {
    return props.item.parent_id === current_folder.value.id;
}

function is_item_sibling_of_a_folder(): boolean {
    return Boolean(
        folder_content.value.find(
            (item) => item.parent_id === current_folder.value.id && isFolder(item),
        ),
    );
}

function can_be_displayed(): boolean {
    return !is_item_in_current_folder() || is_item_sibling_of_a_folder();
}
</script>
