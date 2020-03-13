<?php
/* Copyright (C) 2002 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright (C) 2004,2005 Reini Urban
 *
 * This file is part of PhpWiki.
 *
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpWiki; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('lib/HtmlElement.php');
require_once('lib/CachedMarkup.php');
require_once('lib/InlineParser.php');

////////////////////////////////////////////////////////////////
/**
 * Deal with paragraphs and proper, recursive block indents
 * for the new style markup (version 2)
 *
 * Everything which goes over more than line:
 * automatic lists, UL, OL, DL, table, blockquote, verbatim,
 * p, pre, plugin, ...
 *
 * FIXME:
 *  Still to do:
 *    (old-style) tables
 * FIXME: unify this with the RegexpSet in InlineParser.
 *
 * FIXME: This is very php5 sensitive: It was fixed for 1.3.9,
 *        but is again broken with the 1.3.11
 *        allow_call_time_pass_reference clean fixes
 *
 * @author: Geoffrey T. Dairiki
 */

/**
 * Return type from RegexpSet::match and RegexpSet::nextMatch.
 *
 * @see RegexpSet
 */
class AnchoredRegexpSet_match
{
    /**
     * The matched text.
     */
    public $match;

    /**
     * The text following the matched text.
     */
    public $postmatch;

    /**
     * Index of the regular expression which matched.
     */
    public $regexp_ind;
}

/**
 * A set of regular expressions.
 *
 * This class is probably only useful for InlineTransformer.
 */
class AnchoredRegexpSet
{
    /** Constructor
     *
     * @param $regexps array A list of regular expressions.  The
     * regular expressions should not include any sub-pattern groups
     * "(...)".  (Anonymous groups, like "(?:...)", as well as
     * look-ahead and look-behind assertions are fine.)
     */
    public function __construct($regexps)
    {
        $this->_regexps = $regexps;
        $this->_re = "/((" . join(")|(", $regexps) . "))/Ax";
    }

    /**
     * Search text for the next matching regexp from the Regexp Set.
     *
     * @param $text string The text to search.
     *
     * @return object  A RegexpSet_match object, or false if no match.
     */
    public function match($text)
    {
        if (!is_string($text)) {
            return false;
        }
        if (! preg_match($this->_re, $text, $m)) {
            return false;
        }

        $match = new AnchoredRegexpSet_match;
        $match->postmatch = substr($text, strlen($m[0]));
        $match->match = $m[1];
        $match->regexp_ind = count($m) - 3;
        return $match;
    }

    /**
     * Search for next matching regexp.
     *
     * Here, 'next' has two meanings:
     *
     * Match the next regexp(s) in the set, at the same position as the last match.
     *
     * If that fails, match the whole RegexpSet, starting after the position of the
     * previous match.
     *
     * @param $text string Text to search.
     *
     * @param $prevMatch A RegexpSet_match object
     *
     * $prevMatch should be a match object obtained by a previous
     * match upon the same value of $text.
     *
     * @return object  A RegexpSet_match object, or false if no match.
     */
    public function nextMatch($text, $prevMatch)
    {
        // Try to find match at same position.
        $regexps = array_slice($this->_regexps, $prevMatch->regexp_ind + 1);
        if (!$regexps) {
            return false;
        }

        $pat = "/ ( (" . join(')|(', $regexps) . ") ) /Axs";

        if (! preg_match($pat, $text, $m)) {
            return false;
        }

        $match = new AnchoredRegexpSet_match;
        $match->postmatch = substr($text, strlen($m[0]));
        $match->match = $m[1];
        $match->regexp_ind = count($m) - 3 + $prevMatch->regexp_ind + 1;
        return $match;
    }
}



class BlockParser_Input
{
    public function __construct($text)
    {
        // Expand leading tabs.
        // FIXME: do this better.
        //
        // We want to ensure the only characters matching \s are ' ' and "\n".
        $text = preg_replace('/(?![ \n])\s/', ' ', $text);
        assert(!preg_match('/(?![ \n])\s/', $text));

        $this->_lines = preg_split('/[^\S\n]*\n/', $text);
        $this->_pos = 0;

        // Strip leading blank lines.
        while ($this->_lines and ! $this->_lines[0]) {
            array_shift($this->_lines);
        }
        $this->_atSpace = false;
    }

