/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

var tuleap = tuleap || { };
tuleap.tracker = tuleap.tracker || { };

(function ($) {
    tuleap.tracker.artifactEditInPlace = {

        init: function() {
            var self = this;

            if (tuleap.browserCompatibility.isIE7()) {
                return;
            }

            $('a.backlog-item-link').each( function() {
                $(this).off().on('click', function(event) {
                    event.preventDefault();

                    var artifact_id = $(this).attr('data-artifact-id');
                    self.loadArtifactModal(artifact_id);
                });
            });
        },

        defaultCallback : function() {
            window.location.reload();
        },

        loadArtifactModal : function(artifact_id, update_callback) {
            var self = this;

            if (typeof(update_callback) == 'undefined') {
                update_callback = this.defaultCallback;
            }

            $.ajax({
                url: codendi.tracker.base_url + '?aid='+artifact_id+'&func=get-edit-in-place',
                beforeSend: tuleap.modal.showLoad

            }).done(function(data) {
                tuleap.modal.hideLoad();
                self.showArtifactEditForm(data, artifact_id, update_callback)
                codendi.tracker.runTrackerFieldDependencies();

                $('.tuleap-modal-main-panel form textarea').each( function(){
                    var element = $(this).get(0); //transform to prototype
                    enableRichTextArea(element)
                });
            });

            function enableRichTextArea(element) {
                var html_id    = element.id,
                    id         = html_id.match(/_(\d+)$/),
                    htmlFormat = false,
                    name;

                if (id) {
                    id   = id[1];
                    name = 'artifact['+ id +'][format]';

                    if (Element.readAttribute('artifact['+id+']_body_format', 'value') == 'html') {
                        htmlFormat = true;
                    }

                    new tuleap.trackers.textarea.RTE(
                        element,
                        {toggle: true, default_in_html: false, id: id, name: name, htmlFormat: htmlFormat, no_resize : true}
                    );
                }
            }
        },

        showArtifactEditForm : function(form_html, artifact_id, callback) {
            var self = this,
                modal;

            function beforeSubmit() {
                $('#tuleap-modal-submit')
                    .val($('#tuleap-modal-submit').attr('data-loading-text'))
                    .attr("disabled", true);
            }

            function afterSubmit() {
                $('#tuleap-modal-submit')
                    .val($('#tuleap-modal-submit').attr('data-normal-text'))
                    .attr("disabled", false);
            }

            $('body').append(form_html);

            modal = tuleap.modal.init({
                beforeClose : function() {
                    self.destroyRichTextAreaInstances();
                    $('.artifact-event-popup').remove();
                }
            });

            $('#tuleap-modal-submit').click(function(event) {
                self.updateRichTextAreas();

                if (! self.isArtifactSubmittable(event)) {
                    return;
                }

                $('#artifact-form-errors').hide();

                $.ajax({
                    url       : '/plugins/tracker/?aid='+artifact_id+'&func=update-in-place',
                    type      : 'post',
                    data      : $('.tuleap-modal-main-panel form').serialize(),
                    beforeSend: beforeSubmit

                }).done( function() {
                    self.destroyRichTextAreaInstances();
                    modal.closeModal();
                    callback();

                }).fail( function(response) {
                    var data = JSON.parse(response.responseText);

                    afterSubmit();

                    $('#artifact-form-errors h5').html(data.message);
                    $.each(data.errors, function() {
                      $('#artifact-form-errors ul').html('').append('<li>' + this + '</li>');
                    });

                    $('.tuleap-modal-main-panel .tuleap-modal-content').scrollTop(0);
                    $('#artifact-form-errors').show();
                });

                return false;
            });
        },

        isArtifactSubmittable : function(event) {
            return tuleap.trackers.submissionKeeper.isArtifactSubmittable(event);
        },

        updateRichTextAreas : function() {
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
        },

        destroyRichTextAreaInstances : function() {
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].destroy();
            }
        }
    };

    $(document).ready(function () {
        tuleap.tracker.artifactEditInPlace.init();
    });
})(jQuery);
