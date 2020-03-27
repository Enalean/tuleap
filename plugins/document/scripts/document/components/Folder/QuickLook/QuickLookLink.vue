<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
  -->

<template>
    <div class="document-quick-look-document-action">
        <button
            type="button"
            class="tlp-button-primary tlp-button-small document-quick-look-action-button-margin"
            v-on:click="redirectUrl"
        >
            <i class="fa fa-external-link tlp-button-icon"></i>
            <translate>Open link</translate>
        </button>
        <drop-down-quick-look v-bind:item="item" />
        <div class="document-header-spacer"></div>
        <quick-look-delete-button v-bind:item="item" />
    </div>
</template>

<script>
import { mapState } from "vuex";
import DropDownQuickLook from "../DropDown/DropDownQuickLook.vue";
import QuickLookDeleteButton from "../ActionsQuickLookButton/QuickLookDeleteButton.vue";

export default {
    name: "QuickLookLink",
    components: { DropDownQuickLook, QuickLookDeleteButton },
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["project_id"]),
    },
    methods: {
        redirectUrl() {
            window.location.assign(
                encodeURI(
                    `/plugins/docman/?group_id=${this.project_id}&action=show&id=${this.item.id}`
                )
            );
        },
    },
};
</script>
