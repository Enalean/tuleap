/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
*
* Originally written by Nicolas Terray, 2008
*
* This file is a part of Codendi.
*
* Codendi is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Codendi is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };

codendi.tracker.artifact = { };

function invertFollowups(followupSection) {
    var element  = followupSection.down('.tracker_artifact_followups').cleanWhitespace();
    var elements = [];
    var len      = element.childNodes.length;
    for (var i = len - 1 ; i >= 0 ; --i) {
        elements.push(Element.remove(element.childNodes[i]));
    }
    for (var j = 0 ; j < len ; ++j) {
        element.appendChild(elements[j]);
    }
}

document.observe('dom:loaded', function () {
    $$('.tracker_statistics').each(function (div) {
        codendi.Tooltips.push(
            new codendi.Tooltip(
                div.up()
                   .previous()
                   .down('a.direct-link-to-tracker'), 
                '')
           .createTooltip(
               div.remove()
                  .setStyle(
                      { fontSize: '1.1em' }
                   )
           )
        );
    });
    
    $$('.tracker_artifact_followup_header').each(function (header) {
        if (header.up().next()) {
            header.observe('mouseover', function () {
                header.setStyle({ cursor: 'pointer' });
            });
            header.observe('click', function (evt) {
                if (Event.element(evt).hasClassName('tracker_artifact_followup_permalink')) {
                    header.nextSiblings().invoke('show');
                } else {
                    header.nextSiblings().invoke('toggle');
                }
            });
        }
    });
    
    $$('#tracker_artifact_followup_comments').each(function (followup_section) {
        //We only have one followup_section but I'm too lazy to do a if()

        new Ajax.Request(codendi.tracker.base_url + "comments_order.php", {
            parameters: {
                tracker_id: $('tracker_id').value
            },
            onSuccess: function (transport) {
                if (!transport.responseText) {
                    invertFollowups(followup_section);
                }
            }
        });
        var div = new Element('div').setStyle({
                    textAlign: 'right'
                }).insert(new Element('a', {
                    href: '#invert-order',
                    title: 'invert order of follow-up comments'
                }).update('<img src="' + codendi.imgroot + '/ic/reorder-followups.png" alt="invert order of follow-up comments" />')
                .observe('click', function (evt) {
                    invertFollowups(followup_section);
                    new Ajax.Request(codendi.tracker.base_url + "invert_comments_order.php", {
                        parameters: {
                            tracker_id: $('tracker_id').value
                        }
                    });
                    Event.stop(evt);
                    return false;
                }));
        if (followup_section.down('.tracker_artifact_followups').childElements().size() < 2) {
            div.hide();
        }
        followup_section.down('legend').insert({
            after: div
        });
    });

    $$('.tracker_artifact_field  textarea').each(function (element) {
        var id = element.id;
        new tuleap.trackers.followup.RTE(element, {toggle: true, default_in_html: false, id : id, htmlFormat: false});
    });

    $$('.tracker_artifact_followup_comment_controls_edit').each(function (edit) {
        var id = edit.up('.tracker_artifact_followup').id;
        if (id && id.match(/_\d+$/)) {
            id = id.match(/_(\d+)$/)[1];
            edit.observe('click', function (evt) {
                var comment_panel = edit.up().next();
                if (comment_panel.visible()) {
                    
                    var textarea = new Element('textarea', {id: 'tracker_followup_comment_edit_'+id});
                    if ($('tracker_artifact_followup_comment_body_format_'+id).value == 'html') {
                        textarea.value = comment_panel.down('.tracker_artifact_followup_comment_body').innerHTML;
                        var htmlFormat = true;
                    } else {
                        textarea.value = comment_panel.down('.tracker_artifact_followup_comment_body').innerHTML.stripTags();
                        var htmlFormat = false;
                    }
                    
                    var rteSpan    = new Element('span', { style: 'text-align: left;'}).update(textarea);
                    var edit_panel = new Element('div', { style: 'text-align: right;'}).update(rteSpan);
                    comment_panel.insert({before: edit_panel});
                    new tuleap.trackers.followup.RTE(textarea, {toggle: true, default_in_html: false, id: id, htmlFormat: htmlFormat});
                    while (textarea.offsetWidth < comment_panel.offsetWidth) {
                        textarea.cols++;
                    }
                    while (textarea.offsetHeight < textarea.scrollHeight) {
                        textarea.rows++;
                    }
                    comment_panel.hide();
                    textarea.focus();
                    var button = new Element('button').update('Ok').observe('click', function (evt) {
                        if (CKEDITOR.instances && CKEDITOR.instances['tracker_followup_comment_edit_'+id]) {
                            var content = CKEDITOR.instances['tracker_followup_comment_edit_'+id].getData();
                        } else {
                            var content = $('tracker_followup_comment_edit_'+id).getValue();
                        }
                        var format = document.getElementsByName('comment_format'+id)[0].selected? 'text' : 'html';
                        var req = new Ajax.Request(location.href, {
                            parameters: {
                                func:           'update-comment',
                                changeset_id:   id,
                                content:        content,
                                comment_format: format
                            },
                            onSuccess: function (transport) {
                                if (transport.responseText) {
                                    edit_panel.remove();
                                    comment_panel.update(transport.responseText).show();
                                    var e = new Effect.Highlight(comment_panel);
                                }
                            }
                        });
                        Event.stop(evt);
                        return false;
                    });
                    
                    var cancel = new Element('a', {
                        href: '#cancel'
                    }).update('Cancel').observe('click', function (evt) {
                        edit_panel.remove();
                        comment_panel.show();
                        Event.stop(evt);
                    });
                    
                    edit_panel.insert(new Element('br'))
                    .insert(button)
                    .insert(new Element('span').update('&nbsp;'))
                    .insert(cancel);
                }
                Event.stop(evt);
            });
        }
    });

    $$('.tracker_artifact_showdiff').each(function (link) {
        if (link.next()) {
            link.next().hide();
            link.observe('click', function (evt) {
                link.next().toggle();
                Event.stop(evt);
            });
        }
    });
    
    $$('.tracker_artifact_add_attachment').each(function (attachment) {
            var add = new Element('a', {
                href: '#add-another-file'
            }).update(codendi.locales.tracker_formelement_admin.add_another_file)
            .observe('click', function (evt) {
                Event.stop(evt);
                
                //clone the first attachment selector (file and description inputs)
                var new_attachment = $(attachment.cloneNode(true));
                
                //clear the cloned input
                new_attachment.select('input').each(function (input) {
                    input.value = '';
                });
                
                //Add the remove button
                new_attachment.down('tr', 1)
                .insert(
                    new Element('td')
                    .insert(
                        new Element('a', { href: '#remove-attachment' })
                        .addClassName('tracker_artifact_remove_attachment')
                        .update('<span>remove</span>')
                        .observe('click', function (evt) {
                            Event.stop(evt);
                            new_attachment.remove(); 
                        }
                    )
                ));
                //insert the new attachment selector
                add.insert({ before: new_attachment });
            });
            attachment.insert({ after: add });
        }
    );
    
    if ($('tracker_artifact_canned_response_sb')) {
        var artifact_followup_comment_has_changed = $('tracker_followup_comment_new').value !== '';
        $('tracker_followup_comment_new').observe('change', function () {
            artifact_followup_comment_has_changed = $('tracker_followup_comment_new').value !== '';
        });
        $('tracker_artifact_canned_response_sb').observe('change', function (evt) {
            var sb = Event.element(evt);
            var value = '';
            if (artifact_followup_comment_has_changed) {
                value += $('tracker_followup_comment_new').value;
                if (sb.getValue()) {
                    if (value.substring(value.length - 1) !== "\n") {
                        value += "\n\n";
                    } else if (value.substring(value.length - 2) !== "\n\n") {
                        value += "\n";
                    }
                }
            }
            value += sb.getValue();
            $('tracker_followup_comment_new').value = value;
            if (CKEDITOR.instances && CKEDITOR.instances['tracker_followup_comment_new']) {
                CKEDITOR.instances['tracker_followup_comment_new'].setData(CKEDITOR.instances['tracker_followup_comment_new'].getData()+'<p>'+value+'</p>');
            }
        });
    }
    
    if ($('tracker_select_tracker')) {
        $('tracker_select_tracker').observe('change', function () {
            this.ownerDocument.location.href = this.ownerDocument.location.href.gsub(/tracker=\d+/, 'tracker='+ this.value);
        });
    }

    function toggle_tracker_artifact_attachment_delete(elem) {
        if (elem.checked) {
            elem.up('td').nextSiblings().invoke('addClassName', 'tracker_artifact_attachment_deleted');
            elem.up('tr').select('.tracker_artifact_preview_attachment').invoke('setOpacity', 0.4);
        } else {
            elem.up('td').nextSiblings().invoke('removeClassName', 'tracker_artifact_attachment_deleted');
            elem.up('tr').select('.tracker_artifact_preview_attachment').invoke('setOpacity', 1);
        }
    }

    $$(".tracker_artifact_attachment_delete > input[type=checkbox]").each(function (elem) {
        //on load strike (useful when the checkbox is already checked on dom:loaded. (Missing required field for example)
        toggle_tracker_artifact_attachment_delete(elem);
        elem.observe('click', function (evt) { toggle_tracker_artifact_attachment_delete(elem); });
    });

    $$("p.artifact-submit-button > input").each(function (elem) {
        elem.observe('click', disableWarnOnPageLeave);
    });

    // We know it is crappy, but you know what?
    // IE/Chrome/Firefox don't behave the same way!
    // So if you have a better solution…

    window.onbeforeunload = warnOnPageLeave;

    function disableWarnOnPageLeave() {
        window.onbeforeunload = function(){};
    }

    function warnOnPageLeave() {
        if ( $('artifact-read-only-page') != null && $('tracker_followup_comment_new').value != '' ) {
            return codendi.locales.tracker_formelement_admin.lose_follows;
        }
    }

});

codendi.Tooltip.selectors.push('a[class=direct-link-to-artifact]');