    public function skipSpace()
    {
        $nlines = count($this->_lines);
        while (1) {
            if ($this->_pos >= $nlines) {
                $this->_atSpace = false;
                break;
            }
            if ($this->_lines[$this->_pos] != '') {
                break;
            }
            $this->_pos++;
            $this->_atSpace = true;
        }
        return $this->_atSpace;
    }

    public function currentLine()
    {
        if ($this->_pos >= count($this->_lines)) {
            return false;
        }
        return $this->_lines[$this->_pos];
    }

    public function nextLine()
    {
        $this->_atSpace = $this->_lines[$this->_pos++] === '';
        if ($this->_pos >= count($this->_lines)) {
            return false;
        }
        return $this->_lines[$this->_pos];
    }

    public function advance()
    {
        $this->_atSpace = ($this->_lines[$this->_pos] === '');
        $this->_pos++;
    }

    public function getPos()
    {
        return array($this->_pos, $this->_atSpace);
    }

    public function setPos($pos)
    {
        list($this->_pos, $this->_atSpace) = $pos;
    }

    public function getPrefix()
    {
        return '';
    }

    public function getDepth()
    {
        return 0;
    }

    public function where()
    {
        if ($this->_pos < count($this->_lines)) {
            return $this->_lines[$this->_pos];
        } else {
            return "<EOF>";
        }
    }

    public function _debug($tab, $msg)
    {
        //return ;
        $where = $this->where();
        $tab = str_repeat('____', $this->getDepth()) . $tab;
        printXML(HTML::div("$tab $msg: at: '", HTML::tt($where), "'"));
        flush();
    }
}

class BlockParser_InputSubBlock extends BlockParser_Input
{
    public function __construct(&$input, $prefix_re, $initial_prefix = false)
    {
        $this->_input = &$input;
        $this->_prefix_pat = "/$prefix_re|\\s*\$/Ax";
        $this->_atSpace = false;

        if (($line = $input->currentLine()) === false) {
            $this->_line = false;
        } elseif ($initial_prefix) {
            assert(substr($line, 0, strlen($initial_prefix)) == $initial_prefix);
            $this->_line = (string) substr($line, strlen($initial_prefix));
            $this->_atBlank = ! ltrim($line);
        } elseif (preg_match($this->_prefix_pat, $line, $m)) {
            $this->_line = (string) substr($line, strlen($m[0]));
            $this->_atBlank = ! ltrim($line);
        } else {
            $this->_line = false;
        }
    }

    public function skipSpace()
    {
        // In contrast to the case for top-level blocks,
        // for sub-blocks, there never appears to be any trailing space.
        // (The last block in the sub-block should always be of class tight-bottom.)
        while ($this->_line === '') {
            $this->advance();
        }

        if ($this->_line === false) {
            return $this->_atSpace == 'strong_space';
        } else {
            return $this->_atSpace;
        }
    }

    public function currentLine()
    {
        return $this->_line;
    }

    public function nextLine()
    {
        if ($this->_line === '') {
            $this->_atSpace = $this->_atBlank ? 'weak_space' : 'strong_space';
        } else {
            $this->_atSpace = false;
        }

        $line = $this->_input->nextLine();
        if ($line !== false && preg_match($this->_prefix_pat, $line, $m)) {
            $this->_line = (string) substr($line, strlen($m[0]));
            $this->_atBlank = ! ltrim($line);
        } else {
            $this->_line = false;
        }

        return $this->_line;
    }

    public function advance()
    {
        $this->nextLine();
    }

    public function getPos()
    {
        return array($this->_line, $this->_atSpace, $this->_input->getPos());
    }

    public function setPos($pos)
    {
        $this->_line = $pos[0];
        $this->_atSpace = $pos[1];
        $this->_input->setPos($pos[2]);
    }

    public function getPrefix()
    {
        assert($this->_line !== false);
        $line = $this->_input->currentLine();
        assert($line !== false && strlen($line) >= strlen($this->_line));
        return substr($line, 0, strlen($line) - strlen($this->_line));
    }

