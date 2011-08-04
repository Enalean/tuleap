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
        followup_section.down('legend').insert({
            after: new Element('div').setStyle({
                    textAlign: 'right'
                }).insert(new Element('a', {
                    href: '#invert-order',
                    title: 'invert order of follow-up comments'
                }).update('<img src="' + codendi.imgroot + '/ic/reorder-followups.png" alt="invert order of follow-up comments" />')
                .observe('click', function (evt) {
                    var element = followup_section.down('.tracker_artifact_followups').cleanWhitespace();
                    var elements = [];
                    var len = element.childNodes.length;
                    for (var i = len - 1 ; i >= 0 ; --i) {
                        elements.push(Element.remove(element.childNodes[i]));
                    }
                    for (var j = 0 ; j < len ; ++j) {
                        element.appendChild(elements[j]);
                    }
                    Event.stop(evt);
                    return false;
                }))
        });
    });
    
    $$('.tracker_artifact_followup_comment_controls_edit').each(function (edit) {
        var id = edit.up('.tracker_artifact_followup').id;
        if (id && id.match(/_\d+$/)) {
            id = id.match(/_(\d+)$/)[1];
            edit.observe('click', function (evt) {
                var comment_panel = edit.up().next();
                if (comment_panel.visible()) {
                    
                    var textarea = new Element('textarea');
                    textarea.value = comment_panel.down('.tracker_artifact_followup_comment_body')
                                                  .innerHTML
                                                  .stripTags();
                    
                    var edit_panel = new Element('div', { style: 'text-align: right;'}).update(textarea);
                    comment_panel.insert({before: edit_panel});
                    while (textarea.offsetWidth < comment_panel.offsetWidth) {
                        textarea.cols++;
                    }
                    while (textarea.offsetHeight < textarea.scrollHeight) {
                        textarea.rows++;
                    }
                    comment_panel.hide();
                    textarea.focus();
                    var button = new Element('button').update('Ok').observe('click', function (evt) {
                        var req = new Ajax.Request(location.href, {
                            parameters: {
                                func:         'update-comment',
                                changeset_id: id,
                                content:      textarea.getValue()
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
        var artifact_followup_comment_has_changed = $('artifact_followup_comment').value !== '';
        $('artifact_followup_comment').observe('change', function () {
            artifact_followup_comment_has_changed = $('artifact_followup_comment').value !== '';
        });
        $('tracker_artifact_canned_response_sb').observe('change', function (evt) {
            var sb = Event.element(evt);
            var value = '';
            if (artifact_followup_comment_has_changed) {
                value += $('artifact_followup_comment').value;
                if (sb.getValue()) {
                    if (value.substring(value.length - 1) !== "\n") {
                        value += "\n\n";
                    } else if (value.substring(value.length - 2) !== "\n\n") {
                        value += "\n";
                    }
                }
            }
            value += sb.getValue();
            $('artifact_followup_comment').value = value;
        });
    }
    
    if ($('tracker_select_tracker')) {
        $('tracker_select_tracker').observe('change', function () {
            this.ownerDocument.location.href = this.ownerDocument.location.href.gsub(/tracker=\d+/, 'tracker='+ this.value);
        });
    }

});


