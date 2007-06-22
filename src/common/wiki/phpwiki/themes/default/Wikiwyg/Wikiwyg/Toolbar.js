/*==============================================================================
This Wikiwyg class provides toolbar support

COPYRIGHT:

    Copyright (c) 2005 Socialtext Corporation 
    655 High Street
    Palo Alto, CA 94301 U.S.A.
    All rights reserved.

Wikiwyg is free software. 

This library is free software; you can redistribute it and/or modify it
under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation; either version 2.1 of the License, or (at
your option) any later version.

This library is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
General Public License for more details.

    http://www.gnu.org/copyleft/lesser.txt

 =============================================================================*/

proto = new Subclass('Wikiwyg.Toolbar', 'Wikiwyg.Base');
proto.classtype = 'toolbar';

proto.config = {
    imagesLocation: 'images/',
    imagesExtension: '.gif',
    controlLayout: [
        'save', 'cancel', 'mode_selector', '/',
        'selector',
        'h1', 'h2', 'h3', 'h4', 'p', 'pre', '|',
        'bold', 'italic', 'underline', 'strike', '|',
        'link', 'hr', '|',
        'ordered', 'unordered', '|',
        'indent', 'outdent', '|',
        'table', '|',
        'help'
    ],
    styleSelector: [
        'label', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'
    ],
    controlLabels: {
        save: 'Save',
        cancel: 'Cancel',
        bold: 'Bold (ctl-b)',
        italic: 'Italic (ctl-i)',
        underline: 'Underline (ctl-u)',
        strike: 'Strike Through (ctl-d)',
        hr: 'Horizontal Rule',
        ordered: 'Numbered List',
        unordered: 'Bulleted List',
        indent: 'More Indented',
        outdent: 'Less Indented',
        help: 'About Wikiwyg',
        label: '[Style]',
        p: 'Normal Text',
        pre: 'Preformatted',
        h1: 'Heading 1',
        h2: 'Heading 2',
        h3: 'Heading 3',
        h4: 'Heading 4',
        h5: 'Heading 5',
        h6: 'Heading 6',
        link: 'Create Link',
        table: 'Create Table'
    }
};

proto.initializeObject = function() {
    this.div = Wikiwyg.createElementWithAttrs(
        'div', { 
            'class': 'wikiwyg_toolbar',
            id: 'wikiwyg_toolbar'
        }
    );

    var config = this.config;
    for (var i = 0; i < config.controlLayout.length; i++) {
        var action = config.controlLayout[i];
        var label = config.controlLabels[action]
        if (action == 'save')
            this.addControlItem(label, 'saveChanges');
        else if (action == 'cancel')
            this.addControlItem(label, 'cancelEdit');
        else if (action == 'mode_selector')
            this.addModeSelector();
        else if (action == 'selector')
            this.add_styles();
        else if (action == 'help')
            this.add_help_button(action, label);
        else if (action == '|')
            this.add_separator();
        else if (action == '/')
            this.add_break();
        else
            this.add_button(action, label);
    }
}

proto.enableThis = function() {
    this.div.style.display = 'block';
}

proto.disableThis = function() {
    this.div.style.display = 'none';
}

proto.make_button = function(type, label) {
    var base = this.config.imagesLocation;
    var ext = this.config.imagesExtension;
    return Wikiwyg.createElementWithAttrs(
        'img', {
            'class': 'wikiwyg_button',
            onmouseup: "this.style.border='1px outset';",
            onmouseover: "this.style.border='1px outset';",
            onmouseout:
                "this.style.borderColor=this.style.backgroundColor;" +
                "this.style.borderStyle='solid';",
            onmousedown:     "this.style.border='1px inset';",
            alt: label,
            title: label,
            src: base + type + ext
        }
    );
}

proto.add_button = function(type, label) {
    var img = this.make_button(type, label);
    var self = this;
    img.onclick = function() {
        self.wikiwyg.current_mode.process_command(type);
    };
    this.div.appendChild(img);
}

