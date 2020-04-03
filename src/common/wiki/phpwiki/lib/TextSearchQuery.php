<?php
/**
 * A text search query, converting queries to PCRE and SQL matchers.
 *
 * This represents an enhanced "Google-like" text search query:
 * <dl>
 * <dt> default: case-insensitive glob-style search with special operators OR AND NOT -
 * <dt> wiki -test
 *   <dd> Match strings containing the substring 'wiki', and NOT containing the
 *        substring 'test'.
 * <dt> wiki word or page
 *   <dd> Match strings containing the substring 'wiki' AND either the substring
 *        'word' OR the substring 'page'.
 * <dt> auto-detect regex hints, glob-style or regex-style, and converts them
 *      to PCRE and SQL matchers:
 *   <dd> "^word$" => EXACT(word)
 *   <dd> "^word"  => STARTS_WITH(word)
 *   <dd> "word*"  => STARTS_WITH(word)
 *   <dd> "*word"  => ENDS_WITH(word)
 *   <dd> "/^word.* /" => REGEX(^word.*)
 *   <dd> "word*word" => REGEX(word.*word)
 * </dl>
 *
 * The full query syntax, in order of precedence, is roughly:
 *
 * The unary 'NOT' or '-' operator (they are equivalent) negates the
 * following search clause.
 *
 * Search clauses may be joined with the (left-associative) binary operators
 * 'AND' and 'OR'. (case-insensitive)
 *
 * Two adjoining search clauses are joined with an implicit 'AND'.  This has
 * lower precedence than either an explicit 'AND' or 'OR', so "a b OR c"
 * parses as "a AND ( b OR c )", while "a AND b OR c" parses as
 * "( a AND b ) OR c" (due to the left-associativity of 'AND' and 'OR'.)
 *
 * Search clauses can be grouped with parentheses.
 *
 * Phrases (or other things which don't look like words) can be forced to
 * be interpreted as words by quoting them, either with single (') or double (")
 * quotes.  If you wan't to include the quote character within a quoted string,
 * double-up on the quote character: 'I''m hungry' is equivalent to
 * "I'm hungry".
 *
 * Force regex on "re:word" => posix-style, "/word/" => pcre-style
 * or use regex='glob' to use file wildcard-like matching. (not yet)
 *
 * The parsed tree is then converted to the needed PCRE (highlight,
 * simple backends) or SQL functions.
 *
 * @author: Jeff Dairiki
 * @author: Reini Urban (case and regex detection, enhanced sql callbacks)
 */

// regex-style: 'auto', 'none', 'glob', 'posix', 'pcre', 'sql'
define('TSQ_REGEX_NONE', 0);
define('TSQ_REGEX_AUTO', 1);
define('TSQ_REGEX_POSIX', 2);
define('TSQ_REGEX_GLOB', 4);
define('TSQ_REGEX_PCRE', 8);
define('TSQ_REGEX_SQL', 16);

class TextSearchQuery
{
    /**
     * Create a new query.
     *
     * @param $search_query string The query.  Syntax is as described above.
     * Note that an empty $search_query will match anything.
     * @param $case_exact boolean
     * @param $regex string one of 'auto', 'none', 'glob', 'posix', 'pcre', 'sql'
     * @see TextSearchQuery
     */
    public function __construct($search_query, $case_exact = false, $regex = 'auto')
    {
        if ($regex == 'none' or !$regex) {
            $this->_regex = 0;
        } elseif (defined("TSQ_REGEX_" . strtoupper($regex))) {
            $this->_regex = constant("TSQ_REGEX_" . strtoupper($regex));
        } else {
            trigger_error(fmt("Unsupported argument: %s=%s", 'regex', $regex));
            $this->_regex = 0;
        }
        $this->_case_exact = $case_exact;
        $parser = new TextSearchQuery_Parser();
        $this->_tree = $parser->parse($search_query, $case_exact, $this->_regex);
        $this->_optimize(); // broken under certain circumstances: "word -word -word"
        $this->_stoplist = '(A|An|And|But|By|For|From|In|Is|It|Of|On|Or|The|To|With)';
    }

