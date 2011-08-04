/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Parts of this script are taken from Color Picker Script from Flooble.com
 * For more information, visit 
 *    http://www.flooble.com/scripts/colorpicker.php
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

var codendi = codendi || { };
codendi.colorpicker_theme = codendi.colorpicker_theme || 
                            [["#000000", "#333333", "#666666", "#888888", "#999999", "#AAAAAA", "#CCCCCC", "#EEEEEE", "#FFFFFF"],
                             ["#000033", "#000066", "#000099", "#0000CC", "#0000FF", "#3333FF", "#6666FF", "#9999FF", "#CCCCFF"],
                             ["#003300", "#006600", "#009900", "#00CC00", "#00FF00", "#33FF33", "#66FF66", "#99FF99", "#CCFFCC"],
                             ["#330000", "#660000", "#990000", "#CC0000", "#FF0000", "#FF3333", "#FF6666", "#FF9999", "#FFCCCC"],
                             ["#333300", "#666600", "#999900", "#CCCC00", "#FFFF00", "#FFFF33", "#FFFF66", "#FFFF99", "#FFFFCC"],
                             ["#003333", "#006666", "#009999", "#00CCCC", "#00FFFF", "#33FFFF", "#66FFFF", "#99FFFF", "#CCFFFF"],
                             ["#330033", "#660066", "#990099", "#CC00CC", "#FF00FF", "#FF33FF", "#FF66FF", "#FF99FF", "#FFCCFF"]];
codendi.colorpicker = {
    colorpickers: {},
    Palette: Class.create({
        current_colorpicker: null,
        initialize: function() {
            this.setColorEvent = this.setColor.bindAsEventListener(this);
            var colors = codendi.colorpicker_theme;
            var table = new Element('table', {border: 0, cellspacing: 2, cellpadding: 1});
            var tbody = new Element('tbody');
            table.appendChild(tbody);
            var tr;
            var n = colors.length;
            for (var i = 0 ; i < n ; ++i) {
                tr = new Element('tr');
                tbody.appendChild(tr);
                var m = colors[i].length;
                for (var j = 0 ; j < m ; ++j) {
                    var td = new Element('td', {
                        bgcolor: colors[i][j],
                        title: colors[i][j]
                    });
                    td.setStyle({
                        border:    '1px solid #000000',
                        color:      colors[i][j],
                        background: colors[i][j],
                        width:  '8px',
                        height: '12px',
                        cursor: 'pointer'
                    });
                    //td.update('&nbsp;');
                    td.observe('click', this.setColorEvent);
                    tr.appendChild(td);
                }
            }
            this.picker = new Element('div', {id: 'colorpicker'});
            this.picker.setStyle({
                position:   'absolute',
                display:    'none',
                border:     '#000000 1px solid',
                background: '#FFFFFF'
            });
            this.picker.appendChild(table);
            
            this.picker.appendChild(new Element('img', {
                src: codendi.imgroot+'ic/layer-transparent.png',
                style: 'color:transparent',
                title: 'transparent'
            }).observe('click', this.setColorEvent));
            
            
            document.body.appendChild(this.picker);
            this.pressEscapeEvent = this.pressEscape.bindAsEventListener(this);
            document.observe('keypress', this.pressEscapeEvent);
        },
        setColor: function(evt) {
            var color = Event.element(evt).getStyle('color');
            this.current_colorpicker.setColor(color);
            Event.stop(evt);
        },
        show: function(colorpicker) {
            this.current_colorpicker = colorpicker;
            this.picker.setStyle({
                top: (this.getAbsoluteOffsetTop(colorpicker.element) + 20)+'px',
                left: this.getAbsoluteOffsetLeft(colorpicker.element) + 'px',
                display: 'block'
            });
        },
        hide: function () {
            this.picker.hide();
            this.current_colorpicker = null;
        },
        pressEscape: function (evt) {
            if (evt.keyCode === Event.KEY_ESC && this.picker.visible()) {
                this.hide();
                Event.stop(evt);
            }
        },
        getAbsoluteOffsetTop: function (obj) {
            var top = obj.offsetTop;
            var parent = obj.offsetParent;
            while (parent !== document.body) {
                top += parent.offsetTop;
                parent = parent.offsetParent;
            }
            return top;
        },
         
        getAbsoluteOffsetLeft: function (obj) {
            var left = obj.offsetLeft;
            var parent = obj.offsetParent;
            while (parent !== document.body) {
                left += parent.offsetLeft;
                parent = parent.offsetParent;
            }
            return left;
        },
        toggle: function (colorpicker) {
            if (this.picker.visible()) {
                this.hide();
            } else {
                this.show(colorpicker);
            }
        }
    }),
    current_palette: null,
    Colorpicker: Class.create({
        nocolor: Prototype.Browser.IE ? '' : 'none',
        
        initialize: function (element) {
            this.element = $(element);
            this.field = $(this.element.identify() + '_field');
            if (this.field) {
                this.field.setStyle(
                    {
                        display: 'none'
                    }
                );
            }
            this.element.observe('click', this.pickColor.bind(this));
        },
        
        setColor: function (color) {
            if (this.field) {
                this.field.value = color === 'transparent' ? '' : color.parseColor();
            }
            if (color === '') {
                color = this.nocolor;
            }
            this.element.style.background = color;
            this.element.style.color = color;
            $('colorpicker').style.display = 'none';
            var img = this.element.tagName.toLowerCase() === 'img' ? this.element : this.element.down('img');
            if (img) {
                var src = ['ic/layer-transparent.png', 'blank16x16.png'];
                if (color === 'transparent') {
                    src = src.reverse();
                }
                if (img.src.endsWith(src[0])) {
                    img.src = img.src.gsub(src[0], src[1]);
                }
            }
        },
        
        pickColor: function () {
            if (!codendi.colorpicker.current_palette) {
                codendi.colorpicker.current_palette = new codendi.colorpicker.Palette();
            }
            codendi.colorpicker.current_palette.toggle(this);
        }
    })
};

document.observe('dom:loaded', function () {
    $$('.colorpicker').each(function (element) {
        codendi.colorpicker.colorpickers[element.identify()] = new codendi.colorpicker.Colorpicker(element);
    });
});