proto.add_help_button = function(type, label) {
    var img = this.make_button(type, label);
    var a = Wikiwyg.createElementWithAttrs(
        'a', {
            target: 'wikiwyg_button',
            href: 'http://www.wikiwyg.net/about/'
        }
    );
    a.appendChild(img);
    this.div.appendChild(a);
}

proto.add_separator = function() {
    var base = this.config.imagesLocation;
    var ext = this.config.imagesExtension;
    this.div.appendChild(
        Wikiwyg.createElementWithAttrs(
            'img', {
                'class': 'wikiwyg_separator',
                alt: ' | ',
                title: '',
                src: base + 'separator' + ext
            }
        )
    );
}

proto.addControlItem = function(text, method) {
    var span = Wikiwyg.createElementWithAttrs(
        'span', { 'class': 'wikiwyg_control_link' }
    );

    var link = Wikiwyg.createElementWithAttrs(
        'a', { href: '#' }
    );
    link.innerHTML = text;

    if( method != 'saveChanges' ) {
    span.appendChild(link);
    }
    
    var self = this;
    link.onclick = function() { eval('self.wikiwyg.' + method + '()'); return false };

    // For phpwiki only
    var form = document.getElementById("editpage");  
    form.onsubmit = function() { eval('self.wikiwyg.saveChanges()');return true }

    this.div.appendChild(span);
}

proto.resetModeSelector = function() {
    if (this.firstModeRadio) {
        var temp = this.firstModeRadio.onclick;
        this.firstModeRadio.onclick = null;
        this.firstModeRadio.click();
        this.firstModeRadio.onclick = temp;
    }
}

proto.addModeSelector = function() {
    var span = document.createElement('span');

    var radio_name = Wikiwyg.createUniqueId();
    for (var i = 0; i < this.wikiwyg.config.modeClasses.length; i++) {
        var class_name = this.wikiwyg.config.modeClasses[i];
        var mode_object = this.wikiwyg.mode_objects[class_name];
 
        var radio_id = Wikiwyg.createUniqueId();
 
        var checked = i == 0 ? 'checked' : '';
	if ( i==0 ) 
	  var radio = Wikiwyg.createElementWithAttrs(
            'input', {
                type: 'radio',
                name: radio_name,
                id: radio_id,
                value: mode_object.classname,
                'checked': checked
		}
	    );	    
	else
	   var radio = Wikiwyg.createElementWithAttrs(
             'input', {
                type: 'radio',
                name: radio_name,
                id: radio_id,
                value: mode_object.classname
		 }
	    );

        if (!this.firstModeRadio)
            this.firstModeRadio = radio;
 
        var self = this;
        radio.onclick = function() { 
            self.wikiwyg.switchMode(this.value);
        };
 
        var label = Wikiwyg.createElementWithAttrs(
            'label', { 'for': radio_id }
        );
        label.innerHTML = mode_object.modeDescription;

        span.appendChild(radio);
        span.appendChild(label);
    }
    this.div.appendChild(span);
}

proto.add_break = function() {
    this.div.appendChild(document.createElement('br'));
}

proto.add_styles = function() {
    var options = this.config.styleSelector;
    var labels = this.config.controlLabels;

    this.styleSelect = Wikiwyg.createElementWithAttrs(
        'select', {
            'class': 'wikiwyg_selector'
        }
    );

    for (var i = 0; i < options.length; i++) {
        value = options[i];
        var option = Wikiwyg.createElementWithAttrs(
            'option', { 'value': value }
        );
        option.textContent = labels[value];
        this.styleSelect.appendChild(option);
    }
    var self = this;
    this.styleSelect.onchange = function() { 
        self.set_style(this.value) 
    };
    this.div.appendChild(this.styleSelect);
}

proto.set_style = function(style_name) {
    var idx = this.styleSelect.selectedIndex;
    // First one is always a label
    if (idx != 0)
        this.wikiwyg.current_mode.process_command(style_name);
    this.styleSelect.selectedIndex = 0;
}
