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
        <p v-translate>
            <a v-bind:href="table_owner.user_url">{{ table_owner.display_name }}</a> has created an <a v-bind:href="approval_table_url">approval table</a>  for the last version of {{ filename }}.
        </p>
        <p v-translate>
            You can't upload a new version of this file until the approval table is closed.
        </p>
        <p v-translate>
            Current approval table state: {{ table_state }}.
        </p>
    </error-modal>
</template>

<script>
import ErrorModal from "./ErrorModal.vue";
export default {
    components: { ErrorModal },
    props: {
        reasons: Array
    },
    computed: {
        filename() {
            return this.reasons[0].filename;
        },
        table_owner() {
            return this.reasons[0].approval_table_owner;
        },
        table_state() {
            return this.reasons[0].approval_table_state;
        },
        approval_table_url() {
            return this.reasons[0].approval_table_admin_url;
        }
    },
    methods: {
        bubbleErrorModalHidden() {
            this.$emit("error-modal-hidden");
        }
    }
};
</script>
