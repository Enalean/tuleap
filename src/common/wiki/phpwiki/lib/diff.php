<?php
// -*-php-*-
rcs_id('$Id: diff.php,v 1.52 2005/04/01 14:45:14 rurban Exp $');
// diff.php
//
// PhpWiki diff output code.
//
// Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
// You may copy this code freely under the conditions of the GPL.
require_once('lib/difflib.php');
require_once('lib/HtmlElement.php');

class _HWLDF_WordAccumulator
{
    public function __construct()
    {
        $this->_lines = array();
        $this->_line = false;
        $this->_group = false;
        $this->_tag = '~begin';
    }

    public function _flushGroup($new_tag)
    {
        if ($this->_group !== false) {
            if (!$this->_line) {
                $this->_line = HTML();
            }
            $this->_line->pushContent($this->_tag
                                      ? new HtmlElement(
                                          $this->_tag,
                                          $this->_group
                                      )
                                      : $this->_group);
        }
        $this->_group = '';
        $this->_tag = $new_tag;
    }

    public function _flushLine($new_tag)
    {
        $this->_flushGroup($new_tag);
        if ($this->_line) {
            $this->_lines[] = $this->_line;
        }
        $this->_line = HTML();
    }

    public function addWords($words, $tag = '')
    {
        if ($tag != $this->_tag) {
            $this->_flushGroup($tag);
        }

        foreach ($words as $word) {
            // new-line should only come as first char of word.
            if (!$word) {
                continue;
            }
            if ($word[0] == "\n") {
                $this->_group .= " ";
                $this->_flushLine($tag);
                $word = substr($word, 1);
            }
            assert(!strstr($word, "\n"));
            $this->_group .= $word;
        }
    }

    public function getLines()
    {
        $this->_flushLine('~done');
        return $this->_lines;
    }
}

class WordLevelDiff extends MappedDiff
{
    public function __construct($orig_lines, $final_lines)
    {
        list ($orig_words, $orig_stripped) = $this->_split($orig_lines);
        list ($final_words, $final_stripped) = $this->_split($final_lines);

        parent::__construct(
            $orig_words,
            $final_words,
            $orig_stripped,
            $final_stripped
        );
    }

    public function _split($lines)
    {
        // FIXME: fix POSIX char class.
        if (!preg_match_all(
            '/ ( [^\S\n]+ | [[:alnum:]]+ | . ) (?: (?!< \n) [^\S\n])? /xs',
            implode("\n", $lines),
            $m
        )) {
            return array(array(''), array(''));
        }
        return array($m[0], $m[1]);
    }

    public function orig()
    {
        $orig = new _HWLDF_WordAccumulator;

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $orig->addWords($edit->orig);
            } elseif ($edit->orig) {
                $orig->addWords($edit->orig, 'del');
            }
        }
        return $orig->getLines();
    }

    public function _final()
    {
        $final = new _HWLDF_WordAccumulator;

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $final->addWords($edit->final);
            } elseif ($edit->final) {
                $final->addWords($edit->final, 'ins');
            }
        }
        return $final->getLines();
    }
}


/**
 * HTML unified diff formatter.
 *
 * This class formats a diff into a CSS-based
 * unified diff format.
 *
 * Within groups of changed lines, diffs are highlit
 * at the character-diff level.
 */
class HtmlUnifiedDiffFormatter extends UnifiedDiffFormatter
{
    public function __construct($context_lines = 4)
    {
        parent::__construct($context_lines);
    }

    public function _start_diff()
    {
        $this->_top = HTML::div(array('class' => 'diff'));
    }
    public function _end_diff()
    {
        $val = $this->_top;
        unset($this->_top);
        return $val;
    }

    public function _start_block($header)
    {
        $this->_block = HTML::div(
            array('class' => 'block'),
            HTML::tt($header)
        );
    }

    public function _end_block()
    {
        $this->_top->pushContent($this->_block);
        unset($this->_block);
    }

    public function _lines($lines, $class, $prefix = false, $elem = false)
    {
        if (!$prefix) {
            $prefix = HTML::raw('&nbsp;');
        }
        $div = HTML::div(array('class' => 'difftext'));
        foreach ($lines as $line) {
            if ($elem) {
                $line = new HtmlElement($elem, $line);
            }
            $div->pushContent(HTML::div(
                array('class' => $class),
                HTML::tt(
                    array('class' => 'prefix'),
                    $prefix
                ),
                $line,
                HTML::raw('&nbsp;')
            ));
        }
        $this->_block->pushContent($div);
    }