    public function _optimize()
    {
        $this->_tree = $this->_tree->optimize();
    }

    /**
     * Get a PCRE regexp which matches the query.
     */
    public function asRegexp()
    {
        if (!isset($this->_regexp)) {
            if ($this->_regex) {
                $this->_regexp =  '/' . $this->_tree->regexp() . '/' . ($this->_case_exact ? '' : 'i') . 'sS';
            } else {
                $this->_regexp =  '/^' . $this->_tree->regexp() . '/' . ($this->_case_exact ? '' : 'i') . 'sS';
            }
        }
        return $this->_regexp;
    }

    /**
     * Match query against string.
     *
     * @param $string string The string to match.
     * @return bool True if the string matches the query.
     */
    public function match($string)
    {
        return preg_match($this->asRegexp(), $string);
    }


    /**
     * Get a regular expression suitable for highlighting matched words.
     *
     * This returns a PCRE regular expression which matches any non-negated
     * word in the query.
     *
     * @return string The PCRE regexp.
     */
    public function getHighlightRegexp()
    {
        if (!isset($this->_hilight_regexp)) {
            $words = array_unique($this->_tree->highlight_words());
            if (!$words) {
                $this->_hilight_regexp = false;
            } else {
                foreach ($words as $key => $word) {
                    $words[$key] = preg_quote($word, '/');
                }
                $this->_hilight_regexp = '(?:' . join('|', $words) . ')';
            }
        }
        return $this->_hilight_regexp;
    }

    /**
     * Make an SQL clause which matches the query. (deprecated, use makeSqlClause instead)
     *
     * @param $make_sql_clause_cb WikiCallback
     * A callback which takes a single word as an argument and
     * returns an SQL clause which will match exactly those records
     * containing the word.  The word passed to the callback will always
     * be in all lower case.
     *
     * TODO: support db-specific extensions, like MATCH AGAINST or REGEX
     *       mysql => 4.0.1 can also do Google: MATCH AGAINST IN BOOLEAN MODE
     *       How? WikiDB backend method?
     *
     * Old example usage:
     * <pre>
     *     function sql_title_match($word) {
     *         return sprintf("LOWER(title) like '%s'",
     *                        addslashes($word));
     *     }
     *
     *     ...
     *
     *     $query = new TextSearchQuery("wiki -page");
     *     $cb = new WikiFunctionCb('sql_title_match');
     *     $sql_clause = $query->makeSqlClause($cb);
     * </pre>
     * This will result in $sql_clause containing something like
     * "(LOWER(title) like 'wiki') AND NOT (LOWER(title) like 'page')".
     *
     * @return string The SQL clause.
     */
    public function makeSqlClause($sql_clause_cb)
    {
        $this->_sql_clause_cb = $sql_clause_cb;
        return $this->_sql_clause($this->_tree);
    }
    // deprecated: use _sql_clause_obj now.
    public function _sql_clause($node)
    {
        switch ($node->op) {
            case 'WORD':        // word => %word%
                return $this->_sql_clause_cb->call($node->word);
            case 'NOT':
                return "NOT (" . $this->_sql_clause($node->leaves[0]) . ")";
            case 'AND':
            case 'OR':
                $subclauses = array();
                foreach ($node->leaves as $leaf) {
                    $subclauses[] = "(" . $this->_sql_clause($leaf) . ")";
                }
                return join(" $node->op ", $subclauses);
            default:
                assert($node->op == 'VOID');
                return '1=1';
        }
    }

    /** Get away with the callback and use a db-specific search class instead.
     * @see WikiDB_backend_PearDB_search
     */
    public function makeSqlClauseObj(&$sql_search_cb)
    {
        $this->_sql_clause_cb = $sql_search_cb;
        return $this->_sql_clause_obj($this->_tree);
    }

