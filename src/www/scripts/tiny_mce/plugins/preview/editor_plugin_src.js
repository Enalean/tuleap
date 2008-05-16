/**
 * $Id: editor_plugin_src.js 537 2008-01-14 16:38:33Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.Preview', {
		init : function(ed, url) {
			var t = this;

			t.editor = ed;

			ed.addCommand('mcePreview', t._preview, t);
			ed.addButton('preview', {title : 'preview.preview_desc', cmd : 'mcePreview'});
		},

		getInfo : function() {
			return {
				longname : 'Preview',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/preview',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Private methods

		_preview : function() {
			var ed = this.editor, win, html, c, pos, pos2, css, i, page = ed.getParam("plugin_preview_pageurl", null), w = ed.getParam("plugin_preview_width", "550"), h = ed.getParam("plugin_preview_height", "600");

			// Use a custom preview page
			if (page) {
				ed.windowManager.open({
					file : ed.getParam("plugin_preview_pageurl", null),
					width : w,
					height : h
				}, {
					resizable : "yes",
					scrollbars : "yes",
					inline : 1
				});
			} else {
				win = window.open("", "mcePreview", "menubar=no,toolbar=no,scrollbars=yes,resizable=yes,left=20,top=20,width=" + w + ",height="  + h);
				html = "";
				c = ed.getContent();
				pos = c.indexOf('<body');
				css = ed.getParam("content_css", '').split(',');

				tinymce.map(css, function(u) {
					return ed.documentBaseURI.toAbsolute(u);
				});

				if (pos != -1) {
					pos = c.indexOf('>', pos);
					pos2 = c.lastIndexOf('</body>');
					c = c.substring(pos + 1, pos2);
				}

				html += ed.getParam('doctype');
				html += '<html xmlns="http://www.w3.org/1999/xhtml">';
				html += '<head>';
				html += '<title>' + ed.getLang('preview.preview_desc') + '</title>';
				html += '<base href="' + ed.documentBaseURI.getURI() + '" />';
				html += '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';

				for (i=0; i<css.length; i++)
					html += '<link href="' + css[i] + '" rel="stylesheet" type="text/css" />';

				html += '</head>';
				html += '<body dir="' + ed.getParam("directionality") + '" onload="window.opener.tinymce.EditorManager.get(\'' + ed.id + '\').plugins[\'preview\']._onLoad(window,document);">';
				html += c;
				html += '</body>';
				html += '</html>';

				win.document.write(html);
				win.document.close();
			}
		},

		_onLoad : function(w, d) {
			var t = this, nl, i, el = [], sv, ne;

			t._doc = d;
			w.writeFlash = t._writeFlash;
			w.writeShockWave = t._writeShockWave;
			w.writeQuickTime = t._writeQuickTime;
			w.writeRealMedia = t._writeRealMedia;
			w.writeWindowsMedia = t._writeWindowsMedia;
			w.writeEmbed = t._writeEmbed;

			nl = d.getElementsByTagName("script");
			for (i=0; i<nl.length; i++) {
				sv = tinymce.isIE ? nl[i].innerHTML : nl[i].firstChild.nodeValue;

				if (new RegExp('write(Flash|ShockWave|WindowsMedia|QuickTime|RealMedia)\\(.*', 'g').test(sv))
					el[el.length] = nl[i];
			}

			for (i=0; i<el.length; i++) {
				ne = d.createElement("div");
				ne.innerHTML = d._embeds[i];
				el[i].parentNode.insertBefore(ne.firstChild, el[i]);
			}
		},

		_writeFlash : function(p) {
			p.src = this.editor.documentBaseURI.toAbsolute(p.src);
			TinyMCE_PreviewPlugin._writeEmbed(
				'D27CDB6E-AE6D-11cf-96B8-444553540000',
				'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0',
				'application/x-shockwave-flash',
				p
			);
		},

		_writeShockWave : function(p) {
			this.editor.documentBaseURI.toAbsolute(p.src);
			TinyMCE_PreviewPlugin._writeEmbed(
				'166B1BCA-3F9C-11CF-8075-444553540000',
				'http://download.macromedia.com/pub/shockwave/cabs/director/sw.cab#version=8,5,1,0',
				'application/x-director',
				p
			);
		},

		_writeQuickTime : function(p) {
			this.editor.documentBaseURI.toAbsolute(p.src);
			TinyMCE_PreviewPlugin._writeEmbed(
				'02BF25D5-8C17-4B23-BC80-D3488ABDDC6B',
				'http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0',
				'video/quicktime',
				p
			);
		},

		_writeRealMedia : function(p) {
			this.editor.documentBaseURI.toAbsolute(p.src);
			TinyMCE_PreviewPlugin._writeEmbed(
				'CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA',
				'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0',
				'audio/x-pn-realaudio-plugin',
				p
			);
		},

		_writeWindowsMedia : function(p) {
			this.editor.documentBaseURI.toAbsolute(p.src);
			p.url = p.src;
			TinyMCE_PreviewPlugin._writeEmbed(
				'6BF52A52-394A-11D3-B153-00C04F79FAA6',
				'http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701',
				'application/x-mplayer2',
				p
			);
		},

		_writeEmbed : function(cls, cb, mt, p) {
			var h = '', n, d = t._doc, ne, c;

			h += '<object classid="clsid:' + cls + '" codebase="' + cb + '"';
			h += typeof(p.id) != "undefined" ? 'id="' + p.id + '"' : '';
			h += typeof(p.name) != "undefined" ? 'name="' + p.name + '"' : '';
			h += typeof(p.width) != "undefined" ? 'width="' + p.width + '"' : '';
			h += typeof(p.height) != "undefined" ? 'height="' + p.height + '"' : '';
			h += typeof(p.align) != "undefined" ? 'align="' + p.align + '"' : '';
			h += '>';

			for (n in p)
				h += '<param name="' + n + '" value="' + p[n] + '">';

			h += '<embed type="' + mt + '"';

			for (n in p)
				h += n + '="' + p[n] + '" ';

			h += '></embed></object>';

			d._embeds[d._embeds.length] = h;
		}
	});

	// Register plugin
	tinymce.PluginManager.add('preview', tinymce.plugins.Preview);
})();