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
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa fa-fw document-folder-content-icon" v-bind:class="icon()"></i>
        <a v-bind:href="document_link_url()" class="document-folder-subitem-link" draggable="false">
            {{ item.title }}
        </a>
    </div>
</template>

<script lang="ts">
import FakeCaret from "./FakeCaret.vue";
import { ICON_LINK } from "../../../constants";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Link } from "../../../type";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({ components: { FakeCaret } })
export default class LinkCellTitle extends Vue {
    @Prop({ required: true })
    readonly item!: Link;

    @configuration.State
    readonly project_id!: number;

    document_link_url(): string {
        return `/plugins/docman/?group_id=${this.project_id}&action=show&id=${this.item.id}`;
    }

    icon(): string {
        return ICON_LINK;
    }
}
</script>