    public function _sql_clause_obj($node)
    {
        switch ($node->op) {
            case 'NOT':
                return "NOT (" . $this->_sql_clause_cb->call($node->leaves[0]) . ")";
            case 'AND':
            case 'OR':
                $subclauses = array();
                foreach ($node->leaves as $leaf) {
                    $subclauses[] = "(" . $this->_sql_clause_obj($leaf) . ")";
                }
                return join(" $node->op ", $subclauses);
            case 'VOID':
                return '0=1';
            case 'ALL':
                return '1=1';
            default:
                return $this->_sql_clause_cb->call($node);
        }
    }

    public function sql()
    {
        return '%' . $this->_sql_quote($this->word) . '%';
    }

    /**
     * Get printable representation of the parse tree.
     *
     * This is for debugging only.
     * @return string Printable parse tree.
     */
    public function asString()
    {
        return $this->_as_string($this->_tree);
    }

    public function _as_string($node, $indent = '')
    {
        switch ($node->op) {
            case 'WORD':
                return $indent . "WORD: $node->word";
            case 'VOID':
                return $indent . "VOID";
            case 'ALL':
                return $indent . "ALL";
            default:
                $lines = array($indent . $node->op . ":");
                $indent .= "  ";
                foreach ($node->leaves as $leaf) {
                    $lines[] = $this->_as_string($leaf, $indent);
                }
                return join("\n", $lines);
        }
    }
}

/**
 * This is a TextSearchQuery which matches nothing.
 */
class NullTextSearchQuery extends TextSearchQuery
{
    /**
     * Create a new query.
     *
     * @see TextSearchQuery
     */
    public function __construct()
    {
    }
    public function asRegexp()
    {
        return '/^(?!a)a/x';
    }
    public function match($string)
    {
        return false;
    }
    public function getHighlightRegexp()
    {
        return "";
    }
    public function makeSqlClause($make_sql_clause_cb)
    {
        return "(1 = 0)";
    }
    public function asString()
    {
        return "NullTextSearchQuery";
    }
}


////////////////////////////////////////////////////////////////
//
// Remaining classes are private.
//
////////////////////////////////////////////////////////////////
/**
 * Virtual base class for nodes in a TextSearchQuery parse tree.
 *
 * Also serves as a 'VOID' (contentless) node.
 */
class TextSearchQuery_node
{
    public $op = 'VOID';

    /**
     * Optimize this node.
     * @return object Optimized node.
     */
    public function optimize()
    {
        return $this;
    }

    /**
     * @return regexp matching this node.
     */
    public function regexp()
    {
        return '';
    }

    /**
     * @param bool True if this node has been negated (higher in the parse tree.)
     * @return array A list of all non-negated words contained by this node.
     */
    public function highlight_words($negated = false)
    {
        return array();
    }

    public function sql()
    {
        return $this->word;
    }
}

/**
 * A word.
 */
class TextSearchQuery_node_word extends TextSearchQuery_node
{
    public $op = "WORD";

    public function __construct($word)
    {
        $this->word = $word;
    }
    public function regexp()
    {
        return '(?=.*' . preg_quote($this->word, '/') . ')';
    }
    public function highlight_words($negated = false)
    {
        return $negated ? array() : array($this->word);
    }
    public function _sql_quote()
    {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $this->word);
        return $GLOBALS['request']->_dbi->qstr($word);
    }
    public function sql()
    {
        return '%' . $this->_sql_quote($this->word) . '%';
    }
}

class TextSearchQuery_node_all extends TextSearchQuery_node
{
    public $op = "ALL";
    public function regexp()
    {
        return '(?=.*)';
    }
    public function sql()
    {
        return '%';
    }
}
class TextSearchQuery_node_starts_with extends TextSearchQuery_node_word
{
    public $op = "STARTS_WITH";
    public function regexp()
    {
        return '(?=.*\b' . preg_quote($this->word, '/') . ')';
    }
    public function sql()
    {
        return $this->_sql_quote($this->word) . '%';
    }
}

