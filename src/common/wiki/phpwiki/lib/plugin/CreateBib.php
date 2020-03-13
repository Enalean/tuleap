<?php
// -*-php-*-
rcs_id('$Id: CreateToc.php,v 1.20 2004/05/11 13:57:46 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * CreateBib:  Automatically create a BibTex file from page
 *
 * Usage:
 *  <?plugin CreateBib pagename||=whatever ?>
 *
 * @author:  Lea Viljanen
 */

class WikiPlugin_CreateBib extends WikiPlugin
{
    public function getName()
    {
        return _("CreateBib");
    }

    public function getDescription()
    {
        return _("Automatically create a Bibtex file from linked pages");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 0.01 $"
        );
    }

    public function getDefaultArguments()
    {
        return array( 'pagename'  => '[pagename]', // The page from which the BibTex file is generated
                      );
    }

    public function preg_quote($heading)
    {
        return str_replace(
            array("/",".","?","*"),
            array('\/','\.','\?','\*'),
            $heading
        );
    }


    // Have to include the $starttag and $endtag to the regexps...
    public function extractBibTeX(&$content, $starttag, $endtag)
    {
        $bib = array();

        $start = false;
        $stop = false;
        for ($i = 0; $i < count($content); $i++) {
            // $starttag shows when to start
            if (preg_match('/^@/', $content[$i], $match)) {
                $start = true;
            } elseif (preg_match('/^\}/', $content[$i], $match)) { // $endtag shows when to stop
                $stop = true;
            }
            if ($start) {
                $bib[] = $content[$i];
                if ($stop) {
                    $start = false;
                }
            }
        }
        return $bib;
    }

    // Extract article links. Current markup is by * characters...
    // Assume straight list
    public function extractArticles(&$content)
    {
        $articles = array();
        for ($i = 0; $i < count($content); $i++) {
            // Should match "* [WikiPageName] whatever"
            //if (preg_match('/^\s*\*\s+(\[.+\])/',$content[$i],$match))
            if (preg_match('/^\s*\*\s+\[(.+)\]/', $content[$i], $match)) {
                $articles[] = $match[1];
            }
        }
        return $articles;
    }


    public function dumpFile(&$thispage, $filename)
    {
        include_once("lib/loadsave.php");
        $mailified = MailifyPage($thispage);

        $attrib = array('mtime' => $thispage->get('mtime'), 'is_ascii' => 1);

        $zip = new ZipWriter("Created by PhpWiki " . PHPWIKI_VERSION, $filename);
        $zip->addRegularFile(
            FilenameForPage($thispage->getName()),
            $mailified,
            $attrib
        );
        $zip->finish();
    }

    public function run($dbi, $argstr, $request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        if ($pagename) {
            // Expand relative page names.
            $page = new WikiPageName($pagename, $basepage);
            $pagename = $page->name;
        }
        if (!$pagename) {
            return $this->error(_("no page specified"));
        }

        // Get the links page contents
        $page = $dbi->getPage($pagename);
        $current = $page->getCurrentRevision();
        $content = $current->getContent();

    // Prepare the button to trigger dumping
        $dump_url = $request->getURLtoSelf(array("file" => "tube.bib"));
        global $WikiTheme;
        $dump_button = $WikiTheme->makeButton(
            "To File",
            $dump_url,
            'foo'
        );

        $html = HTML::div(array('class' => 'bib','align' => 'left'));
        $html->pushContent($dump_button, ' ');
        $list = HTML::pre(array('name' => 'biblist','id' => 'biblist',
        'class' => 'bib'));

        // Let's find the subpages
        if ($articles = $this->extractArticles($content)) {
            foreach ($articles as $h) {
                // Now let's get the bibtex information from that subpage
                $subpage = $dbi->getPage($h);
                $subversion = $subpage->getCurrentRevision();
                $subcontent = $subversion->getContent();

                $bib = $this->extractBibTeX($subcontent, "@", "}");

                // ...and finally just push the bibtex data to page
                $foo = implode("\n", $bib);
                $bar = $foo . "\n\n";
                $list->pushContent(HTML::raw($bar));
            }
        }
        $html->pushContent($list);

        if ($request->getArg('file')) {
            // Yes, we want to dump this somewhere
            // Get the contents of this page
            $p = $dbi->getPage($pagename);
            $c = $p->getCurrentRevision();
            $pagedata = $c->getContent();
            $this->dumpFile($pagedata, $request->getArg('file'));
        }

        return $html;
    }
}

// $Log: CreateBib.php,v $
// Based on CreateTOC

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
