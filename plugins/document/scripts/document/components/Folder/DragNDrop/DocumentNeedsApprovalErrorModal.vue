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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <error-modal v-on:error-modal-hidden="bubbleErrorModalHidden">
        <p v-dompurify-html="approval_table_message"></p>
        <p v-translate>
            You can't upload a new version of this file until the approval table is closed.
        </p>
        <p v-translate="{ table_state }">
            Current approval table state: %{ table_state }.
        </p>
    </error-modal>
</template>

<script>
import ErrorModal from "./ErrorModal.vue";
import { mapState } from "vuex";
export default {
    components: { ErrorModal },
    props: {
        reasons: Array,
    },
    computed: {
        ...mapState(["project_id"]),
        table_owner() {
            return this.reasons[0].approval_table_owner;
        },
        table_state() {
            return this.reasons[0].approval_table_state;
        },
        approval_table_url() {
            return (
                "/plugins/docman/?group_id=" +
                this.project_id +
                "&id=" +
                this.reasons[0].item_id +
                "&action=details&section=approval"
            );
        },
        approval_table_message() {
            let translated = this.$gettext(
                `<a href="%{ table_owner_url }">%{ table_owner_name }</a> has created an <a href="%{ approval_table_url }">approval table</a> for the last version of %{ filename }.`
            );
            return this.$gettextInterpolate(translated, {
                table_owner_url: this.table_owner.user_url,
                table_owner_name: this.table_owner.display_name,
                approval_table_url: this.approval_table_url,
                filename: this.reasons[0].filename,
            });
        },
    },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        },
    },
};
</script>