class TextSearchQuery_node_ends_with extends TextSearchQuery_node_word
{
    public $op = "ENDS_WITH";
    public function regexp()
    {
        return '(?=.*' . preg_quote($this->word, '/') . '\b)';
    }
    public function sql()
    {
        return '%' . $this->_sql_quote($this->word);
    }
}

class TextSearchQuery_node_exact extends TextSearchQuery_node_word
{
    public $op = "EXACT";
    public function regexp()
    {
        return '(?=\b' . preg_quote($this->word, '/') . '\b)';
    }
    public function sql()
    {
        return $this->_sql_squote($this->word);
    }
}

class TextSearchQuery_node_regex extends TextSearchQuery_node_word
{
 // posix regex. FIXME!
    public $op = "REGEX"; // using REGEXP or ~ extension
    public function regexp()
    {
        return '(?=.*\b' . $this->word . '\b)';
    }
    public function sql()
    {
        return $this->_sql_quote($this->word);
    }
}

class TextSearchQuery_node_regex_glob extends TextSearchQuery_node_regex
{
    public $op = "REGEX_GLOB";
    public function regexp()
    {
        return '(?=.*\b' . glob_to_pcre($this->word) . '\b)';
    }
}

class TextSearchQuery_node_regex_pcre extends TextSearchQuery_node_regex
{
 // how to handle pcre modifiers? /i
    public $op = "REGEX_PCRE";
    public function regexp()
    {
        return $this->word;
    }
}

class TextSearchQuery_node_regex_sql extends TextSearchQuery_node_regex
{
    public $op = "REGEX_SQL"; // using LIKE
    public function regexp()
    {
        return str_replace(array("/%/","/_/"), array(".*","."), $this->word);
    }
    public function sql()
    {
        return $this->word;
    }
}

/**
 * A negated clause.
 */
class TextSearchQuery_node_not extends TextSearchQuery_node
{
    public $op = "NOT";

    public function __construct($leaf)
    {
        $this->leaves = array($leaf);
    }

    public function optimize()
    {
        $leaf = &$this->leaves[0];
        $leaf = $leaf->optimize();
        if ($leaf->op == 'NOT') {
            return $leaf->leaves[0]; // ( NOT ( NOT x ) ) -> x
        }
        return $this;
    }

    public function regexp()
    {
        $leaf = &$this->leaves[0];
        return '(?!' . $leaf->regexp() . ')';
    }

    public function highlight_words($negated = false)
    {
        return $this->leaves[0]->highlight_words(!$negated);
    }
}

/**
 * Virtual base class for 'AND' and 'OR conjoins.
 */
class TextSearchQuery_node_binop extends TextSearchQuery_node
{
    public function __construct($leaves)
    {
        $this->leaves = $leaves;
    }

    public function _flatten()
    {
        // This flattens e.g. (AND (AND a b) (OR c d) e)
        //        to (AND a b e (OR c d))
        $flat = array();
        foreach ($this->leaves as $leaf) {
            $leaf = $leaf->optimize();
            if ($this->op == $leaf->op) {
                $flat = array_merge($flat, $leaf->leaves);
            } else {
                $flat[] = $leaf;
            }
        }
        $this->leaves = $flat;
    }

    public function optimize()
    {
        $this->_flatten();
        assert(!empty($this->leaves));
        if (count($this->leaves) == 1) {
            return $this->leaves[0]; // (AND x) -> x
        }
        return $this;
    }

    public function highlight_words($negated = false)
    {
        $words = array();
        foreach ($this->leaves as $leaf) {
            array_splice(
                $words,
                0,
                0,
                $leaf->highlight_words($negated)
            );
        }
        return $words;
    }
}

/**
 * A (possibly multi-argument) 'AND' conjoin.
 */
class TextSearchQuery_node_and extends TextSearchQuery_node_binop
{
    public $op = "AND";

