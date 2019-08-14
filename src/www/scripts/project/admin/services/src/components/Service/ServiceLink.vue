<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element" v-bind:class="{'tlp-form-element-disabled': disabled }">
        <label class="tlp-label" for="project-admin-services-edit-modal-link">
            <translate>Link</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            id="project-admin-services-edit-modal-link"
            name="link"
            placeholder="https://example.com/my-service/"
            maxlength="255"
            pattern="(https?://|#|/|\?).+"
            v-bind:title="link_title"
            required
            v-bind:disabled="disabled"
            v-bind:value="value"
        >
        <p class="tlp-text-info">
            <i class="fa fa-info-circle"></i>
            <translate>A few keywords can be inserted into the link, they will be automatically replaced by their value:</translate>
        </p>
        <ul class="tlp-text-info">
            <li v-translate>$projectname: short name of the project</li>
            <li v-translate>$sys_default_domain: domain of your Tuleap server (e.g. “tuleap.example.com”)</li>
            <li v-translate>$group_id: project number</li>
            <li v-translate>$sys_default_protocol: ‘https’ if your server is configured in secure mode, ‘http’ otherwise</li>
        </ul>
    </div>
</template>
<script>
export default {
    name: "ServiceLink",
    props: {
        value: {
            type: String,
            required: true
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        link_title() {
            return this.$gettext("Please, enter a http:// or https:// link");
        }
    }
};
</script>
