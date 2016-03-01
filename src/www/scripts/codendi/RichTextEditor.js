/**
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
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
 */

var codendi = codendi || { };

codendi.RTE = Class.create(
    {
        initialize: function (element, options) {
            this.element = $(element);
            this.options = Object.extend({
                toolbar: 'tuleap', //basic | full | minimal | tuleap
                onLoad: Prototype.emptyFunction,
                toggle: false,
                default_in_html: true
            }, options || { });
            
            this.rte = false;

            if (!this.options.toggle || this.options.default_in_html) {
                this.init_rte();
            }
        },

        can_be_resized : function () {
            var resize_enabled = this.options.resize_enabled;
            return (typeof(resize_enabled) == 'undefined' || resize_enabled);
        },

        init_rte: function () {
            var replace_options = {
                resize_enabled : true
            };

            if (CKEDITOR.instances && CKEDITOR.instances[this.element.id]) {
                CKEDITOR.instances[this.element.id].destroy(true);
            }
            if (this.options.toolbar == 'basic') {
                var toolbar = [
                                  ['Styles', 'Format', 'Font', 'FontSize'],
                                  ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],
                                  '/',
                                  ['TextColor', 'BGColor'],
                                  ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote'],
                                  ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                                  ['Link', 'Unlink', 'Anchor', 'Image']
                              ];
            } else if (this.options.toolbar == 'minimal') {
                var toolbar = [
                                  ['Bold', 'Italic', 'Underline'],
                                  ['NumberedList', 'BulletedList', '-', 'Blockquote', 'Format'],
                                  ['Link', 'Unlink', 'Anchor', 'Image']
                              ];
            } else if (this.options.toolbar == 'tuleap') {
                var toolbar = [
                                  ['Bold', 'Italic', 'Underline'],
                                  ['NumberedList', 'BulletedList', '-', 'Blockquote', 'Format'],
                                  ['Link', 'Unlink', 'Anchor', 'Image'],
                                  ['Source']
                              ];
            } else {
                var toolbar = 'Full'
            }

            /*
             This is done for IE
             If we load the page and the RTE is displayed, IE will not
             catch the instanceCreated event on load (it will catch it later if we change
             the format between Text and HTML). So we have to set this option when loading
             */

            replace_options.toolbar = toolbar;
            if (! this.can_be_resized()) {
                replace_options.resize_enabled = false;
            }

            this.rte = CKEDITOR.replace(this.element.id, replace_options);

            /*CKEDITOR filters HTML tags
              So, if your default text is like <blabla>, this will not be displayed.
              To "fix" this, we escape the textarea content.
              However, we don't need to espace this for non default values.
            */

            if (this.element.readAttribute('data-field-default-value') !== null) {
                var escaped_value = tuleap.escaper.html(this.element.value);
                this.rte.setData(escaped_value);
            }

            CKEDITOR.on('dialogDefinition', function (ev) {
                var tab,
                    dialog     = ev.data.name,
                    definition = ev.data.definition;

                if (dialog === 'link') {
                   definition.removeContents('target');
                }

                if (dialog === 'image') {
                    tab = definition.getContents('Link');
                    tab.remove('cmbTarget');
                }
            });

            this.rte.on('instanceReady', function (evt) {
                this.document.getBody().$.contentEditable = true;
                tuleap.mention.init(this.document.getBody().$);
            });

            CKEDITOR.on('instanceCreated', function (evt) {
                if (evt.editor === this.rte) {
                    this.options.onLoad();
                }

                if (! this.can_be_resized()) {
                      evt.editor.config.resize_enabled = false;
                }

            }.bind(this));

            CKEDITOR.on('instanceReady', function (evt) {
                if (evt.editor === this.rte) {
                    if (undefined != this.options.full_width && this.options.full_width) {
                        evt.editor.resize('100%', this.element.getHeight(), true);

                    } else if (this.element.getWidth() > 0 && (typeof(this.options.no_resize) == 'undefined' || ! this.options.no_resize)) {
                        evt.editor.resize(this.element.getWidth(), this.element.getHeight(), true);
                    }
                }
            }.bind(this));

        },
        toggle: function (evt, option) {
            if (option == 'html' && !this.rte) {
                this.init_rte();
            } else {
                this.rte.destroy();
                this.rte = null;
            }
            Event.stop(evt);
            return false;
        },
        destroy: function () {
            try {
                this.rte.destroy(false);
            } catch (e) {
            }
            this.rte = null;
        }
    }
);