    public function getDepth()
    {
        return $this->_input->getDepth() + 1;
    }

    public function where()
    {
        return $this->_input->where();
    }
}


class Block_HtmlElement extends HtmlElement
{
    public function __construct($tag /*, ... */)
    {
        $this->_init(func_get_args());
    }

    public function setTightness($top, $bottom)
    {
        $this->setInClass('tightenable');
        $this->setInClass('top', $top);
        $this->setInClass('bottom', $bottom);
    }
}

class ParsedBlock extends Block_HtmlElement
{
    public function __construct(&$input, $tag = 'div', $attr = false)
    {
        parent::__construct($tag, $attr);
        $this->_initBlockTypes();
        $this->_parse($input);
    }

    public function _parse(&$input)
    {
        // php5 failed to advance the block. php5 copies objects by ref.
        // nextBlock == block, both are the same objects. So we have to clone it.
        for ($block = $this->_getBlock($input); $block; $block = (is_object($nextBlock) ? clone $nextBlock : $nextBlock)) {
            while ($nextBlock = $this->_getBlock($input)) {
                // Attempt to merge current with following block.
                if (! ($merged = $block->merge($nextBlock))) {
                    break;      // can't merge
                }
                $block = $merged;
            }
            $this->pushContent($block->finish());
        }
    }

    // FIXME: hackish. This should only be called once.
    public function _initBlockTypes()
    {
        // better static or global?
        static $_regexpset, $_block_types;

        if (!is_object($_regexpset)) {
            foreach (array('oldlists', 'list', 'dl', 'table_dl',
                           'blockquote', 'heading', 'hr', 'pre', 'email_blockquote',
                           'plugin', 'p') as $type) {
                   $class = "Block_$type";
                   $proto = new $class;
                   $this->_block_types[] = $proto;
                   $this->_regexps[] = $proto->_re;
            }
            $this->_regexpset = new AnchoredRegexpSet($this->_regexps);
            $_regexpset = $this->_regexpset;
            $_block_types = $this->_block_types;
        } else {
             $this->_regexpset = $_regexpset;
             $this->_block_types = $_block_types;
        }
    }

    public function _getBlock(&$input)
    {
        $this->_atSpace = $input->skipSpace();

        $line = $input->currentLine();
        if ($line === false or $line === '') { // allow $line === '0'
            return false;
        }
        $tight_top = !$this->_atSpace;
        $re_set = &$this->_regexpset;
        //FIXME: php5 fails to advance here!
        for ($m = $re_set->match($line); $m; $m = $re_set->nextMatch($line, $m)) {
            $block = clone $this->_block_types[$m->regexp_ind];
            if (DEBUG & _DEBUG_PARSER) {
                $input->_debug('>', get_class($block));
            }

            if ($block->_match($input, $m)) {
                //$block->_text = $line;
                if (DEBUG & _DEBUG_PARSER) {
                    $input->_debug('<', get_class($block));
                }
                $tight_bottom = ! $input->skipSpace();
                $block->_setTightness($tight_top, $tight_bottom);
                return $block;
            }
            if (DEBUG & _DEBUG_PARSER) {
                $input->_debug('[', "_match failed");
            }
        }
        if ($line === false or $line === '') {// allow $line === '0'
            return false;
        }

        trigger_error("Couldn't match block: '$line'", E_USER_NOTICE);
        return false;
    }
}

class WikiText extends ParsedBlock
{
    public function __construct($text)
    {
        $input = new BlockParser_Input($text);
        parent::__construct($input);
    }
}

class SubBlock extends ParsedBlock
{
    public function __construct(
        &$input,
        $indent_re,
        $initial_indent = false,
        $tag = 'div',
        $attr = false
    ) {
        $subinput = new BlockParser_InputSubBlock($input, $indent_re, $initial_indent);
        parent::__construct($subinput, $tag, $attr);
    }
}

