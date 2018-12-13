<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="empty-pane">
        <div class="empty-page-illustration">
            <empty-folder-for-writers-svg/>
        </div>
        <p class="empty-page-text" v-translate>It's time to add new documents!</p>
        <new-item-button class="tlp-button-primary tlp-button-large"/>
    </div>
</template>

<script>
import EmptyFolderForWritersSvg from "./EmptyFolderForWritersSvg.vue";
import NewItemButton from "../NewItem/NewItemButton.vue";

export default {
    name: "EmptyFolderForWriters",
    components: { EmptyFolderForWritersSvg, NewItemButton }
};
</script>
