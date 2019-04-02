<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <a
        v-if="item.user_can_write"
        v-bind:class="button_classes"
        v-bind:data-tlp-tooltip="cannot_update_wiki_beacause_approval_table"
        v-on:click="goToUpdate"
        data-test="docman-item-update-button"
    >
        <i v-bind:class="iconClasses"></i>
        <translate>Update</translate>
    </a>
</template>
<script>
import { mapState } from "vuex";
import { TYPE_EMPTY, TYPE_LINK, TYPE_WIKI } from "../../../constants.js";

import { redirect_to_url } from "../../../helpers/location-helper.js";
export default {
    name: "UpdatButton",
    props: {
        item: Object,
        buttonClasses: String,
        iconClasses: String
    },
    computed: {
        ...mapState(["project_id"]),
        is_item_a_wiki_with_approval_table() {
            return this.item.type === TYPE_WIKI && this.item.approval_table !== null;
        },
        cannot_update_wiki_beacause_approval_table() {
            return this.$gettext("This wiki has a approval table, you can't update it.");
        },
        button_classes() {
            let classes = this.buttonClasses;

            if (this.is_item_a_wiki_with_approval_table) {
                classes += " document-update-button-disabled tlp-tooltip tlp-tooltip-left";
            }

            return classes;
        }
    },
    methods: {
        goToUpdate() {
            if (this.is_item_a_wiki_with_approval_table) {
                return;
            }

            if (this.item.type === TYPE_EMPTY || this.item.type === TYPE_LINK) {
                const action =
                    this.item.type !== TYPE_EMPTY ? "action_new_version" : "action_update";
                return redirect_to_url(
                    `/plugins/docman/index.php?group_id=${this.project_id}&id=${
                        this.item.id
                    }&action=${action}`
                );
            }

            this.showUpdateFileModal();
        },
        showUpdateFileModal() {
            document.dispatchEvent(
                new CustomEvent("show-update-item-modal", {
                    detail: { current_item: this.item }
                })
            );
        }
    }
};
</script>
