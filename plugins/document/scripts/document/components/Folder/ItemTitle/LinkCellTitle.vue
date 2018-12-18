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
        <i class="fa fa-fw fa-link document-link-icon"></i>
        <a v-bind:href="document_link_url" class="document-folder-subitem-link">
            {{ item.title }}
        </a>
    </td>
</template>

<script>
export default {
    name: "LinkCellTitle",
    props: {
        item: Object
    },
    computed: {
        document_link_url() {
            return this.item.link_properties.html_url;
        }
    }
};
</script>
