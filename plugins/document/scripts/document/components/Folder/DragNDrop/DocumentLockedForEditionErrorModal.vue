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
        <p v-dompurify-html="lock_message"></p>
    </error-modal>
</template>

<script>
import ErrorModal from "./ErrorModal.vue";
export default {
    components: { ErrorModal },
    props: {
        reasons: Array,
    },
    computed: {
        lock_owner() {
            return this.reasons[0].lock_owner;
        },
        lock_message() {
            let translated = this
                .$gettext(`%{ filename } has been locked for edition by <a href="%{ lock_owner_url }">%{ lock_owner_name }</a>.
                You can't upload a new version of this file until the lock is released.`);
            return this.$gettextInterpolate(translated, {
                filename: this.reasons[0].filename,
                lock_owner_url: this.lock_owner.user_url,
                lock_owner_name: this.lock_owner.display_name,
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
