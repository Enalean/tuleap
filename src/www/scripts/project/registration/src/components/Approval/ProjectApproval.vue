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
    <div class="empty-page">
        <div class="empty-page-illustration">
            <project-approval-svg />
        </div>

        <div class="empty-page-text-with-small-text">
            <span v-translate>
                Your project has been submitted to the administrators for validation
            </span>
            <div class="empty-page-small-text" v-dompurify-html="message_admin_validation"></div>
        </div>

        <div>
            <a class="tlp-button-primary tlp-button-large" href="/my/">
                <i class="fa fa-reply tlp-button-icon"></i>
                <span v-translate>Go to my home page</span>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import ProjectApprovalSvg from "./ProjectApprovalSvg.vue";
import { Getter } from "vuex-class";
@Component({
    components: { ProjectApprovalSvg },
})
export default class ProjectApproval extends Vue {
    @Getter
    is_template_selected!: boolean;

    mounted(): void {
        if (!this.is_template_selected) {
            this.$router.push("new");
        }
    }

    get message_admin_validation(): string {
        return this.$gettext(
            "<b>You will receive an email</b> when the administrator has validated it."
        );
    }
}
</script>
