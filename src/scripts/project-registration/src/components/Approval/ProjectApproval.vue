<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="empty-state-page">
        <div class="empty-state-illustration">
            <project-approval-svg />
        </div>

        <h1 class="empty-state-title" v-translate>
            Your project has been submitted to the administrators for validation
        </h1>
        <p class="empty-state-text" v-dompurify-html="message_admin_validation"></p>

        <a class="tlp-button-primary tlp-button-large empty-state-action" href="/my/">
            <i class="fa fa-reply tlp-button-icon"></i>
            <span v-translate>Go to my home page</span>
        </a>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import ProjectApprovalSvg from "./ProjectApprovalSvg.vue";
import { useStore } from "../../stores/root";
@Component({
    components: { ProjectApprovalSvg },
})
export default class ProjectApproval extends Vue {
    root_store = useStore();

    mounted(): void {
        if (!this.root_store.is_template_selected) {
            this.$router.push("new");
        }
    }

    get message_admin_validation(): string {
        return this.$gettext("You will receive an email when the administrator has validated it.");
    }
}
</script>