/**
 * TightSubBlock is for use in parsing lists item bodies.
 *
 * If the sub-block consists of a single paragraph, it omits
 * the paragraph element.
 *
 * We go to this trouble so that "tight" lists look somewhat reasonable
 * in older (non-CSS) browsers.  (If you don't do this, then, without
 * CSS, you only get "loose" lists.
 */
class TightSubBlock extends SubBlock
{
    public function __construct(
        &$input,
        $indent_re,
        $initial_indent = false,
        $tag = 'div',
        $attr = false
    ) {
        parent::__construct($input, $indent_re, $initial_indent, $tag, $attr);

        // If content is a single paragraph, eliminate the paragraph...
        if (count($this->_content) == 1) {
            $elem = $this->_content[0];
            if (isa($elem, 'XmlElement') and $elem->getTag() == 'p') {
                assert($elem->getAttr('class') == 'tightenable top bottom');
                $this->setContent($elem->getContent());
            }
        }
    }
}

class BlockMarkup
{
    public $_re;

    public function _match(&$input, $match)
    {
        trigger_error('pure virtual', E_USER_ERROR);
    }

    public function _setTightness($top, $bot)
    {
        $this->_element->setTightness($top, $bot);
    }

    public function merge($followingBlock)
    {
        return false;
    }

    public function finish()
    {
        return $this->_element;
    }
}

class Block_blockquote extends BlockMarkup
{
    public $_depth;
    public $_re = '\ +(?=\S)';

    public function _match(&$input, $m)
    {
        $this->_depth = strlen($m->match);
        $indent = sprintf("\\ {%d}", $this->_depth);
        $this->_element = new SubBlock($input, $indent, $m->match, 'blockquote');
        return true;
    }

    public function merge($nextBlock)
    {
        if (get_class($nextBlock) == static::class) {
            assert($nextBlock->_depth < $this->_depth);
            $nextBlock->_element->unshiftContent($this->_element);
            $nextBlock->_tight_top = $this->_tight_top;
            return $nextBlock;
        }
        return false;
    }
}

class Block_list extends BlockMarkup
{
    //var $_tag = 'ol' or 'ul';
    public $_re = '\ {0,4}
                (?: \+
                  | \\# (?!\[.*\])
                  | -(?!-)
                  | [o](?=\ )
                  | [*] (?!(?=\S)[^*]*(?<=\S)[*](?:\\s|[-)}>"\'\\/:.,;!?_*=]) )
                )\ *(?=\S)';
    public $_content = array();

    public function _match(&$input, $m)
    {
        // A list as the first content in a list is not allowed.
        // E.g.:
        //   *  * Item
        // Should markup as <ul><li>* Item</li></ul>,
        // not <ul><li><ul><li>Item</li></ul>/li></ul>.
        if (preg_match('/[*#+-o]/', $input->getPrefix())) {
            return false;
        }

        $prefix = $m->match;
        $indent = sprintf("\\ {%d}", strlen($prefix));

        $bullet = trim($m->match);
        $this->_tag = $bullet == '#' ? 'ol' : 'ul';
        $this->_content[] = new TightSubBlock($input, $indent, $m->match, 'li');
        return true;
    }

    public function _setTightness($top, $bot)
    {
        $li = &$this->_content[0];
        $li->setTightness($top, $bot);
    }

    public function merge($nextBlock)
    {
        if (isa($nextBlock, 'Block_list') and $this->_tag == $nextBlock->_tag) {
            if ($nextBlock->_content === $this->_content) {
                trigger_error("Internal Error: no block advance", E_USER_NOTICE);
                return false;
            }
            array_splice($this->_content, count($this->_content), 0, $nextBlock->_content);
            return $this;
        }
        return false;
    }

    public function finish()
    {
        return new Block_HtmlElement($this->_tag, false, $this->_content);
    }
}

class Block_dl extends Block_list
{
    public $_tag = 'dl';

    public function __construct()
    {
        $this->_re = '\ {0,4}\S.*(?<!' . ESCAPE_CHAR . '):\s*$';
    }

    public function _match(&$input, $m)
    {
        if (!($p = $this->_do_match($input, $m))) {
            return false;
        }
        list ($term, $defn, $loose) = $p;

        $this->_content[] = new Block_HtmlElement('dt', false, $term);
        $this->_content[] = $defn;
        $this->_tight_defn = !$loose;
        return true;
    }

