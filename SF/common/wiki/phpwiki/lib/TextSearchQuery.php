<?php rcs_id('$Id$');
/**
 * A text search query.
 *
 * This represents "Google-like" text search queries like:
 * <dl>
 * <dt> wiki -test
 *   <dd> Match strings containing the substring 'wiki',  and not containing the
 *        substring 'test'.
 * <dt> wiki word or page
 *   <dd> Match strings containing the substring 'wiki' and either the substring
 *        'word' or the substring 'page'.
 * </dl>
 *
 * The full query syntax, in order of precedence, is roughly:
 *
 * The unary 'NOT' or '-' operator (they are equivalent) negates the
 * following search clause.
 *
 * Search clauses may be joined with the (left-associative) binary operators
 * 'AND' and 'OR'.
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
 * @author: Jeff Dairiki
 */
class TextSearchQuery {
    /**
     * Create a new query.
     *
     * @param $search_query string The query.  Syntax is as described above.
     * Note that an empty $search_query will match anything.
     * @see TextSearchQuery
     */
    function TextSearchQuery($search_query, $case_exact=false, $regex=false) {
        $parser = new TextSearchQuery_Parser;
        $this->_tree = $parser->parse($search_query);
        $this->_optimize();
    }

    function _optimize() {
        $this->_tree = $this->_tree->optimize();
    }

    /**
     * Get a regexp which matches the query.
     */
    function asRegexp() {
        if (!isset($this->_regexp))
            $this->_regexp =  '/^' . $this->_tree->regexp() . '/isS';
        return $this->_regexp;
    }

    /**
     * Match query against string.
     *
     * @param $string string The string to match. 
     * @return boolean True if the string matches the query.
     */
    function match($string) {
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
    function getHighlightRegexp() {
        if (!isset($this->_hilight_regexp)) {
            $words = array_unique($this->_tree->highlight_words());
            if (!$words) {
                $this->_hilight_regexp = false;
            }
            else {
                foreach ($words as $key => $word)
                    $words[$key] = preg_quote($word, '/');
                $this->_hilight_regexp = '(?:' . join('|', $words) . ')';
            }
        }
        return $this->_hilight_regexp;
    }

    /**
     * Make an SQL clause which matches the query.
     *
     * @param $make_sql_clause_cb WikiCallback
     * A callback which takes a single word as an argument and
     * returns an SQL clause which will match exactly those records
     * containing the word.  The word passed to the callback will always
     * be in all lower case.
     *
     * TODO: support db-specific extensions, like MATCH or REGEX
     *
     * Example usage:
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
     * @return string The PCRE regexp.
     */
    function makeSqlClause($make_sql_clause_cb) {
        $this->_sql_clause_cb = $make_sql_clause_cb;
        return $this->_sql_clause($this->_tree);
    }

    function _sql_clause($node) {
        switch ($node->op) {
        case 'WORD':
            return $this->_sql_clause_cb->call($node->word);
        case 'NOT':
            return "NOT (" . $this->_sql_clause($node->leaves[0]) . ")";
        case 'AND':
        case 'OR':
            $subclauses = array();
            foreach ($node->leaves as $leaf)
                $subclauses[] = "(" . $this->_sql_clause($leaf) . ")";
            return join(" $node->op ", $subclauses);
        default:
            assert($node->op == 'VOID');
            return '1=1';
        }
    }

    /**
     * Get printable representation of the parse tree.
     *
     * This is for debugging only.
     * @return string Printable parse tree.
     */
    function asString() {
        return $this->_as_string($this->_tree);
    }

    function _as_string($node, $indent = '') {
        switch ($node->op) {
        case 'WORD':
            return $indent . "WORD: $node->word";
        case 'VOID':
            return $indent . "VOID";
        default:
            $lines = array($indent . $node->op . ":");
            $indent .= "  ";
            foreach ($node->leaves as $leaf)
                $lines[] = $this->_as_string($leaf, $indent);
            return join("\n", $lines);
        }
    }
}

/**
 * This is a TextSearchQuery which matches nothing.
 */
class NullTextSearchQuery extends TextSearchQuery {
    /**
     * Create a new query.
     *
     * @see TextSearchQuery
     */
    function NullTextSearchQuery() {}
    function asRegexp()		{ return '/^(?!a)a/x'; }
    function match($string)	{ return false; }
    function getHighlightRegexp() { return ""; }
    function makeSqlClause($make_sql_clause_cb) { return "(1 = 0)"; }
    function asString() { return "NullTextSearchQuery"; }
};


////////////////////////////////////////////////////////////////
//
// Remaining classes are private.
//
////////////////////////////////////////////////////////////////
/**
 * Virtual base class for nodes in a TextSearchQuery parse tree.
 *
 * Also servers as a 'VOID' (contentless) node.
 */
class TextSearchQuery_node
{
    var $op = 'VOID';

