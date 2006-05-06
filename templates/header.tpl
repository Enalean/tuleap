{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *}
{* $contentType = strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') === false ? 'text/html' : 'application/xhtml+xml';
header("Content-Type: $contentType; charset=utf-8"); *}
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<!-- gitphp web interface {$version}, (C) 2006 Christopher Han <xiphux@gmail.com> -->
<head>
<title>{$title}</title>
{literal}<style type="text/css">/*<![CDATA[[*/<!--
 .italic {font-style:italic;}
 .bold {font-weight:bold;}
 .underline {text-decoration:underline;}
body {
	font-family: sans-serif; font-size: 12px; border:solid #d9d8d1; border-width:1px;
	margin:10px; background-color:#ffffff; color:#000000;
}
a { color:#0000cc; }
a:hover, a:visited, a:active { color:#880000; }
div.page_header { height:25px; padding:8px; font-size:18px; font-weight:bold; background-color:#d9d8d1; }
div.page_header a:visited, a.header { color:#0000cc; }
div.page_header a:hover { color:#880000; }
div.page_nav { padding:8px; }
div.page_nav a:visited { color:#0000cc; }
div.page_path { padding:8px; border:solid #d9d8d1; border-width:0px 0px 1px}
div.page_footer { height:17px; padding:4px 8px; background-color: #d9d8d1; }
div.page_footer_text { float:left; color:#555555; font-style:italic; }
div.page_body { padding:8px; }
div.title, a.title {
	display:block; padding:6px 8px;
	font-weight:bold; background-color:#edece6; text-decoration:none; color:#000000;
}
a.title:hover { background-color: #d9d8d1; }
div.title_text { padding:6px 0px; border: solid #d9d8d1; border-width:0px 0px 1px; }
div.log_body { padding:8px 8px 8px 150px; }
span.age { position:relative; float:left; width:142px; font-style:italic; }
div.log_link {
	padding:0px 8px;
	font-size:10px; font-family:sans-serif; font-style:normal;
	position:relative; float:left; width:136px;
}
div.list_head { padding:6px 8px 4px; border:solid #d9d8d1; border-width:1px 0px 0px; font-style:italic; }
a.list { text-decoration:none; color:#000000; }
a.list:hover { text-decoration:underline; color:#880000; }
a.text { text-decoration:none; color:#0000cc; }
a.text:visited { text-decoration:none; color:#880000; }
a.text:hover { text-decoration:underline; color:#880000; }
table { padding:8px 4px; }
th { padding:2px 5px; font-size:12px; text-align:left; }
tr.light:hover { background-color:#edece6; }
tr.dark { background-color:#f6f6f0; }
tr.dark:hover { background-color:#edece6; }
td { padding:2px 5px; font-size:12px; vertical-align:top; }
td.link { padding:2px 5px; font-family:sans-serif; font-size:10px; }
div.pre { font-family:monospace; font-size:12px; white-space:pre; }
div.diff_info { font-family:monospace; color:#000099; background-color:#edece6; font-style:italic; }
div.index_include { border:solid #d9d8d1; border-width:0px 0px 1px; padding:12px 8px; }
div.search { margin:4px 8px; position:absolute; top:56px; right:12px }
a.linenr { color:#999999; text-decoration:none }
a.rss_logo {
	float:right; padding:3px 0px; width:35px; line-height:10px;
	border:1px solid; border-color:#fcc7a5 #7d3302 #3e1a01 #ff954e;
	color:#ffffff; background-color:#ff6600;
	font-weight:bold; font-family:sans-serif; font-size:10px;
	text-align:center; text-decoration:none;
}
a.rss_logo:hover { background-color:#ee5500; }
span.tag {
	padding:0px 4px; font-size:10px; font-weight:normal;
	background-color:#ffffaa; border:1px solid; border-color:#ffffcc #ffee00 #ffee00 #ffffcc;
}
/*]]>*/--></style>{/literal}
{$smarty.capture.header}
</head>
<body>
<div class="page_header">
<a href="http://www.kernel.org/pub/software/scm/git/docs/" title="git documentation">
<img src="images/git-logo.png" width="72" height="27" alt="git" style="float:right; border-width:0px;" />
</a>
<a href="{$SCRIPT_NAME}">projects</a> / 
{if $project}
  <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>
  {if $action}
    / {$action}
  {/if}
{/if}
</div>
