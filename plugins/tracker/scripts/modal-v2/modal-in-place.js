/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import jQuery from "jquery";
import CKEDITOR from "ckeditor4";
import codendi from "codendi";
import { initMentions } from "@tuleap/mention";
import { loadTooltips } from "@tuleap/tooltip";

import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { RichTextEditorsCreator } from "../artifact/rich-text-editor-creator/RichTextEditorsCreator";

var tuleap = window.tuleap || {};
tuleap.tracker = tuleap.tracker || {};
tuleap.textarea = tuleap.textarea || {};

(function ($) {
    tuleap.tracker.artifactModalInPlace = {
        init: function () {
            var self = this;

            $("a.backlog-item-link").each(function () {
                $(this)
                    .off()
                    .on("click", function (event) {
                        event.preventDefault();

                        var artifact_id = $(this).attr("data-artifact-id");
                        self.loadEditArtifactModal(artifact_id);
                    });
            });

            $("a.create-item-link").each(function () {
                $(this)
                    .off()
                    .on("click", function (event) {
                        event.preventDefault();

                        var tracker_id = $(this).attr("data-tracker-id");
                        var artifact_link_id = $(this).attr("data-link-id");
                        self.loadCreateArtifactModal(tracker_id, artifact_link_id);
                    });
            });
        },

        defaultCallback: function () {
            window.location.reload();
        },

        enableRichTextArea: function () {
            let locale = "en_US";
            if (document.body.dataset.userLocale) {
                locale = document.body.dataset.userLocale;
            }
            const editor_creator = new RichTextEditorsCreator(
                document,
                new UploadImageFormFactory(document, locale),
                RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
            );
            editor_creator.createTextFieldEditors();
        },

        displayAutocomputed: function (element) {
            element.find(".tracker_formelement_label").hide();
            element.find(".tracker_formelement_edit").show();
            element.find(".auto-computed").show();
            element.find(".add-field").hide();
            element.removeClass("in-edition");
        },

        displayInEdition: function (element) {
            element.find(".tracker_formelement_edit").hide();
            element.find(".tracker_formelement_label").show();
            element.find(".auto-computed").hide();
            element.find(".add-field").show();
            element.addClass("in-edition");
        },

        switchValueToManualMode: function (field_id) {
            var field_computed_is_autocomputed = document.getElementsByName(
                "artifact[" + field_id + "][is_autocomputed]",
            );
            if (
                field_computed_is_autocomputed[0] !== undefined &&
                field_computed_is_autocomputed[0] !== undefined
            ) {
                field_computed_is_autocomputed[0].value = "0";
            }
        },

        switchValueToAutoComputedMode: function (field_id) {
            var field_computed_manual_value = document.getElementsByName(
                "artifact[" + field_id + "][manual_value]",
            );
            var field_computed_is_autocomputed = document.getElementsByName(
                "artifact[" + field_id + "][is_autocomputed]",
            );
            if (
                field_computed_manual_value[0] !== undefined &&
                field_computed_is_autocomputed[0] !== undefined
            ) {
                field_computed_manual_value[0].value = null;
                field_computed_is_autocomputed[0].value = "1";
            }
        },

        loadCreateArtifactModal: function (tracker_id, artifact_link_id, callback) {
            var self = this;

            if (typeof callback === "undefined") {
                callback = this.defaultCallback;
            }

            $.ajax({
                url:
                    codendi.tracker.base_url +
                    "?tracker=" +
                    tracker_id +
                    "&artifact-link-id=" +
                    artifact_link_id +
                    "&func=get-create-in-place",
                beforeSend: tuleap.modal.showLoad,
            })
                .done(function (data) {
                    tuleap.modal.hideLoad();
                    self.showArtifactCreationForm(data, tracker_id, artifact_link_id, callback);
                    tuleap.tracker.runTrackerFieldDependencies();

                    self.enableRichTextArea();

                    $(".tracker_artifact_field-computed").each(function () {
                        var $element = $(this);
                        var $field_id = $(this).find(".add-field").data("field-id");

                        if ($element.hasClass("with-default-value")) {
                            self.displayInEdition($element);
                        } else {
                            self.displayAutocomputed($element);
                        }

                        $element.find(".tracker_formelement_edit").on("click", function () {
                            self.displayInEdition($element);
                            $element.off("click");
                            self.switchValueToManualMode($field_id);
                        });

                        $element.find(".auto-compute").on("click", function () {
                            self.displayAutocomputed($element);
                            self.switchValueToAutoComputedMode($field_id);
                        });
                    });
                })
                .fail(function () {
                    tuleap.modal.hideLoad();
                    codendi.feedback.log("error", codendi.locales.tracker_modal_errors.bad_request);
                });
        },

        loadEditArtifactModal: function (artifact_id, update_callback, load_callback, data) {
            var self = this;

            if (typeof update_callback === "undefined") {
                update_callback = this.defaultCallback;
            }

            $.ajax({
                url: codendi.tracker.base_url + "?aid=" + artifact_id + "&func=get-edit-in-place",
                beforeSend: tuleap.modal.showLoad,
                data: data,
                method: "POST",
            })
                .done(function (data) {
                    tuleap.modal.hideLoad();
                    self.showArtifactEditForm(data, artifact_id, update_callback);
                    tuleap.tracker.runTrackerFieldDependencies();

                    var modalLoadedEvent = new Event("EditModalLoaded");
                    document.dispatchEvent(modalLoadedEvent);

                    self.enableRichTextArea();

                    if (typeof load_callback !== "undefined") {
                        load_callback();
                    }

                    $(".tracker_artifact_field").each(function () {
                        var $element = $(this);
                        var $field_id = $(this).find(".add-field").data("field-id");
                        var field_computed_manual_value = document.getElementsByName(
                            "artifact[" + $field_id + "][manual_value]",
                        );
                        var field_computed_is_autocomputed = document.getElementsByName(
                            "artifact[" + $field_id + "][is_autocomputed]",
                        );

                        if (
                            field_computed_manual_value[0] !== undefined &&
                            field_computed_is_autocomputed[0] !== undefined
                        ) {
                            if (field_computed_manual_value[0].value) {
                                self.displayInEdition($element);
                            } else {
                                self.displayAutocomputed($element);
                            }
                        }

                        $element.find(".tracker_formelement_edit").on("click", function () {
                            self.displayInEdition($element);
                            $element.off("click");
                            self.switchValueToManualMode($field_id);
                        });

                        $element.find(".auto-compute").on("click", function () {
                            self.displayAutocomputed($element);
                            self.switchValueToAutoComputedMode($field_id);
                        });
                    });
                })
                .fail(function () {
                    tuleap.modal.hideLoad();
                    codendi.feedback.log("error", codendi.locales.tracker_modal_errors.bad_request);
                });
        },

        initModalInteraction: function (modal) {
            const tuleap_modal = modal.getDOMElement();
            loadTooltips(tuleap_modal, true);
            codendi.Toggler.init(tuleap_modal);
            tuleap.dateTimePicker.init();
            codendi.tracker.textboxlist.init();
            initMentions(
                '.tuleap-modal input[type="text"].user-mention, .tuleap-modal textarea.user-mention',
            );
        },

        beforeSubmit: function () {
            $("#tuleap-modal-submit")
                .val($("#tuleap-modal-submit").attr("data-loading-text"))
                .attr("disabled", true);
        },

        afterSubmit: function () {
            $("#tuleap-modal-submit")
                .val($("#tuleap-modal-submit").attr("data-normal-text"))
                .attr("disabled", false);
        },

        submitDone: function (modal, callback) {
            this.destroyRichTextAreaInstances();
            modal.closeModal();
            if (callback == this.defaultCallback) {
                modal.showLoad();
            }
            callback();
        },

        showArtifactCreationForm: function (form_html, tracker_id, artifact_link_id, callback) {
            var self = this,
                modal;

            $("body").append(form_html);

            modal = tuleap.modal.init({
                beforeClose: function () {
                    self.destroyRichTextAreaInstances();
                    $(".artifact-event-popup").remove();
                },
            });

            self.initModalInteraction(modal);

            $("#tuleap-modal-submit").click(function () {
                self.updateRichTextAreas();
                $("#artifact-form-errors").hide();

                $.ajax({
                    url:
                        "/plugins/tracker/?tracker=" +
                        tracker_id +
                        "&artifact-link-id=" +
                        artifact_link_id +
                        "&func=submit-artifact-in-place",
                    type: "post",
                    data: $(".tuleap-modal-main-panel form").serialize(),
                    beforeSend: self.beforeSubmit,
                })
                    .done(function () {
                        self.submitDone(modal, callback);
                    })
                    .fail(function (response) {
                        var data = JSON.parse(response.responseText);

                        self.afterSubmit();

                        $("#artifact-form-errors h5").html(data.message);
                        $.each(data.errors, function () {
                            $("#artifact-form-errors ul")
                                .html("")
                                .append("<li>" + this + "</li>");
                        });

                        $(".tuleap-modal-main-panel .tuleap-modal-content").scrollTop(0);
                        $("#artifact-form-errors").show();
                    });

                return false;
            });
        },

        showArtifactEditForm: function (form_html, artifact_id, callback) {
            var self = this,
                modal;

            $("body").append(form_html);

            modal = tuleap.modal.init({
                beforeClose: function () {
                    self.destroyRichTextAreaInstances();
                    $(".artifact-event-popup").remove();
                },
            });

            self.initModalInteraction(modal);

            $("#tuleap-modal-submit").click(function (event) {
                self.updateRichTextAreas();

                if (!self.isArtifactSubmittable(event)) {
                    return;
                }

                $("#artifact-form-errors").hide();

                $.ajax({
                    url: "/plugins/tracker/?aid=" + artifact_id + "&func=update-in-place",
                    type: "post",
                    data: $(".tuleap-modal-main-panel form").serialize(),
                    beforeSend: self.beforeSubmit,
                })
                    .done(function () {
                        self.submitDone(modal, callback);
                    })
                    .fail(function (response) {
                        self.showSubmitFailFeedback(response.responseText);
                    });

                return false;
            });
        },

        isArtifactSubmittable: function (event) {
            return tuleap.trackers.submissionKeeper.isArtifactSubmittable(event);
        },

        updateRichTextAreas: function () {
            for (let instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
        },

        destroyRichTextAreaInstances: function () {
            for (let instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].destroy();
            }
        },

        showSubmitFailFeedback: function (responseText) {
            var data = JSON.parse(responseText);

            this.afterSubmit();

            $("#artifact-form-errors h5").html(data.message);

            if (data.errors) {
                $.each(data.errors, function () {
                    $("#artifact-form-errors ul")
                        .html("")
                        .append("<li>" + this + "</li>");
                });
            }

            $(".tuleap-modal-main-panel .tuleap-modal-content").scrollTop(0);
            $("#artifact-form-errors").show();
        },
    };

    $(document).ready(function () {
        tuleap.tracker.artifactModalInPlace.init();
    });
})(jQuery);
