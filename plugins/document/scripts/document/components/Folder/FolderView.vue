<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
        <folder-header />
        <div class="tlp-framed">
            <clipboard-content-information />
            <drag-n-drop-handler v-if="!is_loading_folder" />
            <under-the-fold-notification v-if="!is_loading_folder" />
            <folder-loading-screen v-if="is_loading_folder" />
            <empty-folder-for-writers
                v-else-if="is_folder_empty && current_folder && current_folder.user_can_write"
            />
            <empty-folder-for-readers
                v-else-if="is_folder_empty && current_folder && !current_folder.user_can_write"
            />
            <folder-content v-else />
        </div>
    </div>
</template>
<script setup lang="ts">
import FolderHeader from "./FolderHeader.vue";
import FolderLoadingScreen from "./FolderLoadingScreen.vue";
import FolderContent from "./FolderContent.vue";
import EmptyFolderForWriters from "./EmptyState/EmptyFolderForWriters.vue";
import EmptyFolderForReaders from "./EmptyState/EmptyFolderForReaders.vue";
import DragNDropHandler from "./DragNDrop/DragNDropHandler.vue";
import UnderTheFoldNotification from "./DropDown/NewDocument/UnderTheFoldNotification.vue";
import ClipboardContentInformation from "./Clipboard/ClipboardContentInformation.vue";
import { useGetters, useState } from "vuex-composition-helpers";
import type { State } from "../../type";
import type { RootGetter } from "../../store/getters";

const { is_loading_folder, current_folder } = useState<
    Pick<State, "is_loading_folder" | "current_folder">
>(["is_loading_folder", "current_folder"]);

const { is_folder_empty } = useGetters<Pick<RootGetter, "is_folder_empty">>(["is_folder_empty"]);
</script>
