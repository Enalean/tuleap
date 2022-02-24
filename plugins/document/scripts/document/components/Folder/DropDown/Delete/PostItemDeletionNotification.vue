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

<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import { State } from "vuex-class";

@Component
export default class PostItemDeletionNotification extends Vue {
    private is_displayed = false;
    private is_fadeout = false;

    @State
    private readonly show_post_deletion_notification!: boolean;

    @Watch("show_post_deletion_notification")
    public updateValue(value: string): void {
        if (value) {
            this.show();
        }
    }

    show(): void {
        this.is_displayed = true;
        this.is_fadeout = false;
        setTimeout(() => {
            this.is_fadeout = true;
        }, 2000);
        setTimeout(() => {
            this.is_displayed = false;
            this.$store.commit("hidePostDeletionNotification");
        }, 3000);
    }
}
</script>