var Codendi_RTE_Light = Class.create({
    initialize: function (element) {
        this.element = $(element);
        this.rte     = false;

        // If there is an element named after the editor's id + label add
        // a toggler on it
        if ($(element+'_label')) {
            var toggle = Builder.node('a', {'class': 'rte_toggle', href: '#'});
            toggle.appendChild(document.createTextNode(' Rich text editor '));

            // Must use Event.observe(toggle... instead of toggle.observe(...
            // Otherwise IE cannot manage it. Oo
            Event.observe(toggle, 'click', this.toggle.bindAsEventListener(this));

            $(element+'_label').appendChild(toggle);
        }

    },
    init_rte: function() {
        tinyMCE.init({
                // General options
                mode : "exact",
                elements : this.element.id,
                theme : "advanced",

                // Inherit language from Codendi default (see Layout class)
                language : useLanguage,

                // Theme options
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_buttons1 : "bold,italic,blockquote,formatselect,image,|,bullist,numlist,|,link,unlink,|,code",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : "",
                theme_advanced_resizing : true,
                theme_advanced_blockformats : "p,pre",
                
                codendi:null //cheat to not have to remove the last comma in elements above. #*%@ IE !
        });
        this.rte = true;
    },
    toggle: function(event) {
        if (!this.rte) {
            this.init_rte();
        } else {
            if (!tinyMCE.get(this.element.id)) {
                tinyMCE.execCommand("mceAddControl", false, this.element.id);
            } else {
                tinyMCE.execCommand("mceRemoveControl", false, this.element.id);
            }
        }
        event.stop();
    }
});

var Codendi_RTE_Send_HTML_MAIL = Class.create(Codendi_RTE_Light, {
    initialize: function ($super,element, format) {
        $super(element);

        var label = $(element+'_label');

        // This div contains comment format selection buttons
        var div = Builder.node('div', {'class' : 'rte_format'});
        var bold = document.createElement("b");
        bold.appendChild(document.createTextNode("Body format : "));
        div.appendChild(bold);

        var selectbox = Builder.node('select', {'name' : 'body_format', 'class' : 'input-small'});
        div.appendChild(selectbox);

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.

        var text_option = Builder.node(
            'option',
            {'value' : '0', 'id' : 'body_format_text', 'selected' : 'selected'},
            "Text"
        );
        selectbox.appendChild(text_option);

        // Add an option that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_option = Builder.node(
            'option',
            {'value': '1', 'id' : 'body_format_html'},
            "HTML"
        );
        selectbox.appendChild(html_option);

        label.appendChild(div);

        // This div is used to clear the CSS of the pervious div
        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        label.appendChild(div_clear);

        if (format == 'html') {
            this.switchButtonToHtml();
        } else {
            $('body_format_text').checked = true;
        }
        
    },
    init_rte: function() {
        tinyMCE.init({
                // General options
                mode : "exact",
                elements : this.element.id,
                theme : "advanced",

                // Inherit language from Codendi default (see Layout class)
                language : useLanguage,

                // Theme options
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_buttons1 : "bold,italic,underline,strikethrough,blockquote,formatselect,|,bullist,numlist,|,link,unlink,|,forecolor,backcolor,code",
                theme_advanced_buttons2 : "formatselect,fontselect,fontsizeselect,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,outdent,indent",
                theme_advanced_buttons3 : "",
                theme_advanced_resizing : true,
                theme_advanced_blockformats : "p,pre",
            
                codendi:null //cheat to not have to remove the last comma in elements above. #*%@ IE !
        });
        this.rte = true;
    },
    toggle: function ($super, event) {
        this.switchButtonToHtml();
        $super(event);
    },
    /**
     * Disable the radio button that tells that the content is text
     * Check the radio button that tells that the content is HTML
     */
    switchButtonToHtml: function () {
        $('body_format_text').disabled = true;
        $('body_format_html').selected  = true;
    }
});