    public function _context($lines)
    {
        $this->_lines($lines, 'context');
    }
    public function _deleted($lines)
    {
        $this->_lines($lines, 'deleted', '-', 'del');
    }

    public function _added($lines)
    {
        $this->_lines($lines, 'added', '+', 'ins');
    }

    public function _changed($orig, $final)
    {
        $diff = new WordLevelDiff($orig, $final);
        $this->_lines($diff->orig(), 'original', '-');
        $this->_lines($diff->_final(), 'final', '+');
    }
}

/**
 * HTML table-based unified diff formatter.
 *
 * This class formats a diff into a table-based
 * unified diff format.  (Similar to what was produced
 * by previous versions of PhpWiki.)
 *
 * Within groups of changed lines, diffs are highlit
 * at the character-diff level.
 */
class TableUnifiedDiffFormatter extends HtmlUnifiedDiffFormatter
{
    public function __construct($context_lines = 4)
    {
        parent::__construct($context_lines);
    }

    public function _start_diff()
    {
        $this->_top = HTML::table(array('width' => '100%',
                                        'class' => 'diff',
                                        'cellspacing' => 1,
                                        'cellpadding' => 1,
                                        'border' => 1));
    }

    public function _start_block($header)
    {
        $this->_block = HTML::table(
            array('width' => '100%',
                                          'class' => 'block',
                                          'cellspacing' => 0,
                                          'cellpadding' => 1,
                                          'border' => 0),
            HTML::tr(HTML::td(
                array('colspan' => 2),
                HTML::tt($header)
            ))
        );
    }

    public function _end_block()
    {
        $this->_top->pushContent(HTML::tr(HTML::td($this->_block)));
        unset($this->_block);
    }

    public function _lines($lines, $class, $prefix = false, $elem = false)
    {
        if (!$prefix) {
            $prefix = HTML::raw('&nbsp;');
        }
        $prefix = HTML::td(array('class' => 'prefix',
                                 'width' => "1%"), $prefix);
        foreach ($lines as $line) {
            if (! trim($line)) {
                $line = HTML::raw('&nbsp;');
            } elseif ($elem) {
                $line = new HtmlElement($elem, $line);
            }
            $this->_block->pushContent(HTML::tr(
                array('valign' => 'top'),
                $prefix,
                HTML::td(
                    array('class' => $class),
                    $line
                )
            ));
        }
    }
}


/////////////////////////////////////////////////////////////////

function PageInfoRow($label, $rev, &$request, $is_current = false)
{
    global $WikiTheme;

    $row = HTML::tr(HTML::td(array('align' => 'right'), $label));
    if ($rev) {
        $author = $WikiTheme->getAuthorMessage($rev);
        $dbi = $request->getDbh();

        $version = $rev->getVersion();
        $linked_version = WikiLink($rev, 'existing', $version);
        if ($is_current) {
            $revertbutton = HTML();
        } else {
            $revertbutton = $WikiTheme->makeActionButton(
                array('action' => 'revert',
                                                               'version' => $version),
                false,
                $rev
            );
        }
        $row->pushContent(
            HTML::td(fmt("version %s", $linked_version)),
            HTML::td($WikiTheme->getLastModifiedMessage(
                $rev,
                false
            )),
            HTML::td($author),
            HTML::td($revertbutton)
        );
    } else {
        $row->pushContent(HTML::td(array('colspan' => '4'), _("None")));
    }
    return $row;
}

