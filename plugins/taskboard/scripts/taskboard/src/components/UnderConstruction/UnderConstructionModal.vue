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
  -
  -->

<template>
    <div class="tlp-modal tlp-modal-warning" role="dialog" aria-labelledby="taskboard-under-construction-modal-title">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="taskboard-under-construction-modal-title">
                <i class="fa fa-warning tlp-modal-title-icon"></i>
                <translate>This feature is under construction</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" v-bind:title="close" role="button">
                ×
            </div>
        </div>
        <div class="tlp-modal-body">
            <translate tag="p">Hello,</translate>
            <translate tag="p">Thanks for your interest in Tuleap next features. You're about to test a new feature that is under construction. All the buttons, links, menus, … might not work. Things may also be broken on old browsers like IE11.</translate>
            <p v-dompurify-html="leave_feedback"></p>
        </div>
        <div class="tlp-modal-footer">
            <button type="button" class="tlp-button-warning tlp-modal-action" data-dismiss="modal">
                <i class="fa fa-thumbs-up tlp-button-icon"></i>
                <translate>I understand this is under construction</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { modal as createModal } from "tlp";
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class UnderConstructionModal extends Vue {
    mounted(): void {
        const modal = createModal(this.$el);
        modal.show();
    }

    get close(): string {
        return this.$gettext("Close");
    }

    get leave_feedback(): string {
        return this.$gettext(
            'If you have any questions or if you want to leave feedback, drop an email to <a href="mailto:feedback-taskboard@enalean.com">feedback-taskboard@enalean.com</a> or join us on <a href="https://chat.tuleap.org">chat.tuleap.org</a>.'
        );
    }
}
</script>