    public function optimize()
    {
        $this->_flatten();

        // Convert (AND (NOT a) (NOT b) c d) into (AND (NOT (OR a b)) c d).
        // Since OR's are more efficient for regexp matching:
        //   (?!.*a)(?!.*b)  vs   (?!.*(?:a|b))

        // Suck out the negated leaves.
        $nots = array();
        foreach ($this->leaves as $key => $leaf) {
            if ($leaf->op == 'NOT') {
                $nots[] = $leaf->leaves[0];
                unset($this->leaves[$key]);
            }
        }

        // Combine the negated leaves into a single negated or.
        if ($nots) {
            $node = ( new TextSearchQuery_node_not(new TextSearchQuery_node_or($nots)) );
            array_unshift($this->leaves, $node->optimize());
        }

        assert(!empty($this->leaves));
        if (count($this->leaves) == 1) {
            return $this->leaves[0];  // (AND x) -> x
        }
        return $this;
    }

    /* FIXME!
     * Either we need all combinations of all words to be position independent,
     * or we have to use multiple match calls for each AND
     * (AND x y) => /(?(:x)(:y))|(?(:y)(:x))/
     */
    public function regexp()
    {
        $regexp = '';
        foreach ($this->leaves as $leaf) {
            $regexp .= $leaf->regexp();
        }
        return $regexp;
    }
}

/**
 * A (possibly multi-argument) 'OR' conjoin.
 */
class TextSearchQuery_node_or extends TextSearchQuery_node_binop
{
    public $op = "OR";

    public function regexp()
    {
        // We will combine any of our direct descendents which are WORDs
        // into a single (?=.*(?:word1|word2|...)) regexp.

        $regexps = array();
        $words = array();

        foreach ($this->leaves as $leaf) {
            if ($leaf->op == 'WORD') {
                $words[] = preg_quote($leaf->word, '/');
            } else {
                $regexps[] = $leaf->regexp();
            }
        }

        if ($words) {
            array_unshift(
                $regexps,
                '(?=.*' . $this->_join($words) . ')'
            );
        }

        return $this->_join($regexps);
    }

    public function _join($regexps)
    {
        assert(count($regexps) > 0);

        if (count($regexps) > 1) {
            return '(?:' . join('|', $regexps) . ')';
        } else {
            return $regexps[0];
        }
    }
}


////////////////////////////////////////////////////////////////
//
// Parser:
//   op's (and, or, not) are forced to lowercase in the tokenizer.
//
////////////////////////////////////////////////////////////////
define('TSQ_TOK_BINOP', 1);
define('TSQ_TOK_NOT', 2);
define('TSQ_TOK_LPAREN', 4);
define('TSQ_TOK_RPAREN', 8);
define('TSQ_TOK_WORD', 16);
define('TSQ_TOK_STARTS_WITH', 32);
define('TSQ_TOK_ENDS_WITH', 64);
define('TSQ_TOK_EXACT', 128);
define('TSQ_TOK_REGEX', 256);
define('TSQ_TOK_REGEX_GLOB', 512);
define('TSQ_TOK_REGEX_PCRE', 1024);
define('TSQ_TOK_REGEX_SQL', 2048);
define('TSQ_TOK_ALL', 4096);
// all bits from word to the last.
define('TSQ_ALLWORDS', (4096 * 2) - 1 - (16 - 1));

class TextSearchQuery_Parser
{
    /*
     * This is a simple recursive descent parser, based on the following grammar:
     *
     * toplist    :
     *        | toplist expr
     *        ;
     *
     *
     * list    : expr
     *        | list expr
     *        ;
     *
     * expr    : atom
     *        | expr BINOP atom
     *        ;
     *
     * atom    : '(' list ')'
     *        | NOT atom
     *        | WORD
     *        ;
     *
     * The terminal tokens are:
     *
     *
     * and|or          BINOP
     * -|not          NOT
     * (          LPAREN
     * )          RPAREN
     * /[^-()\s][^()\s]*  WORD
     * /"[^"]*"/      WORD
     * /'[^']*'/      WORD
     *
     * ^WORD              STARTS_WITH
     * WORD*              STARTS_WITH
     * *WORD              ENDS_WITH
     * ^WORD$             EXACT
     * *                  ALL
     */

