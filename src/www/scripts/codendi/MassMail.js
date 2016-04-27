/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

/**
 * Scripts of the mass mail engine
 * Manages sending a preview
 */
var MassMail = Class.create({
    initialize: function (editor) {
        // Must use Event.observe(toggle... instead of toggle.observe(...
        // Otherwise IE cannot manage it. Oo
        Event.observe($('preview_submit'), 'click', this.sendPreview.bindAsEventListener(this));
        Event.observe($('preview_destination'), 'keypress', this.disableEnterKey.bindAsEventListener(this));
        Event.observe($('massmail_form'), 'submit', this.confirmSubmitMassMail.bindAsEventListener(this));
        this.editor = editor;
    },
    //Disable massmail_form submission when enter key is pressed, the preview is sent instead .
    disableEnterKey: function(event) {
        if (Event.KEY_RETURN == event.keyCode) {
            // Sending the preview using enter key causes bad surprises when combined with autocomplete
            //this.sendPreview(event);
            event.stop();
            return false;
        }
    },
    sendPreview: function(event) {
        var mailSubject = encodeURIComponent($('mail_subject').value);
        previewDestination = encodeURIComponent($('preview_destination').value);
        if (previewDestination != '') {
             $('comment_format_text', 'comment_format_html').each(function(node){
                if (node.selected) {
                    bodyFormat = encodeURIComponent(node.value);
                }
            });
            if (this.editor.isInstantiated()) {
                $('mail_message').value = this.editor.getContent();
            }
            var mailMessage = encodeURIComponent($('mail_message').value);
            var formParameters = 'destination=preview&mail_subject='+mailSubject+'&comment_format='+bodyFormat+'&mail_message='+mailMessage+'&preview_destination='+previewDestination+'&Submit=Submit';
            var spinner = Builder.node('img', {'src'    : '/themes/common/images/ic/spinner.gif',
                                               'border' : '0'});
            //we request the preview here, massmail_execute will process the whole stuff
            $('massmail_form').request({
                method: 'post',
                parameters: formParameters,
                onCreate: function() {
                    $('preview_result').appendChild(spinner);
                },
                onSuccess: function(response){
                    var span = Builder.node('span', {'style' : 'color:red'});
                    span.appendChild(document.createTextNode(response.responseText));
                    while ($('preview_result').hasChildNodes()) {
                        $('preview_result').removeChild($('preview_result').lastChild);
                    }
                    $('preview_result').appendChild(span);
                }
            });
            event.stop();
            return false;
        }
    },
    confirmSubmitMassMail: function(event) {
        users = false;
        $$('*[name^="destination"]').each(function(node) {
            if (node.checked) {
                users = node.up('label').readAttribute('name');
            }
        });
        if (!users) {
            alert("No destination");
            event.stop();
            return false;
        }
        if (!confirm("You are about to send to " + users + " people, do you confirm ?")) {
            event.stop();
        }
        return false;
    }
});