    public function _setTightness($top, $bot)
    {
        $dt = &$this->_content[0];
        $dd = &$this->_content[1];

        $dt->setTightness($top, $this->_tight_defn);
        $dd->setTightness($this->_tight_defn, $bot);
    }

    public function _do_match(&$input, $m)
    {
        $pos = $input->getPos();

        $firstIndent = strspn($m->match, ' ');
        $pat = sprintf('/\ {%d,%d}(?=\s*\S)/A', $firstIndent + 1, $firstIndent + 5);

        $input->advance();
        $loose = $input->skipSpace();
        $line = $input->currentLine();

        if (!$line || !preg_match($pat, $line, $mm)) {
            $input->setPos($pos);
            return false;       // No body found.
        }

        $indent = strlen($mm[0]);
        $term = TransformInline(rtrim(substr(trim($m->match), 0, -1)));
        $defn = new TightSubBlock($input, sprintf("\\ {%d}", $indent), false, 'dd');
        return array($term, $defn, $loose);
    }
}



class Block_table_dl_defn extends XmlContent
{
    public $nrows;
    public $ncols;

    public function __construct($term, $defn)
    {
        parent::__construct();
        if (!is_array($defn)) {
            $defn = $defn->getContent();
        }

        $this->_next_tight_top = false; // value irrelevant - gets fixed later
        $this->_ncols = $this->_ComputeNcols($defn);
        $this->_nrows = 0;

        foreach ($defn as $item) {
            if ($this->_IsASubtable($item)) {
                $this->_addSubtable($item);
            } else {
                $this->_addToRow($item);
            }
        }
        $this->_flushRow();

        $th = HTML::th($term);
        if ($this->_nrows > 1) {
            $th->setAttr('rowspan', $this->_nrows);
        }
        $this->_setTerm($th);
    }

    public function setTightness($tight_top, $tight_bot)
    {
        $this->_tight_top = $tight_top;
        $this->_tight_bot = $tight_bot;
        $first = &$this->firstTR();
        $last  = &$this->lastTR();
        $first->setInClass('top', $tight_top);
        if (!empty($last)) {
            $last->setInClass('bottom', $tight_bot);
        } else {
            trigger_error(sprintf("no lastTR: %s", AsXML($this->_content[0])), E_USER_WARNING);
        }
    }

    public function _addToRow($item)
    {
        if (empty($this->_accum)) {
            $this->_accum = HTML::td();
            if ($this->_ncols > 2) {
                $this->_accum->setAttr('colspan', $this->_ncols - 1);
            }
        }
        $this->_accum->pushContent($item);
    }

    public function _flushRow($tight_bottom = false)
    {
        if (!empty($this->_accum)) {
            $row = new Block_HtmlElement('tr', false, $this->_accum);

            $row->setTightness($this->_next_tight_top, $tight_bottom);
            $this->_next_tight_top = $tight_bottom;

            $this->pushContent($row);
            $this->_accum = false;
            $this->_nrows++;
        }
    }

    public function _addSubtable($table)
    {
        if (!($table_rows = $table->getContent())) {
            return;
        }

        $this->_flushRow($table_rows[0]->_tight_top);

        foreach ($table_rows as $subdef) {
            $this->pushContent($subdef);
            $this->_nrows += $subdef->nrows();
            $this->_next_tight_top = $subdef->_tight_bot;
        }
    }

    public function _setTerm($th)
    {
        $first_row = &$this->_content[0];
        if (isa($first_row, 'Block_table_dl_defn')) {
            $first_row->_setTerm($th);
        } else {
            $first_row->unshiftContent($th);
        }
    }

    public function _ComputeNcols($defn)
    {
        $ncols = 2;
        foreach ($defn as $item) {
            if ($this->_IsASubtable($item)) {
                $row = $this->_FirstDefn($item);
                $ncols = max($ncols, $row->ncols() + 1);
            }
        }
        return $ncols;
    }