    public function parse($search_expr, $case_exact = false, $regex = TSQ_REGEX_AUTO)
    {
        $this->lexer = new TextSearchQuery_Lexer($search_expr, $case_exact, $regex);
        $this->_regex = $regex;
        $tree = $this->get_list('toplevel');
        assert($this->lexer->eof());
        unset($this->lexer);
        return $tree;
    }

    public function get_list($is_toplevel = false)
    {
        $list = array();

        // token types we'll accept as words (and thus expr's) for the
        // purpose of error recovery:
        $accept_as_words = TSQ_TOK_NOT | TSQ_TOK_BINOP;
        if ($is_toplevel) {
            $accept_as_words |= TSQ_TOK_LPAREN | TSQ_TOK_RPAREN;
        }

        while (
            ($expr = $this->get_expr())
                || ($expr = $this->get_word($accept_as_words))
        ) {
            $list[] = $expr;
        }

        if (!$list) {
            if ($is_toplevel) {
                return new TextSearchQuery_node();
            } else {
                return false;
            }
        }
        return new TextSearchQuery_node_and($list);
    }

    public function get_expr()
    {
        if (!($expr = $this->get_atom())) {
            return false;
        }

        $savedpos = $this->lexer->tell();
        while (($op = $this->lexer->get(TSQ_TOK_BINOP))) {
            if (! ($right = $this->get_atom())) {
                break;
            }

            if ($op == 'and') {
                $expr = new TextSearchQuery_node_and(array($expr, $right));
            } else {
                assert($op == 'or');
                $expr = new TextSearchQuery_node_or(array($expr, $right));
            }

            $savedpos = $this->lexer->tell();
        }
        $this->lexer->seek($savedpos);

        return $expr;
    }


    public function get_atom()
    {
        if ($word = $this->get_word(TSQ_ALLWORDS)) {
            return $word;
        }

        $savedpos = $this->lexer->tell();
        if ($this->lexer->get(TSQ_TOK_LPAREN)) {
            if (($list = $this->get_list()) && $this->lexer->get(TSQ_TOK_RPAREN)) {
                return $list;
            }
        } elseif ($this->lexer->get(TSQ_TOK_NOT)) {
            if (($atom = $this->get_atom())) {
                return new TextSearchQuery_node_not($atom);
            }
        }
        $this->lexer->seek($savedpos);
        return false;
    }

    public function get_word($accept = TSQ_ALLWORDS)
    {
        foreach (
            array("WORD","STARTS_WITH","ENDS_WITH","EXACT",
                       "REGEX","REGEX_GLOB","REGEX_PCRE","ALL") as $tok
        ) {
            $const = constant("TSQ_TOK_" . $tok);
            if ($accept & $const and ($word = $this->lexer->get($const))) {
                $classname = "TextSearchQuery_node_" . strtolower($tok);
                return new $classname($word);
            }
        }
        return false;
    }
}

class TextSearchQuery_Lexer
{
    public function __construct($query_str, $case_exact = false, $regex = TSQ_REGEX_AUTO)
    {
        $this->tokens = $this->tokenize($query_str, $case_exact, $regex);
        $this->pos = 0;
    }

    public function tell()
    {
        return $this->pos;
    }

    public function seek($pos)
    {
        $this->pos = $pos;
    }

    public function eof()
    {
        return $this->pos == count($this->tokens);
    }

