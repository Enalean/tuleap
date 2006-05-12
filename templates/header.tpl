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
{if $rss_link}
<link rel="alternate" title="{$project} log" href="{$SCRIPT_NAME}?p={$project}&a=rss" type="application/rss+xml" />
{/if}
{literal}<style type="text/css">/*<![CDATA[[*/<!--
 .italic {font-style:italic;}
 .bold {font-weight:bold;}
 .underline {text-decoration:underline;}
/*]]>*/--></style>{/literal}
<style type="text/css">
 @import url({$stylesheet});
</style>
{$smarty.capture.header}
</head>
<body>
<div class="page_header">
<a href="http://www.kernel.org/pub/software/scm/git/docs/" title="git documentation">
<img src="git-logo.png" width="72" height="27" alt="git" style="float:right; border-width:0px;" />
</a>
<a href="index.php">projects</a> / 
{if $project}
  <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>
  {if $action}
    / {$action}
  {/if}
{/if}
</div>