    /**
     * Optimize this node.
     * @return object Optimized node.
     */
    function optimize() {
        return $this;
    }

    /**
     * @return regexp matching this node.
     */
    function regexp() {
        return '';
    }

    /**
     * @param bool True if this node has been negated (higher in the parse tree.)
     * @return array A list of all non-negated words contained by this node.
     */
    function highlight_words($negated = false) {
        return array();
    }
}

/**
 * A word.
 */
class TextSearchQuery_node_word
extends TextSearchQuery_node
{
    var $op = "WORD";
    
    function TextSearchQuery_node_word($word) {
        $this->word = $word;
    }

    function regexp() {
        return '(?=.*' . preg_quote($this->word, '/') . ')';
    }

    function highlight_words($negated = false) {
        return $negated ? array() : array($this->word);
    }
}


/**
 * A negated clause.
 */
class TextSearchQuery_node_not
extends TextSearchQuery_node
{
    var $op = "NOT";
    
    function TextSearchQuery_node_not($leaf) {
        $this->leaves = array($leaf);
    }

    function optimize() {
        $leaf = &$this->leaves[0];
        $leaf = $leaf->optimize();
        if ($leaf->op == 'NOT')
            return $leaf->leaves[0]; // ( NOT ( NOT x ) ) -> x
        return $this;
    }
    
    function regexp() {
        $leaf = &$this->leaves[0];
        return '(?!' . $leaf->regexp() . ')';
    }

    function highlight_words($negated = false) {
        return $this->leaves[0]->highlight_words(!$negated);
    }
}

/**
 * Virtual base class for 'AND' and 'OR conjoins.
 */
class TextSearchQuery_node_binop
extends TextSearchQuery_node
{
    function TextSearchQuery_node_binop($leaves) {
        $this->leaves = $leaves;
    }

    function _flatten() {
        // This flattens e.g. (AND (AND a b) (OR c d) e)
        //        to (AND a b e (OR c d))
        $flat = array();
        foreach ($this->leaves as $leaf) {
            $leaf = $leaf->optimize();
            if ($this->op == $leaf->op)
                $flat = array_merge($flat, $leaf->leaves);
            else
                $flat[] = $leaf;
        }
        $this->leaves = $flat;
    }

    function optimize() {
        $this->_flatten();
        assert(!empty($this->leaves));
        if (count($this->leaves) == 1)
            return $this->leaves[0]; // (AND x) -> x
        return $this;
    }

    function highlight_words($negated = false) {
        $words = array();
        foreach ($this->leaves as $leaf)
            array_splice($words,0,0,
                         $leaf->highlight_words($negated));
        return $words;
    }
}

/**
 * A (possibly multi-argument) 'AND' conjoin.
 */
class TextSearchQuery_node_and
extends TextSearchQuery_node_binop
{
    var $op = "AND";
    
    function optimize() {
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
            $node = ( new TextSearchQuery_node_not
                      (new TextSearchQuery_node_or($nots)) );
            array_unshift($this->leaves, $node->optimize());
        }
        
        assert(!empty($this->leaves));
        if (count($this->leaves) == 1)
            return $this->leaves[0];  // (AND x) -> x
        return $this;
    }

    function regexp() {
        $regexp = '';
        foreach ($this->leaves as $leaf)
            $regexp .= $leaf->regexp();
        return $regexp;
    }
}

/**
 * A (possibly multi-argument) 'OR' conjoin.
 */
class TextSearchQuery_node_or
extends TextSearchQuery_node_binop
{
    var $op = "OR";

    function regexp() {
        // We will combine any of our direct descendents which are WORDs
        // into a single (?=.*(?:word1|word2|...)) regexp.
        
        $regexps = array();
        $words = array();

        foreach ($this->leaves as $leaf) {
            if ($leaf->op == 'WORD')
                $words[] = preg_quote($leaf->word, '/');
            else
                $regexps[] = $leaf->regexp();
        }

        if ($words)
            array_unshift($regexps,
                          '(?=.*' . $this->_join($words) . ')');

        return $this->_join($regexps);
    }

    function _join($regexps) {
        assert(count($regexps) > 0);

        if (count($regexps) > 1)
            return '(?:' . join('|', $regexps) . ')';
        else
            return $regexps[0];
    }
}


////////////////////////////////////////////////////////////////
//
// Parser:
//
////////////////////////////////////////////////////////////////
define ('TSQ_TOK_WORD',   1);
define ('TSQ_TOK_BINOP',  2);
define ('TSQ_TOK_NOT',    4);
define ('TSQ_TOK_LPAREN', 8);
define ('TSQ_TOK_RPAREN', 16);

