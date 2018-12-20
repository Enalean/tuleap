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
    <div>
        <fake-caret v-bind:item="item"/>
        <i class="fa fa-fw fa-wikipedia-w document-wiki-icon document-folder-content-icon"></i>
        <a v-bind:href="wiki_html_url" class="document-folder-subitem-link">
            {{ item.title }}
        </a>
    </div>
</template>

<script>
import FakeCaret from "./FakeCaret.vue";

export default {
    name: "WikiCellTitle",
    components: { FakeCaret },
    props: {
        item: Object
    },
    computed: {
        wiki_html_url() {
            return this.item.wiki_properties.html_url;
        }
    }
};
</script>
