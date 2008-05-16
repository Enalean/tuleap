/**
 * $Id: editor_template_src.js 793 2008-04-10 17:32:40Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	var DOM = tinymce.DOM, Event = tinymce.dom.Event, extend = tinymce.extend, each = tinymce.each, Cookie = tinymce.util.Cookie, lastExtID, explode = tinymce.explode;

	// Tell it to load theme specific language pack(s)
	tinymce.ThemeManager.requireLangPack('advanced');

	tinymce.create('tinymce.themes.AdvancedTheme', {
		// Control name lookup, format: title, command
		controls : {
			bold : ['bold_desc', 'Bold'],
			italic : ['italic_desc', 'Italic'],
			underline : ['underline_desc', 'Underline'],
			strikethrough : ['striketrough_desc', 'Strikethrough'],
			justifyleft : ['justifyleft_desc', 'JustifyLeft'],
			justifycenter : ['justifycenter_desc', 'JustifyCenter'],
			justifyright : ['justifyright_desc', 'JustifyRight'],
			justifyfull : ['justifyfull_desc', 'JustifyFull'],
			bullist : ['bullist_desc', 'InsertUnorderedList'],
			numlist : ['numlist_desc', 'InsertOrderedList'],
			outdent : ['outdent_desc', 'Outdent'],
			indent : ['indent_desc', 'Indent'],
			cut : ['cut_desc', 'Cut'],
			copy : ['copy_desc', 'Copy'],
			paste : ['paste_desc', 'Paste'],
			undo : ['undo_desc', 'Undo'],
			redo : ['redo_desc', 'Redo'],
			link : ['link_desc', 'mceLink'],
			unlink : ['unlink_desc', 'unlink'],
			image : ['image_desc', 'mceImage'],
			cleanup : ['cleanup_desc', 'mceCleanup'],
			help : ['help_desc', 'mceHelp'],
			code : ['code_desc', 'mceCodeEditor'],
			hr : ['hr_desc', 'InsertHorizontalRule'],
			removeformat : ['removeformat_desc', 'RemoveFormat'],
			sub : ['sub_desc', 'subscript'],
			sup : ['sup_desc', 'superscript'],
			forecolor : ['forecolor_desc', 'ForeColor'],
			forecolorpicker : ['forecolor_desc', 'mceForeColor'],
			backcolor : ['backcolor_desc', 'HiliteColor'],
			backcolorpicker : ['backcolor_desc', 'mceBackColor'],
			charmap : ['charmap_desc', 'mceCharMap'],
			visualaid : ['visualaid_desc', 'mceToggleVisualAid'],
			anchor : ['anchor_desc', 'mceInsertAnchor'],
			newdocument : ['newdocument_desc', 'mceNewDocument'],
			blockquote : ['blockquote_desc', 'mceBlockQuote']
		},

		stateControls : ['bold', 'italic', 'underline', 'strikethrough', 'bullist', 'numlist', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull', 'sub', 'sup', 'blockquote'],

		init : function(ed, url) {
			var t = this, s, v;
	
			t.editor = ed;
			t.url = url;
			t.onResolveName = new tinymce.util.Dispatcher(this);

			// Default settings
			t.settings = s = extend({
				theme_advanced_path : true,
				theme_advanced_toolbar_location : 'bottom',
				theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect",
				theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code",
				theme_advanced_buttons3 : "hr,removeformat,visualaid,|,sub,sup,|,charmap",
				theme_advanced_blockformats : "p,address,pre,h1,h2,h3,h4,h5,h6",
				theme_advanced_toolbar_align : "center",
				theme_advanced_fonts : "Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats",
				theme_advanced_font_sizes : "1,2,3,4,5,6,7",
				theme_advanced_more_colors : 1,
				theme_advanced_row_height : 23,
				theme_advanced_resize_horizontal : 1,
				theme_advanced_resizing_use_cookie : 1
			}, ed.settings);

			if ((v = s.theme_advanced_path_location) && v != 'none')
				s.theme_advanced_statusbar_location = s.theme_advanced_path_location;

			if (s.theme_advanced_statusbar_location == 'none')
				s.theme_advanced_statusbar_location = 0;

			// Init editor
			ed.onInit.add(function() {
				ed.onNodeChange.add(t._nodeChanged, t);

				if (ed.settings.content_css !== false)
					ed.dom.loadCSS(ed.baseURI.toAbsolute("themes/advanced/skins/" + ed.settings.skin + "/content.css"));
			});

			ed.onSetProgressState.add(function(ed, b, ti) {
				var co, id = ed.id, tb;

				if (b) {
					t.progressTimer = setTimeout(function() {
						co = ed.getContainer();
						co = co.insertBefore(DOM.create('DIV', {style : 'position:relative'}), co.firstChild);
						tb = DOM.get(ed.id + '_tbl');

						DOM.add(co, 'div', {id : id + '_blocker', 'class' : 'mceBlocker', style : {width : tb.clientWidth + 2, height : tb.clientHeight + 2}});
						DOM.add(co, 'div', {id : id + '_progress', 'class' : 'mceProgress', style : {left : tb.clientWidth / 2, top : tb.clientHeight / 2}});
					}, ti || 0);
				} else {
					DOM.remove(id + '_blocker');
					DOM.remove(id + '_progress');
					clearTimeout(t.progressTimer);
				}
			});

			DOM.loadCSS(ed.baseURI.toAbsolute(s.editor_css || "themes/advanced/skins/" + ed.settings.skin + "/ui.css"));

			if (s.skin_variant)
				DOM.loadCSS(ed.baseURI.toAbsolute(s.editor_css || "themes/advanced/skins/" + ed.settings.skin + "/ui_" + s.skin_variant + ".css"));
		},

		createControl : function(n, cf) {
			var cd, c;

			if (c = cf.createControl(n))
				return c;

			switch (n) {
				case "styleselect":
					return this._createStyleSelect();

				case "formatselect":
					return this._createBlockFormats();

				case "fontselect":
					return this._createFontSelect();

				case "fontsizeselect":
					return this._createFontSizeSelect();

				case "forecolor":
					return this._createForeColorMenu();

				case "backcolor":
					return this._createBackColorMenu();
			}

			if ((cd = this.controls[n]))
				return cf.createButton(n, {title : "advanced." + cd[0], cmd : cd[1], ui : cd[2], value : cd[3]});
		},

		execCommand : function(cmd, ui, val) {
			var f = this['_' + cmd];

			if (f) {
				f.call(this, ui, val);
				return true;
			}

			return false;
		},

		_importClasses : function() {
			var ed = this.editor, c = ed.controlManager.get('styleselect');

			if (c.getLength() == 0) {
				each(ed.dom.getClasses(), function(o) {
					c.add(o['class'], o['class']);
				});
			}
		},

		_createStyleSelect : function(n) {
			var t = this, ed = t.editor, cf = ed.controlManager, c = cf.createListBox('styleselect', {
				title : 'advanced.style_select',
				onselect : function(v) {
					if (c.selectedValue === v) {
						ed.execCommand('mceSetStyleInfo', 0, {command : 'removeformat'});
						c.select();
						return false;
					} else
						ed.execCommand('mceSetCSSClass', 0, v);
				}
			});

			if (c) {
				each(ed.getParam('theme_advanced_styles', '', 'hash'), function(v, k) {
					if (v)
						c.add(t.editor.translate(k), v);
				});

				c.onPostRender.add(function(ed, n) {
					Event.add(n, 'focus', t._importClasses, t);
					Event.add(n, 'mousedown', t._importClasses, t);
				});
			}

			return c;
		},

		_createFontSelect : function() {
			var c, t = this, ed = t.editor;

			c = ed.controlManager.createListBox('fontselect', {title : 'advanced.fontdefault', cmd : 'FontName'});
			if (c) {
				each(ed.getParam('theme_advanced_fonts', t.settings.theme_advanced_fonts, 'hash'), function(v, k) {
					c.add(ed.translate(k), v, {style : v.indexOf('dings') == -1 ? 'font-family:' + v : ''});
				});
			}

			return c;
		},

		_createFontSizeSelect : function() {
			var c, t = this, lo = [
				"1 (8 pt)",
				"2 (10 pt)",
				"3 (12 pt)",
				"4 (14 pt)",
				"5 (18 pt)",
				"6 (24 pt)",
				"7 (36 pt)"
			], fz = [8, 10, 12, 14, 18, 24, 36];

			c = t.editor.controlManager.createListBox('fontsizeselect', {title : 'advanced.font_size', cmd : 'FontSize'});
			if (c) {
				each(explode(t.settings.theme_advanced_font_sizes), function(v) {
					c.add(lo[parseInt(v) - 1], v, {'style' : 'font-size:' + fz[v - 1] + 'pt', 'class' : 'mceFontSize' + v});
				});
			}

			return c;
		},

		_createBlockFormats : function() {
			var c, fmts = {
				p : 'advanced.paragraph',
				address : 'advanced.address',
				pre : 'advanced.pre',
				h1 : 'advanced.h1',
				h2 : 'advanced.h2',
				h3 : 'advanced.h3',
				h4 : 'advanced.h4',
				h5 : 'advanced.h5',
				h6 : 'advanced.h6',
				div : 'advanced.div',
				blockquote : 'advanced.blockquote',
				code : 'advanced.code',
				dt : 'advanced.dt',
				dd : 'advanced.dd',
				samp : 'advanced.samp'
			}, t = this;

			c = t.editor.controlManager.createListBox('formatselect', {title : 'advanced.block', cmd : 'FormatBlock'});
			if (c) {
				each(t.editor.getParam('theme_advanced_blockformats', t.settings.theme_advanced_blockformats, 'hash'), function(v, k) {
					c.add(t.editor.translate(k != v ? k : fmts[v]), v, {'class' : 'mce_formatPreview mce_' + v});
				});
			}

			return c;
		},

		_createForeColorMenu : function() {
			var c, t = this, s = t.settings, o = {}, v;

			if (s.theme_advanced_more_colors) {
				o.more_colors_func = function() {
					t._mceColorPicker(0, {
						color : c.value,
						func : function(co) {
							c.setColor(co);
						}
					});
				};
			}

			if (v = s.theme_advanced_text_colors)
				o.colors = v;

			o.title = 'advanced.forecolor_desc';
			o.cmd = 'ForeColor';
			o.scope = this;

			c = t.editor.controlManager.createColorSplitButton('forecolor', o);

			return c;
		},

		_createBackColorMenu : function() {
			var c, t = this, s = t.settings, o = {}, v;

			if (s.theme_advanced_more_colors) {
				o.more_colors_func = function() {
					t._mceColorPicker(0, {
						color : c.value,
						func : function(co) {
							c.setColor(co);
						}
					});
				};
			}

			if (v = s.theme_advanced_background_colors)
				o.colors = v;

			o.title = 'advanced.backcolor_desc';
			o.cmd = 'HiliteColor';
			o.scope = this;

			c = t.editor.controlManager.createColorSplitButton('backcolor', o);

			return c;
		},

		renderUI : function(o) {
			var n, ic, tb, t = this, ed = t.editor, s = t.settings, sc, p, nl;

			n = p = DOM.create('span', {id : ed.id + '_parent', 'class' : 'mceEditor ' + ed.settings.skin + 'Skin' + (s.skin_variant ? ' ' + ed.settings.skin + 'Skin' + t._ufirst(s.skin_variant) : '')});

			if (!DOM.boxModel)
				n = DOM.add(n, 'div', {'class' : 'mceOldBoxModel'});

			n = sc = DOM.add(n, 'table', {id : ed.id + '_tbl', 'class' : 'mceLayout', cellSpacing : 0, cellPadding : 0});
			n = tb = DOM.add(n, 'tbody');

			switch ((s.theme_advanced_layout_manager || '').toLowerCase()) {
				case "rowlayout":
					ic = t._rowLayout(s, tb, o);
					break;

				case "customlayout":
					ic = ed.execCallback("theme_advanced_custom_layout", s, tb, o, p);
					break;

				default:
					ic = t._simpleLayout(s, tb, o, p);
			}

			n = o.targetNode;

			// Add classes to first and last TRs
			nl = DOM.stdMode ? sc.getElementsByTagName('tr') : sc.rows; // Quick fix for IE 8
			DOM.addClass(nl[0], 'mceFirst');
			DOM.addClass(nl[nl.length - 1], 'mceLast');

			// Add classes to first and last TDs
			each(DOM.select('tr', tb), function(n) {
				DOM.addClass(n.firstChild, 'mceFirst');
				DOM.addClass(n.childNodes[n.childNodes.length - 1], 'mceLast');
			});

			if (DOM.get(s.theme_advanced_toolbar_container))
				DOM.get(s.theme_advanced_toolbar_container).appendChild(p);
			else
				DOM.insertAfter(p, n);

			Event.add(ed.id + '_path_row', 'click', function(e) {
				e = e.target;

				if (e.nodeName == 'A') {
					t._sel(e.className.replace(/^.*mcePath_([0-9]+).*$/, '$1'));

					return Event.cancel(e);
				}
			});
/*
			if (DOM.get(ed.id + '_path_row')) {
				Event.add(ed.id + '_tbl', 'mouseover', function(e) {
					var re;
	
					e = e.target;

					if (e.nodeName == 'SPAN' && DOM.hasClass(e.parentNode, 'mceButton')) {
						re = DOM.get(ed.id + '_path_row');
						t.lastPath = re.innerHTML;
						DOM.setHTML(re, e.parentNode.title);
					}
				});

				Event.add(ed.id + '_tbl', 'mouseout', function(e) {
					if (t.lastPath) {
						DOM.setHTML(ed.id + '_path_row', t.lastPath);
						t.lastPath = 0;
					}
				});
			}
*/

			if (!ed.getParam('accessibility_focus') || ed.getParam('tab_focus'))
				Event.add(DOM.add(p, 'a', {href : '#'}, '<!-- IE -->'), 'focus', function() {tinyMCE.get(ed.id).focus();});

			if (s.theme_advanced_toolbar_location == 'external')
				o.deltaHeight = 0;

			t.deltaHeight = o.deltaHeight;
			o.targetNode = null;

			return {
				iframeContainer : ic,
				editorContainer : ed.id + '_parent',
				sizeContainer : sc,
				deltaHeight : o.deltaHeight
			};
		},

		getInfo : function() {
			return {
				longname : 'Advanced theme',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			}
		},

		resizeBy : function(dw, dh) {
			var e = DOM.get(this.editor.id + '_tbl');

			this.resizeTo(e.clientWidth + dw, e.clientHeight + dh);
		},

		resizeTo : function(w, h) {
			var ed = this.editor, s = ed.settings, e = DOM.get(ed.id + '_tbl'), ifr = DOM.get(ed.id + '_ifr'), dh;

			// Boundery fix box
			w = Math.max(s.theme_advanced_resizing_min_width || 100, w);
			h = Math.max(s.theme_advanced_resizing_min_height || 100, h);
			w = Math.min(s.theme_advanced_resizing_max_width || 0xFFFF, w);
			h = Math.min(s.theme_advanced_resizing_max_height || 0xFFFF, h);

			// Calc difference between iframe and container
			dh = e.clientHeight - ifr.clientHeight;

			// Resize iframe and container
			DOM.setStyle(ifr, 'height', h - dh);
			DOM.setStyles(e, {width : w, height : h});
		},

		destroy : function() {
			var id = this.editor.id;

			Event.clear(id + '_resize');
			Event.clear(id + '_path_row');
			Event.clear(id + '_external_close');
		},

		// Internal functions

		_simpleLayout : function(s, tb, o, p) {
			var t = this, ed = t.editor, lo = s.theme_advanced_toolbar_location, sl = s.theme_advanced_statusbar_location, n, ic, etb, c;

			// Create toolbar container at top
			if (lo == 'top')
				t._addToolbars(tb, o);

			// Create external toolbar
			if (lo == 'external') {
				n = c = DOM.create('div', {style : 'position:relative'});
				n = DOM.add(n, 'div', {id : ed.id + '_external', 'class' : 'mceExternalToolbar'});
				DOM.add(n, 'a', {id : ed.id + '_external_close', href : 'javascript:;', 'class' : 'mceExternalClose'});
				n = DOM.add(n, 'table', {id : ed.id + '_tblext', cellSpacing : 0, cellPadding : 0});
				etb = DOM.add(n, 'tbody');

				if (p.firstChild.className == 'mceOldBoxModel')
					p.firstChild.appendChild(c);
				else
					p.insertBefore(c, p.firstChild);

				t._addToolbars(etb, o);

				ed.onMouseUp.add(function() {
					var e = DOM.get(ed.id + '_external');
					DOM.show(e);

					DOM.hide(lastExtID);

					var f = Event.add(ed.id + '_external_close', 'click', function() {
						DOM.hide(ed.id + '_external');
						Event.remove(ed.id + '_external_close', 'click', f);
					});

					DOM.show(e);
					DOM.setStyle(e, 'top', 0 - DOM.getRect(ed.id + '_tblext').h - 1);

					// Fixes IE rendering bug
					DOM.hide(e);
					DOM.show(e);
					e.style.filter = '';

					lastExtID = ed.id + '_external';

					e = null;
				});
			}

			if (sl == 'top')
				t._addStatusBar(tb, o);

			// Create iframe container
			if (!s.theme_advanced_toolbar_container) {
				n = DOM.add(tb, 'tr');
				n = ic = DOM.add(n, 'td', {'class' : 'mceIframeContainer'});
			}

			// Create toolbar container at bottom
			if (lo == 'bottom')
				t._addToolbars(tb, o);

			if (sl == 'bottom')
				t._addStatusBar(tb, o);

			return ic;
		},

		_rowLayout : function(s, tb, o) {
			var t = this, ed = t.editor, dc, da, cf = ed.controlManager, n, ic, to, a;

			dc = s.theme_advanced_containers_default_class || '';
			da = s.theme_advanced_containers_default_align || 'center';

			each(explode(s.theme_advanced_containers || ''), function(c, i) {
				var v = s['theme_advanced_container_' + c] || '';

				switch (c.toLowerCase()) {
					case 'mceeditor':
						n = DOM.add(tb, 'tr');
						n = ic = DOM.add(n, 'td', {'class' : 'mceIframeContainer'});
						break;

					case 'mceelementpath':
						t._addStatusBar(tb, o);
						break;

					default:
						a = s['theme_advanced_container_' + c + '_align'].toLowerCase();
						a = 'mce' + t._ufirst(a);

						n = DOM.add(DOM.add(tb, 'tr'), 'td', {
							'class' : 'mceToolbar ' + (s['theme_advanced_container_' + c + '_class'] || dc) + ' ' + a || da
						});

						to = cf.createToolbar("toolbar" + i);
						t._addControls(v, to);
						DOM.setHTML(n, to.renderHTML());
						o.deltaHeight -= s.theme_advanced_row_height;
				}
			});

			return ic;
		},

		_addControls : function(v, tb) {
			var t = this, s = t.settings, di, cf = t.editor.controlManager;

			if (s.theme_advanced_disable && !t._disabled) {
				di = {};

				each(explode(s.theme_advanced_disable), function(v) {
					di[v] = 1;
				});

				t._disabled = di;
			} else
				di = t._disabled;

			each(explode(v), function(n) {
				var c;

				if (di && di[n])
					return;

				// Compatiblity with 2.x
				if (n == 'tablecontrols') {
					each(["table","|","row_props","cell_props","|","row_before","row_after","delete_row","|","col_before","col_after","delete_col","|","split_cells","merge_cells"], function(n) {
						n = t.createControl(n, cf);

						if (n)
							tb.add(n);
					});

					return;
				}

				c = t.createControl(n, cf);

				if (c)
					tb.add(c);
			});
		},

		_addToolbars : function(c, o) {
			var t = this, i, tb, ed = t.editor, s = t.settings, v, cf = ed.controlManager, di, n, h = [], a;

			a = s.theme_advanced_toolbar_align.toLowerCase();
			a = 'mce' + t._ufirst(a);

			n = DOM.add(DOM.add(c, 'tr'), 'td', {'class' : 'mceToolbar ' + a});

			if (!ed.getParam('accessibility_focus') || ed.getParam('tab_focus'))
				h.push(DOM.createHTML('a', {href : '#', onfocus : 'tinyMCE.get(\'' + ed.id + '\').focus();'}, '<!-- IE -->'));

			h.push(DOM.createHTML('a', {href : '#', accesskey : 'q', title : ed.getLang("advanced.toolbar_focus")}, '<!-- IE -->'));

			// Create toolbar and add the controls
			for (i=1; (v = s['theme_advanced_buttons' + i]); i++) {
				tb = cf.createToolbar("toolbar" + i, {'class' : 'mceToolbarRow' + i});

				if (s['theme_advanced_buttons' + i + '_add'])
					v += ',' + s['theme_advanced_buttons' + i + '_add'];

				if (s['theme_advanced_buttons' + i + '_add_before'])
					v = s['theme_advanced_buttons' + i + '_add_before'] + ',' + v;

				t._addControls(v, tb);

				//n.appendChild(n = tb.render());
				h.push(tb.renderHTML());

				o.deltaHeight -= s.theme_advanced_row_height;
			}

			h.push(DOM.createHTML('a', {href : '#', accesskey : 'z', title : ed.getLang("advanced.toolbar_focus"), onfocus : 'tinyMCE.getInstanceById(\'' + ed.id + '\').focus();'}, '<!-- IE -->'));
			DOM.setHTML(n, h.join(''));
		},

		_addStatusBar : function(tb, o) {
			var n, t = this, ed = t.editor, s = t.settings, r, mf, me, td;

			n = DOM.add(tb, 'tr');
			n = td = DOM.add(n, 'td', {'class' : 'mceStatusbar'});
			n = DOM.add(n, 'div', {id : ed.id + '_path_row'}, s.theme_advanced_path ? ed.translate('advanced.path') + ': ' : '&nbsp;');
			DOM.add(n, 'a', {href : '#', accesskey : 'x'});

			if (s.theme_advanced_resizing && !tinymce.isOldWebKit) {
				DOM.add(td, 'a', {id : ed.id + '_resize', href : 'javascript:;', onclick : "return false;", 'class' : 'mceResize'});

				if (s.theme_advanced_resizing_use_cookie) {
					ed.onPostRender.add(function() {
						var o = Cookie.getHash("TinyMCE_" + ed.id + "_size"), c = DOM.get(ed.id + '_tbl');

						if (!o)
							return;

						if (s.theme_advanced_resize_horizontal)
							c.style.width = Math.max(10, o.cw) + 'px';

						c.style.height = Math.max(10, o.ch) + 'px';
						DOM.get(ed.id + '_ifr').style.height = Math.max(10, parseInt(o.ch) + t.deltaHeight) + 'px';
					});
				}

				ed.onPostRender.add(function() {
					Event.add(ed.id + '_resize', 'mousedown', function(e) {
						var c, p, w, h, n, pa;

						// Measure container
						c = DOM.get(ed.id + '_tbl');
						w = c.clientWidth;
						h = c.clientHeight;

						miw = s.theme_advanced_resizing_min_width || 100;
						mih = s.theme_advanced_resizing_min_height || 100;
						maw = s.theme_advanced_resizing_max_width || 0xFFFF;
						mah = s.theme_advanced_resizing_max_height || 0xFFFF;

						// Setup placeholder
						p = DOM.add(DOM.get(ed.id + '_parent'), 'div', {'class' : 'mcePlaceHolder'});
						DOM.setStyles(p, {width : w, height : h});

						// Replace with placeholder
						DOM.hide(c);
						DOM.show(p);

						// Create internal resize obj
						r = {
							x : e.screenX,
							y : e.screenY,
							w : w,
							h : h,
							dx : null,
							dy : null
						};

						// Start listening
						mf = Event.add(DOM.doc, 'mousemove', function(e) {
							var w, h;

							// Calc delta values
							r.dx = e.screenX - r.x;
							r.dy = e.screenY - r.y;

							// Boundery fix box
							w = Math.max(miw, r.w + r.dx);
							h = Math.max(mih, r.h + r.dy);
							w = Math.min(maw, w);
							h = Math.min(mah, h);

							// Resize placeholder
							if (s.theme_advanced_resize_horizontal)
								p.style.width = w + 'px';

							p.style.height = h + 'px';

							return Event.cancel(e);
						});

						me = Event.add(DOM.doc, 'mouseup', function(e) {
							var ifr;

							// Stop listening
							Event.remove(DOM.doc, 'mousemove', mf);
							Event.remove(DOM.doc, 'mouseup', me);

							c.style.display = '';
							DOM.remove(p);

							if (r.dx === null)
								return;

							ifr = DOM.get(ed.id + '_ifr');

							if (s.theme_advanced_resize_horizontal)
								c.style.width = Math.max(10, r.w + r.dx) + 'px';

							c.style.height = Math.max(10, r.h + r.dy) + 'px';
							ifr.style.height = Math.max(10, ifr.clientHeight + r.dy) + 'px';

							if (s.theme_advanced_resizing_use_cookie) {
								Cookie.setHash("TinyMCE_" + ed.id + "_size", {
									cw : r.w + r.dx,
									ch : r.h + r.dy
								});
							}
						});

						return Event.cancel(e);
					});
				});
			}

			o.deltaHeight -= 21;
			n = tb = null;
		},

		_nodeChanged : function(ed, cm, n, co) {
			var t = this, p, de = 0, v, c, s = t.settings;

			tinymce.each(t.stateControls, function(c) {
				cm.setActive(c, ed.queryCommandState(t.controls[c][1]));
			});

			cm.setActive('visualaid', ed.hasVisual);
			cm.setDisabled('undo', !ed.undoManager.hasUndo() && !ed.typing);
			cm.setDisabled('redo', !ed.undoManager.hasRedo());
			cm.setDisabled('outdent', !ed.queryCommandState('Outdent'));

			p = DOM.getParent(n, 'A');
			if (c = cm.get('link')) {
				if (!p || !p.name) {
					c.setDisabled(!p && co);
					c.setActive(!!p);
				}
			}

			if (c = cm.get('unlink')) {
				c.setDisabled(!p && co);
				c.setActive(!!p && !p.name);
			}

			if (c = cm.get('anchor')) {
				c.setActive(!!p && p.name);

				if (tinymce.isWebKit) {
					p = DOM.getParent(n, 'IMG');
					c.setActive(!!p && DOM.getAttrib(p, 'mce_name') == 'a');
				}
			}

			p = DOM.getParent(n, 'IMG');
			if (c = cm.get('image'))
				c.setActive(!!p && n.className.indexOf('mceItem') == -1);

			if (c = cm.get('styleselect')) {
				if (n.className) {
					t._importClasses();
					c.select(n.className);
				} else
					c.select();
			}

			if (c = cm.get('formatselect')) {
				p = DOM.getParent(n, DOM.isBlock);

				if (p)
					c.select(p.nodeName.toLowerCase());
			}

			if (c = cm.get('fontselect'))
				c.select(ed.queryCommandValue('FontName'));

			if (c = cm.get('fontsizeselect'))
				c.select(ed.queryCommandValue('FontSize'));

			if (s.theme_advanced_path && s.theme_advanced_statusbar_location) {
				p = DOM.get(ed.id + '_path') || DOM.add(ed.id + '_path_row', 'span', {id : ed.id + '_path'});
				DOM.setHTML(p, '');

				ed.dom.getParent(n, function(n) {
					var na = n.nodeName.toLowerCase(), u, pi, ti = '';

					// Ignore non element and hidden elements
					if (n.nodeType != 1 || (DOM.hasClass(n, 'mceItemHidden') || DOM.hasClass(n, 'mceItemRemoved')))
						return;

					// Fake name
					if (v = DOM.getAttrib(n, 'mce_name'))
						na = v;
	
					// Handle prefix
					if (tinymce.isIE && n.scopeName !== 'HTML')
						na = n.scopeName + ':' + na;

					// Remove internal prefix
					na = na.replace(/mce\:/g, '');

					// Handle node name
					switch (na) {
						case 'b':
							na = 'strong';
							break;

						case 'i':
							na = 'em';
							break;

						case 'img':
							if (v = DOM.getAttrib(n, 'src'))
								ti += 'src: ' + v + ' ';

							break;

						case 'a':
							if (v = DOM.getAttrib(n, 'name')) {
								ti += 'name: ' + v + ' ';
								na += '#' + v;
							}

							if (v = DOM.getAttrib(n, 'href'))
								ti += 'href: ' + v + ' ';

							break;

						case 'font':
							if (s.convert_fonts_to_spans)
								na = 'span';

							if (v = DOM.getAttrib(n, 'face'))
								ti += 'font: ' + v + ' ';

							if (v = DOM.getAttrib(n, 'size'))
								ti += 'size: ' + v + ' ';

							if (v = DOM.getAttrib(n, 'color'))
								ti += 'color: ' + v + ' ';

							break;

						case 'span':
							if (v = DOM.getAttrib(n, 'style'))
								ti += 'style: ' + v + ' ';

							break;
					}

					if (v = DOM.getAttrib(n, 'id'))
						ti += 'id: ' + v + ' ';

					if (v = n.className) {
						v = v.replace(/(webkit-[\w\-]+|Apple-[\w\-]+|mceItem\w+|mceVisualAid)/g, '');

						if (v && v.indexOf('mceItem') == -1) {
							ti += 'class: ' + v + ' ';

							if (DOM.isBlock(n) || na == 'img' || na == 'span')
								na += '.' + v;
						}
					}

					na = na.replace(/(html:)/g, '');
					na = {name : na, node : n, title : ti};
					t.onResolveName.dispatch(t, na);
					ti = na.title;
					na = na.name;

					//u = "javascript:tinymce.EditorManager.get('" + ed.id + "').theme._sel('" + (de++) + "');";
					pi = DOM.create('a', {'href' : "javascript:;", onmousedown : "return false;", title : ti, 'class' : 'mcePath_' + (de++)}, na);

					if (p.hasChildNodes()) {
						p.insertBefore(DOM.doc.createTextNode(' \u00bb '), p.firstChild);
						p.insertBefore(pi, p.firstChild);
					} else
						p.appendChild(pi);
				}, ed.getBody());
			}
		},

		// Commands gets called by execCommand

		_sel : function(v) {
			this.editor.execCommand('mceSelectNodeDepth', false, v);
		},

		_mceInsertAnchor : function(ui, v) {
			var ed = this.editor;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/anchor.htm',
				width : 320 + parseInt(ed.getLang('advanced.anchor_delta_width', 0)),
				height : 90 + parseInt(ed.getLang('advanced.anchor_delta_height', 0)),
				inline : true
			}, {
				theme_url : this.url
			});
		},

		_mceCharMap : function() {
			var ed = this.editor;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/charmap.htm',
				width : 550 + parseInt(ed.getLang('advanced.charmap_delta_width', 0)),
				height : 250 + parseInt(ed.getLang('advanced.charmap_delta_height', 0)),
				inline : true
			}, {
				theme_url : this.url
			});
		},

		_mceHelp : function() {
			var ed = this.editor;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/about.htm',
				width : 480,
				height : 380,
				inline : true
			}, {
				theme_url : this.url
			});
		},

		_mceColorPicker : function(u, v) {
			var ed = this.editor;

			v = v || {};

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/color_picker.htm',
				width : 375 + parseInt(ed.getLang('advanced.colorpicker_delta_width', 0)),
				height : 250 + parseInt(ed.getLang('advanced.colorpicker_delta_height', 0)),
				close_previous : false,
				inline : true
			}, {
				input_color : v.color,
				func : v.func,
				theme_url : this.url
			});
		},

		_mceCodeEditor : function(ui, val) {
			var ed = this.editor;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/source_editor.htm',
				width : parseInt(ed.getParam("theme_advanced_source_editor_width", 720)),
				height : parseInt(ed.getParam("theme_advanced_source_editor_height", 580)),
				inline : true,
				resizable : true,
				maximizable : true
			}, {
				theme_url : this.url
			});
		},

		_mceImage : function(ui, val) {
			var ed = this.editor;

			// Internal image object like a flash placeholder
			if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
				return;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/image.htm',
				width : 355 + parseInt(ed.getLang('advanced.image_delta_width', 0)),
				height : 275 + parseInt(ed.getLang('advanced.image_delta_height', 0)),
				inline : true
			}, {
				theme_url : this.url
			});
		},

		_mceLink : function(ui, val) {
			var ed = this.editor;

			ed.windowManager.open({
				url : tinymce.baseURL + '/themes/advanced/link.htm',
				width : 310 + parseInt(ed.getLang('advanced.link_delta_width', 0)),
				height : 200 + parseInt(ed.getLang('advanced.link_delta_height', 0)),
				inline : true
			}, {
				theme_url : this.url
			});
		},

		_mceNewDocument : function() {
			var ed = this.editor;

			ed.windowManager.confirm('advanced.newdocument', function(s) {
				if (s)
					ed.execCommand('mceSetContent', false, '');
			});
		},

		_mceForeColor : function() {
			var t = this;

			this._mceColorPicker(0, {
				color: t.fgColor,
				func : function(co) {
					t.fgColor = co;
					t.editor.execCommand('ForeColor', false, co);
				}
			});
		},

		_mceBackColor : function() {
			var t = this;

			this._mceColorPicker(0, {
				color: t.bgColor,
				func : function(co) {
					t.bgColor = co;
					t.editor.execCommand('HiliteColor', false, co);
				}
			});
		},

		_ufirst : function(s) {
			return s.substring(0, 1).toUpperCase() + s.substring(1);
		}
	});

	tinymce.ThemeManager.add('advanced', tinymce.themes.AdvancedTheme);
}());