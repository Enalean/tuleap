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
    <form action="#"
          method="post"
          id="create-repository-modal"
          class="tlp-modal"
          role="dialog"
    >
        <div role="dialog" aria-labelledby="create-repository-modal-title">
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title" id="create-repository-modal-title">
                    <i class="fa fa-plus tlp-modal-title-icon"></i>
                    <translate>Add repository</translate>
                </h1>
                <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">
                    &times;
                </div>
            </div>
            <div class="tlp-modal-body git-repository-create-modal-body">
                <div class="tlp-form-element">
                    <label class="tlp-label" for="repository_name">
                        <translate>Repository name</translate>
                        <i class="fa fa-asterisk"></i></label>
                    <input
                            type="text"
                            class="tlp-input"
                            id="repository_name"
                            name="repository_name"
                            v-bind:placeholder="placeholder"
                            pattern="a-zA-Z0-9/_.-"
                            maxlength="255"
                            v-bind:title="repositoryPattern"
                    >
                    <p class="tlp-text-info">
                        <i class="fa fa-info-circle"></i> {{ repositoryPattern }}
                        {{ repositoryMaxLenght }}
                        {{ repositoryRules }}
                    </p>
                </div>
            </div>
            <div class="tlp-modal-footer">
                <button type="reset" class="tlp-button-primary tlp-button-outline tlp-modal-action"
                        data-dismiss="modal" v-translate>
                    Cancel
                </button>
                <button type="button" class="tlp-button-primary tlp-modal-action" disabled>
                    <i class="fa fa-plus tlp-button-icon"></i>
                    <translate>Add repository</translate>
                </button>
            </div>
        </div>
    </form>
</template>
<script>
    export default {
        name: 'GitRepositoryCreate',
        computed: {
            placeholder() {
                return this.$gettext("Repository name");
            },
            repositoryPattern() {
                return this.$gettext("Allowed characters: a-zA-Z0-9/_.-");
            },
            repositoryMaxLenght() {
                return this.$gettext("max length is 255");
            },
            repositoryRules() {
                return this.$gettext("no slashes at the beginning or the end, it also must not finish with \".git");
            }

        }
    }
</script>