function showDiff(&$request)
{
    $pagename = $request->getArg('pagename');
    if (is_array($versions = $request->getArg('versions'))) {
        // Version selection from pageinfo.php display:
        rsort($versions);
        list ($version, $previous) = $versions;
    } else {
        $version = $request->getArg('version');
        $previous = $request->getArg('previous');
    }

    // abort if page doesn't exist
    $dbi = $request->getDbh();
    $page = $request->getPage();
    $current = $page->getCurrentRevision();
    if ($current->getVersion() < 1) {
        $html = HTML::div(
            array('id' => 'content'),
            HTML::p(fmt(
                "I'm sorry, there is no such page as %s.",
                WikiLink($pagename, 'unknown')
            ))
        );
        include_once('lib/Template.php');
        GeneratePage($html, sprintf(_("Diff: %s"), $pagename), false);
        return; //early return
    }

    if ($version) {
        if (!($new = $page->getRevision($version))) {
            NoSuchRevision($request, $page, $version);
        }
        $new_version = fmt("version %d", $version);
    } else {
        $new = $current;
        $new_version = _("current version");
    }

    if (preg_match('/^\d+$/', $previous)) {
        if (!($old = $page->getRevision($previous))) {
            NoSuchRevision($request, $page, $previous);
        }
        $old_version = fmt("version %d", $previous);
        $others = array('major', 'minor', 'author');
    } else {
        switch ($previous) {
            case 'author':
                $old = $new;
                while ($old = $page->getRevisionBefore($old)) {
                    if ($old->get('author') != $new->get('author')) {
                        break;
                    }
                }
                $old_version = _("revision by previous author");
                $others = array('major', 'minor');
                break;
            case 'minor':
                $previous = 'minor';
                $old = $page->getRevisionBefore($new);
                $old_version = _("previous revision");
                $others = array('major', 'author');
                break;
            case 'major':
            default:
                $old = $new;
                while ($old && $old->get('is_minor_edit')) {
                    $old = $page->getRevisionBefore($old);
                }
                if ($old) {
                    $old = $page->getRevisionBefore($old);
                }
                $old_version = _("predecessor to the previous major change");
                $others = array('minor', 'author');
                break;
        }
    }

    $new_link = WikiLink($new, '', $new_version);
    $old_link = $old ? WikiLink($old, '', $old_version) : $old_version;
    $page_link = WikiLink($page);

    $html = HTML::div(
        array('id' => 'content'),
        HTML::p(fmt(
            "Differences between %s and %s of %s.",
            $new_link,
            $old_link,
            $page_link
        ))
    );

    $otherdiffs = HTML::p(_("Other diffs:"));
    $label = array('major' => _("Previous Major Revision"),
                   'minor' => _("Previous Revision"),
                   'author' => _("Previous Author"));
    foreach ($others as $other) {
        $args = array('action' => 'diff', 'previous' => $other);
        if ($version) {
            $args['version'] = $version;
        }
        if (count($otherdiffs->getContent()) > 1) {
            $otherdiffs->pushContent(", ");
        } else {
            $otherdiffs->pushContent(" ");
        }
        $otherdiffs->pushContent(Button($args, $label[$other]));
    }
    $html->pushContent($otherdiffs);

    if ($old and $old->getVersion() == 0) {
        $old = false;
    }

    $html->pushContent(HTML::Table(
        PageInfoRow(
            _("Newer page:"),
            $new,
            $request,
            empty($version)
        ),
        PageInfoRow(
            _("Older page:"),
            $old,
            $request,
            false
        )
    ));

    if ($new && $old) {
        $diff = new Diff($old->getContent(), $new->getContent());

        if ($diff->isEmpty()) {
            $html->pushContent(
                HTML::hr(),
                HTML::p(
                    '[',
                    _("Versions are identical"),
                    ']'
                )
            );
        } else {
            // New CSS formatted unified diffs (ugly in NS4).
            $fmt = new HtmlUnifiedDiffFormatter;

            // Use this for old table-formatted diffs.
            //$fmt = new TableUnifiedDiffFormatter;
            $html->pushContent($fmt->format($diff));
        }
    }

    include_once('lib/Template.php');
    GeneratePage($html, sprintf(_("Diff: %s"), $pagename), $new);
}

// $Log: diff.php,v $
// Revision 1.52  2005/04/01 14:45:14  rurban
// fix dirty side-effect: dont printf too early bypassing ob_buffering.
// fixes MSIE.
//
// Revision 1.51  2005/02/04 15:26:57  rurban
// need div=content for blog
//
// Revision 1.50  2005/02/04 13:44:45  rurban
// prevent from php5 nameclash
//
// Revision 1.49  2004/11/21 11:59:19  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.48  2004/06/14 11:31:36  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.47  2004/06/08 13:51:57  rurban
// some comments only
//
// Revision 1.46  2004/05/01 15:59:29  rurban
// nothing changed
//
// Revision 1.45  2004/01/25 03:57:15  rurban
// use isWikiWord()
//
// Revision 1.44  2003/02/17 02:17:31  dairiki
// Fix so that action=diff will work when the most recent version
// of a page has been "deleted".
//
// Revision 1.43  2003/01/29 19:17:37  carstenklapp
// Bugfix for &nbsp showing on diff page.
//
// Revision 1.42  2003/01/11 23:05:04  carstenklapp
// Tweaked diff formatting.
//
// Revision 1.41  2003/01/08 02:23:02  carstenklapp
// Don't perform a diff when the page doesn't exist (such as a
// nonexistant calendar day/sub-page)
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
