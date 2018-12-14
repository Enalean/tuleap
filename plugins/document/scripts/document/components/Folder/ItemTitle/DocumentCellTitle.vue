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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <td>
        <i class="fa fa-fw"></i>
        <i class="fa fa-fw" v-bind:class="icon_class"></i>
        {{ item.title }}
    </td>
</template>

<script>
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_WIKI } from "../../../constants.js";

export default {
    name: "DocumentCellTitle",
    props: {
        item: Object
    },
    computed: {
        icon_class() {
            switch (this.item.type) {
                case TYPE_EMBEDDED:
                    return "fa-file-text document-text-icon";
                case TYPE_WIKI:
                    return "fa-wikipedia-w document-wiki-icon";
                case TYPE_EMPTY:
                default:
                    return "fa-file-o document-empty-icon";
            }
        }
    }
};
</script>
