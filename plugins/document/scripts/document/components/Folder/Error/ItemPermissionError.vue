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
    <div class="empty-page document-folder-with-error">
        <div class="empty-page-illustration">
            <item-permission-error-svg />
        </div>
        <div class="empty-page-text-with-small-text">
            <translate>You don't have read permission for this item.</translate>
            <div class="empty-page-small-text" v-translate>
                You may only access documents you are granted read permission on.
            </div>
        </div>
        <form
            v-bind:action="`/plugins/document/PermissionDeniedRequestMessage/${project_id}`"
            method="post"
            name="display_form"
            ref="form"
        >
            <input type="hidden" v-bind:name="csrf_token_name" v-bind:value="csrf_token" />
            <div class="tlp-form-element">
                <label class="tlp-label" for="msg_private_project">
                    <translate>
                        Write your message below and click on the button to send your request to the
                        project administrators
                    </translate>
                    <i class="fa fa-asterisk"></i>
                </label>
                <textarea
                    rows="5"
                    cols="70"
                    id="msg_private_project"
                    name="msg_private_project"
                    v-bind:placeholder="placeholder"
                    v-model="mail_content"
                    required
                ></textarea>
                <input type="hidden" name="groupId" v-bind:value="project_id" />
                <p v-if="error !== ''" v-translate class="tlp-text-danger">
                    Please enter a reason for why you need to access this document.
                </p>
            </div>
            <button type="button" class="tlp-button-primary" v-on:click="submit">
                <i class="fa fa-envelope-o tlp-button-icon"></i>
                <translate>Send mail</translate>
            </button>
        </form>
    </div>
</template>

<script>
import { mapState } from "vuex";
import ItemPermissionErrorSvg from "../../svg/error/ItemPermissionErrorSvg.vue";

export default {
    name: "ItemPermissionError",
    components: { ItemPermissionErrorSvg },
    props: {
        csrf_token: {
            type: String,
            required: true,
        },
        csrf_token_name: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            error: "",
            mail_content: "",
        };
    },
    computed: {
        ...mapState(["project_id"]),
        placeholder() {
            return this.$gettext("Please write something meaningful for the admin.");
        },
    },
    methods: {
        submit() {
            if (!this.mail_content) {
                this.error = this.$gettext("Mail content is required");
                return;
            }

            this.$refs.form.submit();
        },
    },
};
</script>