class TextSearchQuery_Parser 
{
    /*
     * This is a simple recursive descent parser, based on the following grammar:
     *
     * toplist	:
     *		| toplist expr
     *		;
     *
     *
     * list	: expr
     *		| list expr
     *		;
     *
     * expr	: atom
     *		| expr BINOP atom
     *		;
     *
     * atom	: '(' list ')'
     *		| NOT atom
     *		| WORD
     *		;
     *
     * The terminal tokens are:
     *
     *
     * and|or		BINOP
     * -|not		NOT
     * (		LPAREN
     * )		RPAREN
     * [^-()\s][^()\s]*	WORD
     * "[^"]*"		WORD
     * '[^']*'		WORD
     */

    function parse ($search_expr) {
        $this->lexer = new TextSearchQuery_Lexer($search_expr);
        $tree = $this->get_list('toplevel');
        assert($this->lexer->eof());
        unset($this->lexer);
        return $tree;
    }
    
    function get_list ($is_toplevel = false) {
        $list = array();

        // token types we'll accept as words (and thus expr's) for the
        // purpose of error recovery:
        $accept_as_words = TSQ_TOK_NOT | TSQ_TOK_BINOP;
        if ($is_toplevel)
            $accept_as_words |= TSQ_TOK_LPAREN | TSQ_TOK_RPAREN;
        
        while ( ($expr = $this->get_expr())
                || ($expr = $this->get_word($accept_as_words)) ) {
            
            $list[] = $expr;
        }

        if (!$list) {
            if ($is_toplevel)
                return new TextSearchQuery_node;
            else
                return false;
        }
        return new TextSearchQuery_node_and($list);
    }

    function get_expr () {
        if ( !($expr = $this->get_atom()) )
            return false;
        
        $savedpos = $this->lexer->tell();
        while ( ($op = $this->lexer->get(TSQ_TOK_BINOP)) ) {
            if ( ! ($right = $this->get_atom()) ) {
                break;
            }
            
            if ($op == 'and')
                $expr = new TextSearchQuery_node_and(array($expr, $right));
            else {
                assert($op == 'or');
                $expr = new TextSearchQuery_node_or(array($expr, $right));
            }

            $savedpos = $this->lexer->tell();
        }
        $this->lexer->seek($savedpos);

        return $expr;
    }
    

    function get_atom() {
        if ($word = $this->get_word())
            return $word;

        $savedpos = $this->lexer->tell();
        if ( $this->lexer->get(TSQ_TOK_LPAREN) ) {
            if ( ($list = $this->get_list()) && $this->lexer->get(TSQ_TOK_RPAREN) )
                return $list;
        }
        elseif ( $this->lexer->get(TSQ_TOK_NOT) ) {
            if ( ($atom = $this->get_atom()) )
                return new TextSearchQuery_node_not($atom);
        }
        $this->lexer->seek($savedpos);
        return false;
    }

    function get_word($accept = TSQ_TOK_WORD) {
        if ( ($word = $this->lexer->get($accept)) )
            return new TextSearchQuery_node_word($word);
        return false;
    }
}

class TextSearchQuery_Lexer {
    function TextSearchQuery_Lexer ($query_str) {
        $this->tokens = $this->tokenize($query_str);
        $this->pos = 0;
    }

    function tell() {
        return $this->pos;
    }

    function seek($pos) {
        $this->pos = $pos;
    }

    function eof() {
        return $this->pos == count($this->tokens);
    }

    function tokenize($string) {
        $tokens = array();
        $buf = strtolower(ltrim($string));
        while (!empty($buf)) {
            if (preg_match('/^(and|or)\b\s*/', $buf, $m)) {
                $val = $m[1];
                $type = TSQ_TOK_BINOP;
            }
            elseif (preg_match('/^(-|not\b)\s*/', $buf, $m)) {
                $val = $m[1];
                $type = TSQ_TOK_NOT;
            }
            elseif (preg_match('/^([()])\s*/', $buf, $m)) {
                $val = $m[1];
                $type = $m[1] == '(' ? TSQ_TOK_LPAREN : TSQ_TOK_RPAREN;
            }
            elseif (preg_match('/^ " ( (?: [^"]+ | "" )* ) " \s*/x', $buf, $m)) {
                $val = str_replace('""', '"', $m[1]);
                $type = TSQ_TOK_WORD;
            }
            elseif (preg_match("/^ ' ( (?:[^']+|'')* ) ' \s*/x", $buf, $m)) {
                $val = str_replace("''", "'", $m[1]);
                $type = TSQ_TOK_WORD;
            }
            elseif (preg_match('/^([^-()][^()\s]*)\s*/', $buf, $m)) {
                $val = $m[1];
                $type = TSQ_TOK_WORD;
            }
            else {
                assert(empty($buf));
                break;
            }
            $buf = substr($buf, strlen($m[0]));
            $tokens[] = array($type, $val);
        }
        return $tokens;
    }
    
    function get($accept) {
        if ($this->pos >= count($this->tokens))
            return false;
        
        list ($type, $val) = $this->tokens[$this->pos];
        if (($type & $accept) == 0)
            return false;
        
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
?>
