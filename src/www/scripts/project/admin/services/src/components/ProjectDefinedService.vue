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
    <div class="tlp-modal-body">
        <input type="hidden" name="service_id" v-bind:value="service.id">

        <div class="project-admin-services-custom-edit-modal-top-fields">
            <div class="tlp-form-element">
                <label class="tlp-label" for="project-admin-services-custom-edit-modal-label">
                    <translate>Label</translate>
                    <i class="fa fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="project-admin-services-custom-edit-modal-label"
                    name="label"
                    v-bind:placeholder="label_placeholder"
                    maxlength="40"
                    required
                    v-model="service.label"
                >
            </div>
            <div class="tlp-form-element">
                <label
                    class="tlp-label"
                    for="project-admin-services-custom-edit-modal-enabled"
                    v-translate
                >Enabled</label>
                <div class="tlp-switch">
                    <input
                        class="tlp-switch-checkbox"
                        id="project-admin-services-custom-edit-modal-enabled"
                        type="checkbox"
                        name="is_used"
                        value="1"
                        v-bind:checked="service.is_used"
                        data-test="service-is-used"
                    >
                    <label
                        class="tlp-switch-button"
                        for="project-admin-services-custom-edit-modal-enabled"
                        aria-hidden
                    ></label>
                </div>
            </div>
        </div>

        <div class="project-admin-services-custom-edit-modal-top-fields">
            <icon-selector v-model="service.icon_name" v-bind:allowed_icons="allowed_icons"/>

            <slot name="is_active">
                <input type="hidden" name="is_active" v-bind:value="is_active_string">
            </slot>
        </div>

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-custom-edit-modal-rank">
                <translate>Rank</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                type="number"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-rank"
                name="rank"
                placeholder="150"
                size="5"
                maxlength="5"
                v-bind:min="minimal_rank"
                required
                v-model="service.rank"
            >
        </div>

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-custom-edit-modal-link">
                <translate>Link</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                type="text"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-link"
                name="link"
                placeholder="https://example.com/my-service/"
                maxlength="255"
                pattern="(https?://|#|/|\?).+"
                v-bind:title="link_title"
                required
                v-model="service.link"
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

        <div class="tlp-form-element">
            <label
                class="tlp-label"
                for="project-admin-services-custom-edit-modal-description"
                v-translate
            >
                Description
            </label>
            <input
                type="text"
                class="tlp-input"
                id="project-admin-services-custom-edit-modal-description"
                name="description"
                v-bind:placeholder="description_placeholder"
                size="70"
                maxlength="255"
                v-model="service.description"
            >
        </div>

        <div class="tlp-form-element">
            <label
                class="tlp-label"
                for="project-admin-services-custom-edit-modal-iframe"
                v-translate
            >Display in iframe</label>
            <div class="tlp-switch">
                <input
                    class="tlp-switch-checkbox"
                    id="project-admin-services-custom-edit-modal-iframe"
                    type="checkbox"
                    name="is_in_iframe"
                    value="1"
                    v-bind:checked="service.is_in_iframe"
                >
                <label
                    class="tlp-switch-button"
                    for="project-admin-services-custom-edit-modal-iframe"
                    aria-hidden
                ></label>
            </div>
        </div>
    </div>
</template>
<script>
import IconSelector from "./IconSelector.vue";

export default {
    name: "ProjectDefinedService",
    components: { IconSelector },
    props: {
        minimal_rank: {
            type: String,
            required: true
        },
        service: {
            type: Object,
            required: true
        },
        allowed_icons: {
            type: Object,
            required: true
        }
    },
    computed: {
        label_placeholder() {
            return this.$gettext("My service");
        },
        link_title() {
            return this.$gettext("Please, enter a http:// or https:// link");
        },
        description_placeholder() {
            return this.$gettext("Awesome service to manage extra stuff");
        },
        is_active_string() {
            return this.service.is_active ? "1" : "0";
        }
    }
};
</script>
