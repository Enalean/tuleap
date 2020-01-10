<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="services-whats-inside">
        <div
            class="services-whats-inside-link"
            v-if="!is_loading"
            v-on:click="show"
            data-test="project-service-link"
            tabindex="0"
            role="button"
        >
            <i class="fa fa-fw fa-long-arrow-right" />
            <translate>What's inside?</translate>
        </div>

        <i
            v-if="is_loading && !has_error"
            class="fa fa-spinner fa-circle-o-notch"
            data-test="project-service-spinner"
        />

        <div class="tlp-text-danger" v-if="has_error" data-test="project-service-error" v-translate>
            Oh snap! Impossible to load project services.
        </div>

        <div
            v-bind:id="`modal-services-used-${project.id}`"
            ref="modal"
            class="tlp-modal"
            role="dialog"
            aria-labelledby="modal-services-used-label"
            data-test="project-service-modal"
        >
            <div class="tlp-modal-header">
                <h1
                    class="tlp-modal-title"
                    v-bind:id="`modal-services-used-${project.id}`"
                    v-translate
                >
                    What's inside?
                </h1>
                <div
                    class="tlp-modal-close"
                    data-dismiss="modal"
                    v-bind:aria-label="$gettext('Close')"
                >
                    &times;
                </div>
            </div>
            <div class="tlp-modal-body">
                <h2 class="tlp-modal-subtitle" key="project_service_label" v-translate>
                    Services used in %{project.title}
                </h2>
                <ul class="project-service-list">
                    <li
                        v-for="service of services"
                        v-bind:key="service.id"
                        v-bind:data-test="`project-modal-services-list${service.id}`"
                    >
                        <i v-bind:class="`fa fa-fw service-modal-icon ${service.icon}`" />
                        {{ service.label }}
                    </li>
                </ul>
            </div>
            <div class="tlp-modal-footer">
                <button
                    type="button"
                    class="tlp-button-primary tlp-modal-action"
                    data-dismiss="modal"
                    v-translate
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { modal as createModal, Modal } from "tlp";
import { TemplateData, ServiceData } from "../../../type";
import { getServices } from "../../../api/rest-querier";

@Component
export default class ProjectServices extends Vue {
    @Prop()
    readonly project!: TemplateData;

    modal: Modal | null = null;
    is_loading = false;
    services: ServiceData[] | null = null;
    has_error = false;

    mounted(): void {
        const element = this.$refs.modal;
        if (element instanceof Element) {
            this.modal = createModal(element);
        }
    }

    async show(): Promise<void> {
        if (!this.modal) {
            return;
        }

        this.is_loading = true;

        try {
            this.services = await getServices(this.project.id);
        } catch (Exception) {
            this.has_error = true;
            throw Exception;
        }

        this.modal.show();

        this.is_loading = false;
    }
}
</script>
