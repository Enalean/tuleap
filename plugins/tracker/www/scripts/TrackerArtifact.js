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

        var comments_inverted = false;

        function toggleCheckForCommentOrder() {
            $('invert-order-menu-item').down('i').toggle();
        }
        function toggleCheckForDisplayChanges() {
            $('display-changes-menu-item').down('i').toggle();
        }

        var display_changes_classname = 'tracker_artifact_followup_comments-display_changes',
            div = new Element('div').setStyle({
                float: 'right'
            }).insert(
                new Element('div', {
                    'class': 'btn-group'
                }).insert(
                    new Element('a', {
                        'href': '#',
                        'class': 'btn dropdown-toggle',
                        'data-toggle': 'dropdown'
                    }).update('<i class="icon-cog"></i> ' + codendi.locales.tracker_artifact.display_settings + ' <span class="caret"></span>')

                ).insert(
                    new Element('ul', {
                        'class': 'dropdown-menu pull-right'
                    })
                    .insert(
                        new Element('li')
                            .insert(new Element ('a', {
                                'id': 'invert-order-menu-item',
                                'href': '#invert-order',
                            })
                            .update('<i class="icon-ok" style="display: none"></i> ' + codendi.locales.tracker_artifact.reverse_order)
                            .observe('click', function (evt) {
                                toggleCheckForCommentOrder();
                                invertFollowups(followup_section);
                                new Ajax.Request(codendi.tracker.base_url + "invert_comments_order.php", {
                                    parameters: {
                                        tracker: $('tracker_id').value
                                    }
                                });
                                Event.stop(evt);
                                return false;
                            }))

                    ).insert(
                        new Element('li')
                            .insert(new Element('a', {
                                'id': 'display-changes-menu-item',
                                'href': '#'
                            })
                            .update('<i class="icon-ok" style="display: none"></i> ' + codendi.locales.tracker_artifact.display_changes)
                            .observe('click', function (evt) {
                                followup_section.toggleClassName(display_changes_classname);
                                toggleCheckForDisplayChanges();
                                new Ajax.Request(codendi.tracker.base_url + "invert_display_changes.php");
                                Event.stop(evt);
                            }))
                    )
                )
            );

            followup_section.down('legend').insert({
                after: div
            });

            new Ajax.Request(codendi.tracker.base_url + "comments_order.php", {
                parameters: {
                    tracker: $('tracker_id').value
                },
                onSuccess: function (transport) {
                    if (!transport.responseText) {
                        toggleCheckForCommentOrder();
                        invertFollowups(followup_section);
                    }
                }
            });

            if (followup_section.hasClassName(display_changes_classname)) {
                toggleCheckForDisplayChanges();
            }
    });

    $$('.tracker_artifact_field  textarea').each(function (element) {
        var html_id = element.id;
        var id = html_id.match(/_(\d+)$/)[1];
        var name = 'artifact['+ id +'][format]';
        var htmlFormat = false;

        if ($('artifact['+id+']_body_format').value == 'html') {
            htmlFormat = true;
        }

        new tuleap.trackers.textarea.RTE(element, {toggle: true, default_in_html: false, id: id, name: name, htmlFormat: htmlFormat});
    });

    function getTextAreaValueAndHtmlFormat(comment_panel, id) {
        var content;
        var htmlFormat;

        if ($('tracker_artifact_followup_comment_body_format_'+id).value == 'html') {
            content    = comment_panel.down('.tracker_artifact_followup_comment_body').innerHTML;
            htmlFormat = true;
        } else {
            content    = comment_panel.down('.tracker_artifact_followup_comment_body').innerHTML.stripTags();
            htmlFormat = false;
        }

        return {value: content, htmlFormat: htmlFormat};
    }

    $$('.tracker_artifact_followup_comment_controls_edit').each(function (edit) {
        var id = edit.up('.tracker_artifact_followup').id;
        var data;

        if (id && id.match(/_\d+$/)) {
            id = id.match(/_(\d+)$/)[1];
            edit.observe('click', function (evt) {
                var comment_panel = edit.up().next();
                if (comment_panel.visible()) {

                    var textarea   = new Element('textarea', {id: 'tracker_followup_comment_edit_'+id});
                    var htmlFormat = false;

                    if (comment_panel.empty()) {
                        textarea.value = ''
                    } else {
                       data           = getTextAreaValueAndHtmlFormat(comment_panel, id)
                       textarea.value = data.value;
                       htmlFormat     = data.htmlFormat;
                    }

                    var rteSpan    = new Element('span', { style: 'text-align: left;'}).update(textarea);
                    var edit_panel = new Element('div', { style: 'text-align: right;'}).update(rteSpan);
                    comment_panel.insert({before: edit_panel});
                    var name = 'comment_format'+id;
                    new tuleap.trackers.textarea.RTE(textarea, {toggle: true, default_in_html: false, id: id, name: name, htmlFormat: htmlFormat});

                    var nb_rows_displayed = 5;
                    var nb_rows_content   = textarea.value.split(/\n/).length;
                    if (nb_rows_content > nb_rows_displayed) {
                        nb_rows_displayed = nb_rows_content;
                    }
                    textarea.rows = nb_rows_displayed;

                    comment_panel.hide();
                    textarea.focus();
                    var button = new Element('button').update('Ok').observe('click', function (evt) {
                        if (CKEDITOR.instances && CKEDITOR.instances['tracker_followup_comment_edit_'+id]) {
                            var content = CKEDITOR.instances['tracker_followup_comment_edit_'+id].getData();
                        } else {
                            var content = $('tracker_followup_comment_edit_'+id).getValue();
                        }
                        var format = $('rte_format_selectbox'+id).value;
                        var req = new Ajax.Request(location.href, {
                            parameters: {
                                func:           'update-comment',
                                changeset_id:   id,
                                content:        content,
                                comment_format: format
                            },
                            onSuccess: function (transport) {
                                    edit_panel.remove();
                                    comment_panel.update(transport.responseText).show();
                                    var e = new Effect.Highlight(comment_panel);
                            }
                        });
                        edit.show();
                        Event.stop(evt);
                        return false;
                    });

                    edit.hide();
                    var cancel = new Element('a', {
                        href: '#cancel'
                    }).update('Cancel').observe('click', function (evt) {
                        edit_panel.remove();
                        comment_panel.show();
                        Event.stop(evt);
                        edit.show();
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

    $$('.toggle-diff').each(function(toggle_button) {
        toggle_button.observe('click', function(event) {
            Event.stop(event);
            toggle_button.next().toggle();
        });
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
                new_attachment.down('p')
                .insert(
                    new Element('div')
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
            elem.up().siblings().invoke('addClassName', 'tracker_artifact_attachment_deleted');
        } else {
            elem.up().siblings().invoke('removeClassName', 'tracker_artifact_attachment_deleted');
        }
    }

    $$(".tracker_artifact_attachment_delete > input[type=checkbox]").each(function (elem) {
        //on load strike (useful when the checkbox is already checked on dom:loaded. (Missing required field for example)
        toggle_tracker_artifact_attachment_delete(elem);
        elem.observe('click', function (evt) { toggle_tracker_artifact_attachment_delete(elem); });
    });

    $$("div.artifact-submit-button input").each(function (elem) {
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
        var edition_switcher = new tuleap.tracker.artifact.editionSwitcher();

        if (edition_switcher.submissionBarIsAlreadyActive()) {
            return codendi.locales.tracker_formelement_admin.lose_follows;
        }
    }

});

codendi.Tooltip.selectors.push('a[class=direct-link-to-artifact]');