    public function _IsASubtable($item)
    {
        return isa($item, 'HtmlElement')
            && $item->getTag() == 'table'
            && $item->getAttr('class') == 'wiki-dl-table';
    }

    public function _FirstDefn($subtable)
    {
        $defs = $subtable->getContent();
        return $defs[0];
    }

    public function ncols()
    {
        return $this->_ncols;
    }

    public function nrows()
    {
        return $this->_nrows;
    }

    public function & firstTR()
    {
        $first = &$this->_content[0];
        if (isa($first, 'Block_table_dl_defn')) {
            return $first->firstTR();
        }
        return $first;
    }

    public function & lastTR()
    {
        $last = &$this->_content[$this->_nrows - 1];
        if (isa($last, 'Block_table_dl_defn')) {
            return $last->lastTR();
        }
        return $last;
    }

    public function setWidth($ncols)
    {
        assert($ncols >= $this->_ncols);
        if ($ncols <= $this->_ncols) {
            return;
        }
        $rows = &$this->_content;
        for ($i = 0; $i < count($rows); $i++) {
            $row = &$rows[$i];
            if (isa($row, 'Block_table_dl_defn')) {
                $row->setWidth($ncols - 1);
            } else {
                $n = count($row->_content);
                $lastcol = &$row->_content[$n - 1];
                if (!empty($lastcol)) {
                    $lastcol->setAttr('colspan', $ncols - 1);
                }
            }
        }
    }
}

class Block_table_dl extends Block_dl
{
    public $_tag = 'dl-table';     // phony.

    public function __construct()
    {
        $this->_re = '\ {0,4} (?:\S.*)? (?<!' . ESCAPE_CHAR . ') \| \s* $';
    }

    public function _match(&$input, $m)
    {
        if (!($p = $this->_do_match($input, $m))) {
            return false;
        }
        list ($term, $defn, $loose) = $p;

        $this->_content[] = new Block_table_dl_defn($term, $defn);
        return true;
    }

    public function _setTightness($top, $bot)
    {
        $this->_content[0]->setTightness($top, $bot);
    }

    public function finish()
    {
        $defs = &$this->_content;

        $ncols = 0;
        foreach ($defs as $defn) {
            $ncols = max($ncols, $defn->ncols());
        }

        foreach ($defs as $key => $defn) {
            $defs[$key]->setWidth($ncols);
        }

        return HTML::table(
            array('class' => 'wiki-dl-table', 'border' => 1, 'cellspacing' => 0, 'cellpadding' => 6),
            $defs
        );
    }
}

class Block_oldlists extends Block_list
{
    //var $_tag = 'ol', 'ul', or 'dl';
    public $_re = '(?: [*] (?!(?=\S)[^*]*(?<=\S)[*](?:\\s|[-)}>"\'\\/:.,;!?_*=]))
                  | [#] (?! \[ .*? \] )
                  | ; .*? :
                ) .*? (?=\S)';

    public function _match(&$input, $m)
    {
        // FIXME:
        if (!preg_match('/[*#;]*$/A', $input->getPrefix())) {
            return false;
        }

        $prefix = $m->match;
        $oldindent = '[*#;](?=[#*]|;.*:.*\S)';
        $newindent = sprintf('\\ {%d}', strlen($prefix));
        $indent = "(?:$oldindent|$newindent)";

        $bullet = $prefix[0];
        if ($bullet == '*') {
            $this->_tag = 'ul';
            $itemtag = 'li';
        } elseif ($bullet == '#') {
            $this->_tag = 'ol';
            $itemtag = 'li';
        } else {
            $this->_tag = 'dl';
            list ($term,) = explode(':', substr($prefix, 1), 2);
            $term = trim($term);
            if ($term) {
                $this->_content[] = new Block_HtmlElement('dt', false, TransformInline($term));
            }
            $itemtag = 'dd';
        }

        $this->_content[] = new TightSubBlock($input, $indent, $m->match, $itemtag);
        return true;
    }