    /**
     * TODO: support more regex styles, esp. prefer the forced ones over auto
     * re: and // stuff
     */
    public function tokenize($string, $case_exact = false, $regex = TSQ_REGEX_AUTO)
    {
        $tokens = array();
        $buf = $case_exact ? ltrim($string) : strtolower(ltrim($string));
        while (!empty($buf)) {
            if (preg_match('/^(and|or)\b\s*/i', $buf, $m)) {
                $val = strtolower($m[1]);
                $type = TSQ_TOK_BINOP;
            } elseif (preg_match('/^(-|not\b)\s*/i', $buf, $m)) {
                $val = strtolower($m[1]);
                $type = TSQ_TOK_NOT;
            } elseif (preg_match('/^([()])\s*/', $buf, $m)) {
                $val = $m[1];
                $type = $m[1] == '(' ? TSQ_TOK_LPAREN : TSQ_TOK_RPAREN;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_GLOB)
                    and preg_match('/^\*\s*/', $buf, $m)
            ) { // * => ALL
                $val = "*";
                $type = TSQ_TOK_ALL;
            } elseif (
                $regex & (TSQ_REGEX_PCRE)
                    and preg_match('/^\.\*\s*/', $buf, $m)
            ) { // .* => ALL
                $val = ".*";
                $type = TSQ_TOK_ALL;
            } elseif (
                $regex & (TSQ_REGEX_SQL)
                    and preg_match('/^%\s*/', $buf, $m)
            ) { // % => ALL
                $val = "%";
                $type = TSQ_TOK_ALL;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_PCRE)
                    and preg_match('/^\^([^-()][^()\s]*)\s*/', $buf, $m)
            ) { // ^word
                $val = $m[1];
                $type = TSQ_TOK_STARTS_WITH;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_GLOB)
                    and preg_match('/^([^-()][^()\s]*)\*\s*/', $buf, $m)
            ) { // word*
                $val = $m[1];
                $type = TSQ_TOK_STARTS_WITH;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_GLOB)
                    and preg_match('/^\*([^-()][^()\s]*)\s*/', $buf, $m)
            ) { // *word
                $val = $m[1];
                $type = TSQ_TOK_ENDS_WITH;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_PCRE)
                    and preg_match('/^([^-()][^()\s]*)\$\s*/', $buf, $m)
            ) { // word$
                $val = $m[1];
                $type = TSQ_TOK_ENDS_WITH;
            } elseif (
                $regex & (TSQ_REGEX_AUTO | TSQ_REGEX_POSIX | TSQ_REGEX_PCRE)
                    and preg_match('/^\^([^-()][^()\s]*)\$\s*/', $buf, $m)
            ) { // ^word$
                $val = $m[1];
                $type = TSQ_TOK_EXACT;
            } elseif (preg_match('/^ " ( (?: [^"]+ | "" )* ) " \s*/x', $buf, $m)) { // "words "
                $val = str_replace('""', '"', $m[1]);
                $type = TSQ_TOK_WORD;
            } elseif (preg_match("/^ ' ( (?:[^']+|'')* ) ' \s*/x", $buf, $m)) { // 'words '
                $val = str_replace("''", "'", $m[1]);
                $type = TSQ_TOK_WORD;
            } elseif (preg_match('/^([^-()][^()\s]*)\s*/', $buf, $m)) { // word
                $val = $m[1];
                $type = TSQ_TOK_WORD;
            } else {
                assert(empty($buf));
                break;
            }
            $buf = substr($buf, strlen($m[0]));

            /* refine the simple parsing from above: bla*bla, bla?bla, ...
            if ($regex and $type == TSQ_TOK_WORD) {
                if (substr($val,0,1) == "^")
                    $type = TSQ_TOK_STARTS_WITH;
                elseif (substr($val,0,1) == "*")
                    $type = TSQ_TOK_ENDS_WITH;
                elseif (substr($val,-1,1) == "*")
                    $type = TSQ_TOK_STARTS_WITH;
            }
            */
            $tokens[] = array($type, $val);
        }
        return $tokens;
    }

    public function get($accept)
    {
        if ($this->pos >= count($this->tokens)) {
            return false;
        }

        list ($type, $val) = $this->tokens[$this->pos];
        if (($type & $accept) == 0) {
            return false;
        }

        $this->pos++;
        return $val;
    }
}

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
