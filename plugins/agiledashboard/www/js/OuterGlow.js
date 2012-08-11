/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

Effect.OuterGlow = Class.create(Effect.Highlight, {
  setup: function() {
    // Prevent executing on elements not in the layout flow
    if (this.element.getStyle('display')=='none') { this.cancel(); return; }
    this.oldStyle = { boxShadow: this.element.getStyle('box-shadow') };
  },
  update: function(position) {
    this.element.setStyle({ boxShadow: '0px 0px ' + Math.round(30 - 30 * position) + 'px ' + this.options.startcolor });
  },
  finish: function() {
    this.element.setStyle(this.oldStyle);
  }
});