    public function _setTightness($top, $bot)
    {
        if (count($this->_content) == 1) {
            $li = &$this->_content[0];
            $li->setTightness($top, $bot);
        } else {
            // This is where php5 usually brakes.
            // wrong duplicated <li> contents
            if (DEBUG and DEBUG & _DEBUG_PARSER) {
                if (count($this->_content) != 2) {
                    echo "<pre>";
                    /*
                    $class = new Reflection_Class('XmlElement');
                    // Print out basic information
                    printf(
                           "===> The %s%s%s %s '%s' [extends %s]\n".
                           "     declared in %s\n".
                           "     lines %d to %d\n".
                           "     having the modifiers %d [%s]\n",
                           $class->isInternal() ? 'internal' : 'user-defined',
                           $class->isAbstract() ? ' abstract' : '',
                           $class->isFinal() ? ' final' : '',
                           $class->isInterface() ? 'interface' : 'class',
                           $class->getName(),
                           var_export($class->getParentClass(), 1),
                           $class->getFileName(),
                           $class->getStartLine(),
                           $class->getEndline(),
                           $class->getModifiers(),
                           implode(' ', Reflection::getModifierNames($class->getModifiers()))
                           );
                    // Print class properties
                    printf("---> Properties: %s\n", var_export($class->getProperties(), 1));
                    */
                    echo 'count($this->_content): ', count($this->_content),"\n";
                    echo "\$this->_content[0]: ";
                    var_dump($this->_content[0]);

                    for ($i = 1; $i < min(5, count($this->_content)); $i++) {
                        $c = $this->_content[$i];
                        echo '$this->_content[',$i,"]: \n";
                        echo "_tag: ";
                        var_dump($c->_tag);
                        echo "_content: ";
                        var_dump($c->_content);
                        echo "_properties: ";
                        var_dump($c->_properties);
                    }
                    echo "</pre>";
                }
            }
            $dt = &$this->_content[0];
            $dd = &$this->_content[1];
            $dt->setTightness($top, false);
            $dd->setTightness(false, $bot);
        }
    }
}

class Block_pre extends BlockMarkup
{
    public $_re = '<(?:pre|verbatim)>';

    public function _match(&$input, $m)
    {
        $endtag = '</' . substr($m->match, 1);
        $text = array();
        $pos = $input->getPos();

        $line = $m->postmatch;
        while (ltrim($line) != $endtag) {
            $text[] = $line;
            if (($line = $input->nextLine()) === false) {
                $input->setPos($pos);
                return false;
            }
        }
        $input->advance();

        $text = join("\n", $text);

        // FIXME: no <img>, <big>, <small>, <sup>, or <sub>'s allowed
        // in a <pre>.
        if ($m->match == '<pre>') {
            $text = TransformInline($text);
        }

        $this->_element = new Block_HtmlElement('pre', false, $text);
        return true;
    }
}

class Block_plugin extends Block_pre
{
    public $_re = '<\?plugin(?:-form)?(?!\S)';

    // FIXME:
    /* <?plugin Backlinks
     *       page=ThisPage ?>
    /* <?plugin ListPages pages=<!plugin-list Backlinks!>
     *                    exclude=<!plugin-list TitleSearch s=T*!> ?>
     *
     * should all work.
     */
    public function _match(&$input, $m)
    {
        $pos = $input->getPos();
        $pi = $m->match . $m->postmatch;
        while (!preg_match('/(?<!' . ESCAPE_CHAR . ')\?>\s*$/', $pi)) {
            if (($line = $input->nextLine()) === false) {
                $input->setPos($pos);
                return false;
            }
            $pi .= "\n$line";
        }
        $input->advance();

        $this->_element = new Cached_PluginInvocation($pi);
        return true;
    }
}

class Block_email_blockquote extends BlockMarkup
{
    public $_attr = array('class' => 'mail-style-quote');
    public $_re = '>\ ?';

    public function _match(&$input, $m)
    {
        //$indent = str_replace(' ', '\\ ', $m->match) . '|>$';
        $indent = $this->_re;
        $this->_element = new SubBlock($input, $indent, $m->match, 'blockquote', $this->_attr);
        return true;
    }
}

class Block_hr extends BlockMarkup
{
    public $_re = '-{4,}\s*$';

