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
    <div
        class="document-notification tlp-alert-success"
        v-bind:class="{
            'document-notification-fadeout': is_fadeout,
        }"
        v-if="is_displayed"
    >
        <translate>The item has been deleted successfully.</translate>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    data() {
        return {
            is_displayed: false,
            is_fadeout: false,
            fadeout_timeout_id: null,
            hidden_timeout_id: null,
        };
    },
    computed: {
        ...mapState(["show_post_deletion_notification"]),
    },
    watch: {
        show_post_deletion_notification: function (value) {
            if (value) {
                this.show();
            }
        },
    },
    methods: {
        show() {
            if (this.is_displayed) {
                clearTimeout(this.fadeout_timeout_id);
                clearTimeout(this.hidden_timeout_id);
            }

            this.is_displayed = true;
            this.is_fadeout = false;
            this.fadeout_timeout_id = setTimeout(() => {
                this.is_fadeout = true;
            }, 2000);
            this.hidden_timeout_id = setTimeout(() => {
                this.is_displayed = false;
                this.$store.commit("hidePostDeletionNotification");
            }, 3000);
        },
    },
};
</script>