    public function _match(&$input, $m)
    {
        $input->advance();
        $this->_element = new Block_HtmlElement('hr');
        return true;
    }

    public function _setTightness($top, $bot)
    {
    // Don't tighten <hr/>s
    }
}

class Block_heading extends BlockMarkup
{
    public $_re = '!{1,3}';

    public function _match(&$input, $m)
    {
        $tag = "h" . (5 - strlen($m->match));
        $text = TransformInline(trim($m->postmatch));
        $input->advance();

        $this->_element = new Block_HtmlElement($tag, false, $text);

        return true;
    }

    public function _setTightness($top, $bot)
    {
    // Don't tighten headers.
    }
}

class Block_p extends BlockMarkup
{
    public $_tag = 'p';
    public $_re = '\S.*';
    public $_text = '';

    public function _match(&$input, $m)
    {
        $this->_text = $m->match;
        $input->advance();
        return true;
    }

    public function _setTightness($top, $bot)
    {
        $this->_tight_top = $top;
        $this->_tight_bot = $bot;
    }

    public function merge($nextBlock)
    {
        $class = get_class($nextBlock);
        if (strtolower($class) == 'block_p' and $this->_tight_bot) {
            $this->_text .= "\n" . $nextBlock->_text;
            $this->_tight_bot = $nextBlock->_tight_bot;
            return $this;
        }
        return false;
    }

    public function finish()
    {
        $content = TransformInline(trim($this->_text));
        $p = new Block_HtmlElement('p', false, $content);
        $p->setTightness($this->_tight_top, $this->_tight_bot);
        return $p;
    }
}

////////////////////////////////////////////////////////////////
/**
 * Transform the text of a page, and return a parse tree.
 */
function TransformTextPre($text, $markup = 2.0, $basepage = false)
{
    if (isa($text, 'WikiDB_PageRevision')) {
        $rev = $text;
        $text = $rev->getPackedContent();
        $markup = $rev->get('markup');
    }
    // NEW: default markup is new, to increase stability
    if (!empty($markup) && $markup < 2.0) {
        $text = ConvertOldMarkup($text);
    }

    // Expand leading tabs.
    $text = expand_tabs($text);
    //set_time_limit(3);
    $output = new WikiText($text);

    return $output;
}

/**
 * Transform the text of a page, and return an XmlContent,
 * suitable for printXml()-ing.
 */
function TransformText($text, $markup = 2.0, $basepage = false)
{
    $output = TransformTextPre($text, $markup, $basepage);
    if ($basepage) {
        // This is for immediate consumption.
        // We must bind the contents to a base pagename so that
        // relative page links can be properly linkified...
        return new CacheableMarkup($output->getContent(), $basepage);
    }
    return new XmlContent($output->getContent());
}
// $Log: BlockParser.php,v $
// Revision 1.55  2005/01/29 21:08:41  rurban
// update (C)
//
// Revision 1.54  2005/01/29 21:00:54  rurban
// do not warn on empty nextBlock
//
// Revision 1.53  2005/01/29 20:36:44  rurban
// very important php5 fix! clone objects
//
// Revision 1.52  2004/10/21 19:52:10  rurban
// Patch #994487: Allow callers to get the parse tree for a page (danfr)
//
// Revision 1.51  2004/09/14 09:54:04  rurban
// cache ParsedBlock::_initBlockTypes
//
// Revision 1.50  2004/09/08 13:38:00  rurban
// improve loadfile stability by using markup=2 as default for undefined markup-style.
// use more refs for huge objects.
// fix debug=static issue in WikiPluginCached
//
// Revision 1.49  2004/07/02 09:55:58  rurban
// more stability fixes: new DISABLE_GETIMAGESIZE if your php crashes when loading LinkIcons: failing getimagesize in old phps; blockparser stabilized
//
// Revision 1.48  2004/06/21 06:30:16  rurban
// revert to prev references
//
// Revision 1.47  2004/06/20 15:30:04  rurban
// get_class case-sensitivity issues
//
// Revision 1.46  2004/06/20 14:42:53  rurban
// various php5 fixes (still broken at blockparser)
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
