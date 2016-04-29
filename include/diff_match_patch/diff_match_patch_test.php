<?php
error_reporting(E_ALL);
require_once('diff_match_patch.php');

/**
 * Test Harness for Diff Match and Patch
 *
 * php port by Tobias Buschor shwups.ch
 *
 * Copyright 2006 Google Inc.
 * http://code.google.com/p/google-diff-match-patch/
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


mb_internal_encoding('UTF-8');

$test_good = 0;
$test_bad = 0;

// If expected and actual are the identical, print 'Ok', otherwise 'Fail!'
function assertEquals($msg, $expected, $actual = null) {
	global $test_good, $test_bad;
	if ($actual === null) {
		// msg is optional.
		$actual = $expected;
		$expected = $msg;
		$msg = "Expected: " . var_export($expected,true) . " \nActual:   " . var_export($actual,1) . " ";
	}
	if ($expected === $actual) {
		echo '<FONT COLOR="#009900">Ok</FONT><BR>';
		$test_good++;
	} else {
		echo '<FONT COLOR="#990000"><BIG>Fail!</BIG></FONT><BR>';
		$msg = preg_replace( array('/&/','/</','/>/'), array('&amp;','&lt;','&gt;'), $msg);
		echo '<pre><code>' . $msg . '</code></pre><BR>';
		$test_bad++;
	}
}

function runTests() {
	$tests = array(
				'testDiffCommonPrefix'
				,'testDiffCommonSuffix'
				,'testDiffHalfMatch'
		,'testDiffLinesToChars', 'testDiffCharsToLines', 'testDiffCleanupMerge'
		,'testDiffCleanupSemanticLossless', 'testDiffCleanupSemantic'
		,'testDiffCleanupEfficiency', 'testDiffPrettyHtml', 'testDiffText'
		,'testDiffDelta', 'testDiffXIndex', 'testDiffLevenshtein', 'testDiffPath'
		,'testDiffMain'

		,'testMatchAlphabet'
				,'testMatchBitap'
				,'testMatchMain'

		,'testPatchObj'
				,'testPatchFromText'
				,'testPatchToText'
		,'testPatchAddContext', 'testPatchMake', 'testPatchSplitMax'
		,'testPatchAddPadding', 'testPatchApply'
				);
	foreach($tests as $test){
		echo '<H3>' . $test . ':</H3>';
		$test();
	}
}

$startTime = microtime(true);
runTests();
$endTime = microtime(true);
echo '<H3>Done.</H3>';
echo '<P>Tests passed: ' . $test_good . '<BR>Tests failed: ' . $test_bad . '</P>';
echo '<P>Total time: ' . ($endTime - $startTime) . ' ms</P>';




















//////////////////////////////////////////////////////////////////////////////////////////
// functions
//////////////////////////////////////////////////////////////////////////////////////////










// If expected and actual are the equivalent, pass the test.
function assertEquivalent($msg, $expected, $actual = null) {
	if ( !$actual === null) {
		// msg is optional.
		$actual = $expected;
		$expected = $msg;
		$msg = 'Expected: \'' . $expected . '\' Actual: \'' . $actual . '\'';
	}
	if (_equivalent($expected, $actual)) {
//    assertEquals($msg, (string)$expected, (string)$actual );
		assertEquals($msg, $expected, $actual );
	} else {
		assertEquals($msg, $expected, $actual);
	}
}


// Are a and b the equivalent? -- Recursive.
function _equivalent($a, $b) {
	if ($a == $b) {
		return true;
	}
	if ( is_array($a) && is_array($b) ) {
		if ( (string)$a != (string)$b ) {
			return false;
		}
		foreach ($a as $p => $obj) {
			if (!_equivalent($a[$p], $b[$p])) {
				return false;
			}
		}
		foreach ( $b as $p => $obj ) {
			if (!_equivalent($a[$p], $b[$p])) {
				return false;
			}
		}
		return true;
	}
	return false;
}


function diff_rebuildtexts($diffs) {
	// Construct the two texts which made up the diff originally.
	$text1 = '';
	$text2 = '';
	for ($x = 0; $x < count($diffs); $x++) {
		if ($diffs[$x][0] != DIFF_INSERT) {
			$text1 .= $diffs[$x][1];
		}
		if ($diffs[$x][0] != DIFF_DELETE) {
			$text2 .= $diffs[$x][1];
		}
	}
	return array($text1, $text2);
}

function dmp(){
	static $dmp = null;
	if(!$dmp){
		$dmp = new diff_match_patch();
	}
	return $dmp;
}

// DIFF TEST FUNCTIONS


function testDiffCommonPrefix() {
	// Detect and remove any common prefix.
	// Null case.
	assertEquals(0, dmp()->diff_commonPrefix('abc', 'xyz'));

	// Non-null case.
	assertEquals(4, dmp()->diff_commonPrefix('1234abcdef', '1234xyz'));

	// Whole case.
	assertEquals(4, dmp()->diff_commonPrefix('1234', '1234xyz'));
}

function testDiffCommonSuffix() {
	// Detect and remove any common suffix.
	// Null case.
	assertEquals(0, dmp()->diff_commonSuffix('abc', 'xyz'));

	// Non-null case.
	assertEquals(4, dmp()->diff_commonSuffix('abcdef1234', 'xyz1234'));

	// Whole case.
	assertEquals(4, dmp()->diff_commonSuffix('1234', 'xyz1234'));
}

function testDiffHalfMatch() {
	// Detect a halfmatch.
	// No match.
	assertEquals(null, dmp()->diff_halfMatch('1234567890', 'abcdef'));

	// Single Match.
	assertEquivalent(array('12', '90', 'a', 'z', '345678'), dmp()->diff_halfMatch('1234567890', 'a345678z'));

	assertEquivalent(array('a', 'z', '12', '90', '345678'), dmp()->diff_halfMatch('a345678z', '1234567890'));

	// Multiple Matches.
	assertEquivalent(array('12123', '123121', 'a', 'z', '1234123451234'), dmp()->diff_halfMatch('121231234123451234123121', 'a1234123451234z'));

	assertEquivalent(array('', '-=-=-=-=-=', 'x', '', 'x-=-=-=-=-=-=-='), dmp()->diff_halfMatch('x-=-=-=-=-=-=-=-=-=-=-=-=', 'xx-=-=-=-=-=-=-='));

	assertEquivalent(array('-=-=-=-=-=', '', '', 'y', '-=-=-=-=-=-=-=y'), dmp()->diff_halfMatch('-=-=-=-=-=-=-=-=-=-=-=-=y', '-=-=-=-=-=-=-=yy'));
}

function testDiffLinesToChars() {
	// Convert lines down to characters.
	assertEquivalent(array("\x01\x02\x01", "\x02\x01\x02", array('', "alpha\n", "beta\n")), dmp()->diff_linesToChars("alpha\nbeta\nalpha\n", "beta\nalpha\nbeta\n"));

	assertEquivalent(array('', "\x01\x02\x03\x03", array('', "alpha\r\n", "beta\r\n", "\r\n")), dmp()->diff_linesToChars('', "alpha\r\nbeta\r\n\r\n\r\n"));

	assertEquivalent(array("\x01", "\x02", array('', 'a', 'b')), dmp()->diff_linesToChars('a', 'b'));

	// More than 256 to reveal any 8-bit limitations.
	$n = 300;
	$lineList = array();
	$charList = array();
	for ($x = 1; $x < $n + 1; $x++) {
		$lineList[$x - 1] = $x . "\n";
		$charList[$x - 1] = mb_chr($x);
	}
	assertEquals($n, count($lineList) );
	$lines = implode('', $lineList);
	$chars = implode('', $charList);
	assertEquals($n, mb_strlen($chars) );
	array_unshift($lineList,'');
	assertEquivalent(array($chars, '', $lineList), dmp()->diff_linesToChars($lines, ''));
}

function testDiffCharsToLines() {
	// Convert chars up to lines.
	$diffs = array(array(DIFF_EQUAL, "\x01\x02\x01"), array(DIFF_INSERT, "\x02\x01\x02"));
	dmp()->diff_charsToLines($diffs, array('', "alpha\n", "beta\n"));
	assertEquivalent(array(array(DIFF_EQUAL, "alpha\nbeta\nalpha\n"), array(DIFF_INSERT, "beta\nalpha\nbeta\n")), $diffs);

	// More than 256 to reveal any 8-bit limitations.
	$n = 300;
	$lineList = array();
	$charList = array();
	for ($x = 1; $x < $n + 1; $x++) {
		$lineList[$x - 1] = $x . "\n";
		$charList[$x - 1] = mb_chr($x);
	}
	assertEquals($n, count($lineList));
	$lines = implode('', $lineList);
	$chars = implode('', $charList);
	assertEquals($n, mb_strlen($chars) );
	array_unshift($lineList,'');
	$diffs = array(array(DIFF_DELETE, $chars));
	dmp()->diff_charsToLines($diffs, $lineList);
	assertEquivalent(array(array(DIFF_DELETE, $lines)), $diffs);
}

function testDiffCleanupMerge() {
	// Cleanup a messy diff.
	// Null case.
	$diffs = array();
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(), $diffs);

	// No change case.
	$diffs = array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'b'), array(DIFF_INSERT, 'c'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'b'), array(DIFF_INSERT, 'c')), $diffs);

	// Merge equalities.
	$diffs = array(array(DIFF_EQUAL, 'a'), array(DIFF_EQUAL, 'b'), array(DIFF_EQUAL, 'c'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, 'abc')), $diffs);

	// Merge deletions.
	$diffs = array(array(DIFF_DELETE, 'a'), array(DIFF_DELETE, 'b'), array(DIFF_DELETE, 'c'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_DELETE, 'abc')), $diffs);

	// Merge insertions.
	$diffs = array(array(DIFF_INSERT, 'a'), array(DIFF_INSERT, 'b'), array(DIFF_INSERT, 'c'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_INSERT, 'abc')), $diffs);

	// Merge interweave.
	$diffs = array(array(DIFF_DELETE, 'a'), array(DIFF_INSERT, 'b'), array(DIFF_DELETE, 'c'), array(DIFF_INSERT, 'd'), array(DIFF_EQUAL, 'e'), array(DIFF_EQUAL, 'f'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_DELETE, 'ac'), array(DIFF_INSERT, 'bd'), array(DIFF_EQUAL, 'ef')), $diffs);

	// Prefix and suffix detection.
	$diffs = array(array(DIFF_DELETE, 'a'), array(DIFF_INSERT, 'abc'), array(DIFF_DELETE, 'dc'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'd'), array(DIFF_INSERT, 'b'), array(DIFF_EQUAL, 'c')), $diffs);

	// Slide edit left.
	$diffs = array(array(DIFF_EQUAL, 'a'), array(DIFF_INSERT, 'ba'), array(DIFF_EQUAL, 'c'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_INSERT, 'ab'), array(DIFF_EQUAL, 'ac')), $diffs);

	// Slide edit right.
	$diffs = array(array(DIFF_EQUAL, 'c'), array(DIFF_INSERT, 'ab'), array(DIFF_EQUAL, 'a'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, 'ca'), array(DIFF_INSERT, 'ba')), $diffs);

	// Slide edit left recursive.
	$diffs = array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'b'), array(DIFF_EQUAL, 'c'), array(DIFF_DELETE, 'ac'), array(DIFF_EQUAL, 'x'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_DELETE, 'abc'), array(DIFF_EQUAL, 'acx')), $diffs);

	// Slide edit right recursive.
	$diffs = array(array(DIFF_EQUAL, 'x'), array(DIFF_DELETE, 'ca'), array(DIFF_EQUAL, 'c'), array(DIFF_DELETE, 'b'), array(DIFF_EQUAL, 'a'));
	dmp()->diff_cleanupMerge($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, 'xca'), array(DIFF_DELETE, 'cba')), $diffs);
}

function testDiffCleanupSemanticLossless() {
	// Slide diffs to match logical boundaries.
	// Null case.
	$diffs = array();
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(), $diffs);

	// Blank lines.
	$diffs = array(array(DIFF_EQUAL, "AAA\r\n\r\nBBB"), array(DIFF_INSERT, "\r\nDDD\r\n\r\nBBB"), array(DIFF_EQUAL, "\r\nEEE"));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "AAA\r\n\r\n"), array(DIFF_INSERT, "BBB\r\nDDD\r\n\r\n"), array(DIFF_EQUAL, "BBB\r\nEEE")), $diffs);

	// Line boundaries.
	$diffs = array(array(DIFF_EQUAL, "AAA\r\nBBB"), array(DIFF_INSERT, " DDD\r\nBBB"), array(DIFF_EQUAL, " EEE"));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "AAA\r\n"), array(DIFF_INSERT, "BBB DDD\r\n"), array(DIFF_EQUAL, "BBB EEE")), $diffs);

	// Word boundaries.
	$diffs = array(array(DIFF_EQUAL, "The c"), array(DIFF_INSERT, "ow and the c"), array(DIFF_EQUAL, "at."));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "The "), array(DIFF_INSERT, "cow and the "), array(DIFF_EQUAL, "cat.")), $diffs);

	// Alphanumeric boundaries.
	$diffs = array(array(DIFF_EQUAL, "The-c"), array(DIFF_INSERT, "ow-and-the-c"), array(DIFF_EQUAL, "at."));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "The-"), array(DIFF_INSERT, "cow-and-the-"), array(DIFF_EQUAL, "cat.")), $diffs);

	// Hitting the start.
	$diffs = array(array(DIFF_EQUAL, "a"), array(DIFF_DELETE, "a"), array(DIFF_EQUAL, "ax"));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "a"), array(DIFF_EQUAL, "aax")), $diffs);

	// Hitting the end.
	$diffs = array(array(DIFF_EQUAL, "xa"), array(DIFF_DELETE, "a"), array(DIFF_EQUAL, "a"));
	dmp()->diff_cleanupSemanticLossless($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "xaa"), array(DIFF_DELETE, "a")), $diffs);
}

function testDiffCleanupSemantic() {
	// Cleanup semantically trivial equalities.
	// Null case.
	$diffs = array();
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(), $diffs);

	// No elimination.
	$diffs = array(array(DIFF_DELETE, "a"), array(DIFF_INSERT, "b"), array(DIFF_EQUAL, "cd"), array(DIFF_DELETE, "e"));
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "a"), array(DIFF_INSERT, "b"), array(DIFF_EQUAL, "cd"), array(DIFF_DELETE, "e")), $diffs);

	// Simple elimination.
	$diffs = array(array(DIFF_DELETE, "a"), array(DIFF_EQUAL, "b"), array(DIFF_DELETE, "c"));
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "abc"), array(DIFF_INSERT, "b")), $diffs);

	// Backpass elimination.
	$diffs = array(array(DIFF_DELETE, "ab"), array(DIFF_EQUAL, "cd"), array(DIFF_DELETE, "e"), array(DIFF_EQUAL, "f"), array(DIFF_INSERT, "g"));
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "abcdef"), array(DIFF_INSERT, "cdfg")), $diffs);

	// Multiple eliminations.
	$diffs = array(array(DIFF_INSERT, "1"), array(DIFF_EQUAL, "A"), array(DIFF_DELETE, "B"), array(DIFF_INSERT, "2"), array(DIFF_EQUAL, "_"), array(DIFF_INSERT, "1"), array(DIFF_EQUAL, "A"), array(DIFF_DELETE, "B"), array(DIFF_INSERT, "2"));
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "AB_AB"), array(DIFF_INSERT, "1A2_1A2")), $diffs);

	// Word boundaries.
	$diffs = array(array(DIFF_EQUAL, "The c"), array(DIFF_DELETE, "ow and the c"), array(DIFF_EQUAL, "at."));
	dmp()->diff_cleanupSemantic($diffs);
	assertEquivalent(array(array(DIFF_EQUAL, "The "), array(DIFF_DELETE, "cow and the "), array(DIFF_EQUAL, "cat.")), $diffs);
}

function testDiffCleanupEfficiency() {
	// Cleanup operationally trivial equalities.
	dmp()->Diff_EditCost = 4;
	// Null case.
	$diffs = array();
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(), $diffs);

	// No elimination.
	$diffs = array(array(DIFF_DELETE, "ab"), array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "wxyz"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "34"));
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "ab"), array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "wxyz"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "34")), $diffs);

	// Four-edit elimination.
	$diffs = array(array(DIFF_DELETE, "ab"), array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "xyz"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "34"));
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "abxyzcd"), array(DIFF_INSERT, "12xyz34")), $diffs);

	// Three-edit elimination.
	$diffs = array(array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "x"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "34"));
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "xcd"), array(DIFF_INSERT, "12x34")), $diffs);

	// Backpass elimination.
	$diffs = array(array(DIFF_DELETE, "ab"), array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "xy"), array(DIFF_INSERT, "34"), array(DIFF_EQUAL, "z"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "56"));
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "abxyzcd"), array(DIFF_INSERT, "12xy34z56")), $diffs);

	// High cost elimination.
	dmp()->Diff_EditCost = 5;
	$diffs = array(array(DIFF_DELETE, "ab"), array(DIFF_INSERT, "12"), array(DIFF_EQUAL, "wxyz"), array(DIFF_DELETE, "cd"), array(DIFF_INSERT, "34"));
	dmp()->diff_cleanupEfficiency($diffs);
	assertEquivalent(array(array(DIFF_DELETE, "abwxyzcd"), array(DIFF_INSERT, "12wxyz34")), $diffs);
	dmp()->Diff_EditCost = 4;
}

function testDiffPrettyHtml() {
	// Pretty print.
	$diffs = array(array(DIFF_EQUAL, "a\n"), array(DIFF_DELETE, "<B>b</B>"), array(DIFF_INSERT, "c&d"));
	assertEquals('<SPAN TITLE="i=0">a&para;<BR></SPAN><DEL STYLE="background:#FFE6E6;" TITLE="i=2">&lt;B&gt;b&lt;/B&gt;</DEL><INS STYLE="background:#E6FFE6;" TITLE="i=2">c&amp;d</INS>', dmp()->diff_prettyHtml($diffs));
}

function testDiffText() {
	// Compute the source and destination texts.
	$diffs = array(array(DIFF_EQUAL, "jump"), array(DIFF_DELETE, "s"), array(DIFF_INSERT, "ed"), array(DIFF_EQUAL, " over "), array(DIFF_DELETE, "the"), array(DIFF_INSERT, "a"), array(DIFF_EQUAL, " lazy"));
	assertEquals("jumps over the lazy", dmp()->diff_text1($diffs));

	assertEquals("jumped over a lazy", dmp()->diff_text2($diffs));
}
function testDiffDelta() {
	// Convert a diff into delta string.
	$diffs = array(array(DIFF_EQUAL, "jump"), array(DIFF_DELETE, "s"), array(DIFF_INSERT, "ed"), array(DIFF_EQUAL, " over "), array(DIFF_DELETE, "the"), array(DIFF_INSERT, "a"), array(DIFF_EQUAL, " lazy"), array(DIFF_INSERT, "old dog"));
	$text1 = dmp()->diff_text1($diffs);
	assertEquals("jumps over the lazy", $text1);

	$delta = dmp()->diff_toDelta($diffs);
	assertEquals("=4\t-1\t+ed\t=6\t-3\t+a\t=5\t+old dog", $delta);

	// Convert delta string into a diff.
	assertEquivalent($diffs, dmp()->diff_fromDelta($text1, $delta));

	// Generates error (19 != 20).

	$Error = "Error expected";
	global $lastException;
	try {
		$res = dmp()->diff_fromDelta($text1."x", $delta);
		if( $lastException === 'Delta length (19) does not equal source text length (20).' ){
			assertEquivalent(null, null);
		} else {
			assertEquivalent($Error, $res);
		}
	} catch (Exception $e) {
		assertEquivalent(null, null);
	}

	// Generates error (19 != 18).
	try {
		$res = dmp()->diff_fromDelta( substr($text1,1), $delta);
		if( $lastException === 'Delta length (19) does not equal source text length (18).' ){
			assertEquivalent(null, null);
		} else {
			assertEquivalent($Error, $res);
		}
	} catch (Exception $e) {
		assertEquivalent(null, null);
	}

	// Generates error (%c3%xy invalid Unicode).
	try {
		$res = dmp()->diff_fromDelta("", "+%c3%xy");
		if( $lastException === '' ){
			assertEquivalent(null, null);
		} else {
			assertEquivalent($Error, $res);
		}
	} catch (Exception $e) {
		assertEquivalent(null, null);
	}

	// Test deltas with special characters.
	$u0680 = mb_chr(0*4096 + 6*256 + 8*16 + 0);
	$u0681 = mb_chr(0*4096 + 6*256 + 8*16 + 1);
	$u0682 = mb_chr(0*4096 + 6*256 + 8*16 + 2);
	$diffs = array(array(DIFF_EQUAL, "$u0680 \x00 \t %"), array(DIFF_DELETE, "$u0681 \x01 \n ^"), array(DIFF_INSERT, "$u0682 \x02 \\ |"));
	$text1 = dmp()->diff_text1($diffs);
	assertEquals("$u0680 \x00 \t %$u0681 \x01 \n ^", $text1);

	$delta = dmp()->diff_toDelta($diffs);
	assertEquals("=7\t-7\t+%DA%82 %02 %5C %7C", $delta);

	// Convert delta string into a diff.
	assertEquivalent($diffs, dmp()->diff_fromDelta($text1, $delta));

	// Verify pool of unchanged characters.
	$diffs = array(array(DIFF_INSERT, 'A-Z a-z 0-9 - _ . ! ~ * \' ( ) ; / ? : @ & = + $ , # '));
	$text2 = dmp()->diff_text2($diffs);
	assertEquals('A-Z a-z 0-9 - _ . ! ~ * \' ( ) ; / ? : @ & = + $ , # ', $text2);

	$delta = dmp()->diff_toDelta($diffs);
	assertEquals('+A-Z a-z 0-9 - _ . ! ~ * \' ( ) ; / ? : @ & = + $ , # ', $delta);

	// Convert delta string into a diff.
	assertEquivalent($diffs, dmp()->diff_fromDelta('', $delta));
}

function testDiffXIndex() {
	// Translate a location in text1 to text2.
	// Translation on equality.
	assertEquals(5, dmp()->diff_xIndex(array(array(DIFF_DELETE, 'a'), array(DIFF_INSERT, '1234'), array(DIFF_EQUAL, 'xyz')), 2));

	// Translation on deletion.
	assertEquals(1, dmp()->diff_xIndex(array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, '1234'), array(DIFF_EQUAL, 'xyz')), 3));
}

function testDiffLevenshtein() {
	// Levenshtein with trailing equality.
	assertEquals(4, dmp()->diff_levenshtein(array(array(DIFF_DELETE, 'abc'), array(DIFF_INSERT, '1234'), array(DIFF_EQUAL, 'xyz'))));
	// Levenshtein with leading equality.
	assertEquals(4, dmp()->diff_levenshtein(array(array(DIFF_EQUAL, 'xyz'), array(DIFF_DELETE, 'abc'), array(DIFF_INSERT, '1234'))));
	// Levenshtein with middle equality.
	assertEquals(7, dmp()->diff_levenshtein(array(array(DIFF_DELETE, 'abc'), array(DIFF_EQUAL, 'xyz'), array(DIFF_INSERT, '1234'))));
}

function testDiffPath() {
	// Single letters.
	// Trace a path from back to front.
	$v_map = array();
	array_push( $v_map, array('0,0'=>true) );
	array_push( $v_map, array('0,1'=>true, '1,0'=>true) );
	array_push( $v_map, array('0,2'=>true, '2,0'=>true, '2,2'=>true) );
	array_push( $v_map, array('0,3'=>true, '2,3'=>true, '3,0'=>true, '4,3'=>true) );
	array_push( $v_map, array('0,4'=>true, '2,4'=>true, '4,0'=>true, '4,4'=>true, '5,3'=>true) );
	array_push( $v_map, array('0,5'=>true, '2,5'=>true, '4,5'=>true, '5,0'=>true, '6,3'=>true, '6,5'=>true) );
	array_push( $v_map, array('0,6'=>true, '2,6'=>true, '4,6'=>true, '6,6'=>true, '7,5'=>true) );
	assertEquivalent(array(array(DIFF_INSERT, 'W'), array(DIFF_DELETE, 'A'), array(DIFF_EQUAL, '1'), array(DIFF_DELETE, 'B'), array(DIFF_EQUAL, '2'), array(DIFF_INSERT, 'X'), array(DIFF_DELETE, 'C'), array(DIFF_EQUAL, '3'), array(DIFF_DELETE, 'D')), dmp()->diff_path1($v_map, 'A1B2C3D', 'W12X3'));

	// Trace a path from front to back.
	array_pop($v_map);
	assertEquivalent(array(array(DIFF_EQUAL, '4'), array(DIFF_DELETE, 'E'), array(DIFF_INSERT, 'Y'), array(DIFF_EQUAL, '5'), array(DIFF_DELETE, 'F'), array(DIFF_EQUAL, '6'), array(DIFF_DELETE, 'G'), array(DIFF_INSERT, 'Z')), dmp()->diff_path2($v_map, '4E5F6G', '4Y56Z'));

	// Double letters
	// Trace a path from back to front.
	$v_map = array();
	array_push( $v_map, array('0,0'=>true) );
	array_push( $v_map, array('0,1'=>true, '1,0'=>true) );
	array_push( $v_map, array('0,2'=>true, '1,1'=>true, '2,0'=>true) );
	array_push( $v_map, array('0,3'=>true, '1,2'=>true, '2,1'=>true, '3,0'=>true) );
	array_push( $v_map, array('0,4'=>true, '1,3'=>true, '3,1'=>true, '4,0'=>true, '4,4'=>true) );
	assertEquivalent(array(array(DIFF_INSERT, 'WX'), array(DIFF_DELETE, 'AB'), array(DIFF_EQUAL, '12')), dmp()->diff_path1($v_map, 'AB12', 'WX12'));

	// Trace a path from front to back.
	$v_map = array();
	array_push( $v_map, array('0,0'=>true) );
	array_push( $v_map, array('0,1'=>true, '1,0'=>true) );
	array_push( $v_map, array('1,1'=>true, '2,0'=>true, '2,4'=>true) );
	array_push( $v_map, array('2,1'=>true, '2,5'=>true, '3,0'=>true, '3,4'=>true) );
	array_push( $v_map, array('2,6'=>true, '3,5'=>true, '4,4'=>true) );
	assertEquivalent(array(array(DIFF_DELETE, 'CD'), array(DIFF_EQUAL, '34'), array(DIFF_INSERT, 'YZ')), dmp()->diff_path2($v_map, 'CD34', '34YZ'));
}

function testDiffMain() {
	// Perform a trivial diff.
	// Null case.
	assertEquivalent(array(array(DIFF_EQUAL, 'abc')), dmp()->diff_main('abc', 'abc', false));

	// Simple insertion.
	assertEquivalent(array(array(DIFF_EQUAL, 'ab'), array(DIFF_INSERT, '123'), array(DIFF_EQUAL, 'c')), dmp()->diff_main('abc', 'ab123c', false));

	// Simple deletion.
	assertEquivalent(array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, '123'), array(DIFF_EQUAL, 'bc')), dmp()->diff_main('a123bc', 'abc', false));

	// Two insertions.
	assertEquivalent(array(array(DIFF_EQUAL, 'a'), array(DIFF_INSERT, '123'), array(DIFF_EQUAL, 'b'), array(DIFF_INSERT, '456'), array(DIFF_EQUAL, 'c')), dmp()->diff_main('abc', 'a123b456c', false));

	// Two deletions.
	assertEquivalent(array(array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, '123'), array(DIFF_EQUAL, 'b'), array(DIFF_DELETE, '456'), array(DIFF_EQUAL, 'c')), dmp()->diff_main('a123b456c', 'abc', false));

	// Perform a real diff.
	// Switch off the timeout.
	dmp()->Diff_Timeout = 0;
	dmp()->Diff_DualThreshold = 32;
	// Simple cases.
	assertEquivalent(array(array(DIFF_DELETE, 'a'), array(DIFF_INSERT, 'b')), dmp()->diff_main('a', 'b', false));

	// zero-check ("0" == false in PHP)
	assertEquivalent(array(array(DIFF_DELETE, '0'), array(DIFF_INSERT, '1')), dmp()->diff_main('0', '1', false));
	
	assertEquivalent(array(array(DIFF_DELETE, 'Apple'), array(DIFF_INSERT, 'Banana'), array(DIFF_EQUAL, 's are a'), array(DIFF_INSERT, 'lso'), array(DIFF_EQUAL, ' fruit.')), dmp()->diff_main('Apples are a fruit.', 'Bananas are also fruit.', false));
	
	$u0680 = mb_chr(0*4096 + 6*256 + 8*16 + 0);
	assertEquivalent(array(array(DIFF_DELETE, 'a'), array(DIFF_INSERT, "$u0680"), array(DIFF_EQUAL, 'x'), array(DIFF_DELETE, "\t"), array(DIFF_INSERT, "\0")), dmp()->diff_main("ax\t", "{$u0680}x\0", false));

	// Overlaps.
	assertEquivalent(array(array(DIFF_DELETE, '1'), array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'y'), array(DIFF_EQUAL, 'b'), array(DIFF_DELETE, '2'), array(DIFF_INSERT, 'xab')), dmp()->diff_main('1ayb2', 'abxab', false));

	assertEquivalent(array(array(DIFF_INSERT, 'xaxcx'), array(DIFF_EQUAL, 'abc'), array(DIFF_DELETE, 'y')), dmp()->diff_main('abcy', 'xaxcxabc', false));

	// Sub-optimal double-ended diff.
	dmp()->Diff_DualThreshold = 2;
	assertEquivalent(array(array(DIFF_INSERT, 'x'), array(DIFF_EQUAL, 'a'), array(DIFF_DELETE, 'b'), array(DIFF_INSERT, 'x'), array(DIFF_EQUAL, 'c'), array(DIFF_DELETE, 'y'), array(DIFF_INSERT, 'xabc')), dmp()->diff_main('abcy', 'xaxcxabc', false));
	dmp()->Diff_DualThreshold = 32;

	// Timeout.
	dmp()->Diff_Timeout = 0.001;  // 1ms
	$a = "`Twas brillig, and the slithy toves\nDid gyre and gimble in the wabe:\nAll mimsy were the borogoves,\nAnd the mome raths outgrabe.\n";
	$b = "I am the very model of a modern major general,\nI\'ve information vegetable, animal, and mineral,\nI know the kings of England, and I quote the fights historical,\nFrom Marathon to Waterloo, in order categorical.\n";
	// Increase the text lengths by 1024 times to ensure a timeout.
	for ($x = 0; $x < 10; $x++) {
		$a = $a . $a;
		$b = $b . $b;
	}
	assertEquals(null, dmp()->diff_map($a, $b));
	dmp()->Diff_Timeout = 0;

	// Test the linemode speedup.
	// Must be long to pass the 200 char cutoff.
	$a = "1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n";
	$b = "abcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\n";
	assertEquivalent(dmp()->diff_main($a, $b, false), dmp()->diff_main($a, $b, true));

	$a = "1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n";
	$b = "abcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n";
	$texts_linemode = diff_rebuildtexts(dmp()->diff_main($a, $b, true));
	$texts_textmode = diff_rebuildtexts(dmp()->diff_main($a, $b, false));
	assertEquivalent($texts_textmode, $texts_linemode);
}


// MATCH TEST FUNCTIONS


function testMatchAlphabet() {
	// Initialise the bitmasks for Bitap.
	// Unique.
	assertEquivalent(array('a'=>4, 'b'=>2, 'c'=>1), dmp()->match_alphabet('abc'));

	// Duplicates.
	assertEquivalent(array('a'=>37, 'b'=>18, 'c'=>8), dmp()->match_alphabet('abcaba'));
}

function testMatchBitap() {
	// Bitap algorithm.
	dmp()->Match_Distance = 100;
	dmp()->Match_Threshold = 0.5;
	// Exact matches.
	assertEquals(5, dmp()->match_bitap('abcdefghijk', 'fgh', 5));

	assertEquals(5, dmp()->match_bitap('abcdefghijk', 'fgh', 0));

	// Fuzzy matches.
	assertEquals(4, dmp()->match_bitap('abcdefghijk', 'efxhi', 0));

	assertEquals(2, dmp()->match_bitap('abcdefghijk', 'cdefxyhijk', 5));

	assertEquals(-1, dmp()->match_bitap('abcdefghijk', 'bxy', 1));

	// Overflow.
	assertEquals(2, dmp()->match_bitap('123456789xx0', '3456789x0', 2));

	// Threshold test.
	dmp()->Match_Threshold = 0.4;
	assertEquals(4, dmp()->match_bitap('abcdefghijk', 'efxyhi', 1));

	dmp()->Match_Threshold = 0.3;
	assertEquals(-1, dmp()->match_bitap('abcdefghijk', 'efxyhi', 1));

	dmp()->Match_Threshold = 0.0;
	assertEquals(1, dmp()->match_bitap('abcdefghijk', 'bcdef', 1));
	dmp()->Match_Threshold = 0.5;

	// Multiple select.
	assertEquals(0, dmp()->match_bitap('abcdexyzabcde', 'abccde', 3));

	assertEquals(8, dmp()->match_bitap('abcdexyzabcde', 'abccde', 5));

	// Distance test.
	dmp()->Match_Distance = 10;  // Strict location.
	assertEquals(-1, dmp()->match_bitap('abcdefghijklmnopqrstuvwxyz', 'abcdefg', 24));

	assertEquals(0, dmp()->match_bitap('abcdefghijklmnopqrstuvwxyz', 'abcdxxefg', 1));

	dmp()->Match_Distance = 1000;  // Loose location.
	assertEquals(0, dmp()->match_bitap('abcdefghijklmnopqrstuvwxyz', 'abcdefg', 24));
}

function testMatchMain() {
	// Full match.
	// Shortcut matches.
	assertEquals(0, dmp()->match_main('abcdef', 'abcdef', 1000));

	assertEquals(-1, dmp()->match_main('', 'abcdef', 1));

	assertEquals(3, dmp()->match_main('abcdef', '', 3));

	assertEquals(3, dmp()->match_main('abcdef', 'de', 3));

	// Beyond end match.
	assertEquals(3, dmp()->match_main("abcdef", "defy", 4));

	// Oversized pattern.
	assertEquals(0, dmp()->match_main("abcdef", "abcdefy", 0));

	// Complex match.
	assertEquals(4, dmp()->match_main('I am the very model of a modern major general.', ' that berry ', 5));
}


// PATCH TEST FUNCTIONS


function testPatchObj() {
	// Patch Object.
	$p = new patch_obj();
	$p->start1 = 20;
	$p->start2 = 21;
	$p->length1 = 18;
	$p->length2 = 17;
	$p->diffs = array(array(DIFF_EQUAL, 'jump'), array(DIFF_DELETE, 's'), array(DIFF_INSERT, 'ed'), array(DIFF_EQUAL, ' over '), array(DIFF_DELETE, 'the'), array(DIFF_INSERT, 'a'), array(DIFF_EQUAL, "\nlaz"));
	$strp = $p->toString();
	assertEquals("@@ -21,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n %0Alaz\n", $strp);
}

function testPatchFromText() {
	assertEquivalent(array(), dmp()->patch_fromText(''));

	$strp = "@@ -21,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n %0Alaz\n";
	$ps = dmp()->patch_fromText($strp);
	assertEquals( $strp, $ps[0]->toString() );

	$ps = dmp()->patch_fromText("@@ -1 +1 @@\n-a\n+b\n");
	assertEquals("@@ -1 +1 @@\n-a\n+b\n", $ps[0]->toString());

	$ps = dmp()->patch_fromText("@@ -1,3 +0,0 @@\n-abc\n");
	assertEquals("@@ -1,3 +0,0 @@\n-abc\n", $ps[0]->toString());

	$ps = dmp()->patch_fromText("@@ -0,0 +1,3 @@\n+abc\n");
	assertEquals("@@ -0,0 +1,3 @@\n+abc\n", $ps[0]->toString());

	// Generates error.
	global $lastException;
	try {
		$res = dmp()->patch_fromText("Bad\nPatch\n");
		if(	$lastException === 'Invalid patch mode "P" in: atch' ){
			assertEquivalent(null, null);
		} else {
			assertEquivalent('Error expected', $res);
		}
	} catch (Exception $e) {
		assertEquivalent(null, null);
	}
}

function testPatchToText() {
	$strp = "@@ -21,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n  laz\n";
	$p = dmp()->patch_fromText($strp);
	assertEquals($strp, dmp()->patch_toText($p));

	$strp = "@@ -1,9 +1,9 @@\n-f\n+F\n oo+fooba\n@@ -7,9 +7,9 @@\n obar\n-,\n+.\n  tes\n";
	$p = dmp()->patch_fromText($strp);
	assertEquals($strp, dmp()->patch_toText($p));
}

function testPatchAddContext() {
	dmp()->Patch_Margin = 4;
	$ps = dmp()->patch_fromText("@@ -21,4 +21,10 @@\n-jump\n+somersault\n");
	dmp()->patch_addContext($ps[0], "The quick brown fox jumps over the lazy dog.");
	assertEquals("@@ -17,12 +17,18 @@\n fox \n-jump\n+somersault\n s ov\n", $ps[0]->toString());

	// Same, but not enough trailing context.
	$ps = dmp()->patch_fromText("@@ -21,4 +21,10 @@\n-jump\n+somersault\n");
	dmp()->patch_addContext($ps[0], "The quick brown fox jumps.");
	assertEquals("@@ -17,10 +17,16 @@\n fox \n-jump\n+somersault\n s.\n", $ps[0]->toString());

	// Same, but not enough leading context.
	$ps = dmp()->patch_fromText("@@ -3 +3,2 @@\n-e\n+at\n");
	dmp()->patch_addContext($ps[0], "The quick brown fox jumps.");
	assertEquals("@@ -1,7 +1,8 @@\n Th\n-e\n+at\n  qui\n", $ps[0]->toString());

	// Same, but with ambiguity.
	$ps = dmp()->patch_fromText("@@ -3 +3,2 @@\n-e\n+at\n");
	dmp()->patch_addContext($ps[0], "The quick brown fox jumps.  The quick brown fox crashes.");
	assertEquals("@@ -1,27 +1,28 @@\n Th\n-e\n+at\n  quick brown fox jumps. \n", $ps[0]->toString());
}

function testPatchMake() {
	$text1 = "The quick brown fox jumps over the lazy dog.";
	$text2 = "That quick brown fox jumped over a lazy dog.";
	// Text2+Text1 inputs.
	$expectedPatch = "@@ -1,8 +1,7 @@\n Th\n-at\n+e\n  qui\n@@ -21,17 +21,18 @@\n jump\n-ed\n+s\n  over \n-a\n+the\n  laz\n";
	// The second patch must be "-21,17 +21,18", not "-22,17 +21,18" due to rolling context.
	$patches = dmp()->patch_make($text2, $text1);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));

	// Text1+Text2 inputs.
	$expectedPatch = "@@ -1,11 +1,12 @@\n Th\n-e\n+at\n  quick b\n@@ -22,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n  laz\n";
	$patches = dmp()->patch_make($text1, $text2);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));

	// Diff input.
	$diffs = dmp()->diff_main($text1, $text2, false);
	$patches = dmp()->patch_make($diffs);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));

	// Text1+Diff inputs.
	$patches = dmp()->patch_make($text1, $diffs);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));

	// Text1+Text2+Diff inputs (deprecated).
	$patches = dmp()->patch_make($text1, $text2, $diffs);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));

	// Character encoding.
	$patches = dmp()->patch_make('`1234567890-=[]\\;\',./', '~!@#$%^&*()_+{}|:"<>?');
	assertEquals('@@ -1,21 +1,21 @@'."\n".'-%601234567890-=%5B%5D%5C;\',./'."\n".'+~!@#$%25%5E&*()_+%7B%7D%7C:%22%3C%3E?'."\n".'', dmp()->patch_toText($patches));


	// Character decoding.
	$diffs = array(array(DIFF_DELETE, '`1234567890-=[]\\;\',./'), array(DIFF_INSERT, '~!@#$%^&*()_+{}|:"<>?'));
	$ps = dmp()->patch_fromText('@@ -1,21 +1,21 @@'."\n".'-%601234567890-=%5B%5D%5C;\',./'."\n".'+~!@#$%25%5E&*()_+%7B%7D%7C:%22%3C%3E?'."\n");
	assertEquivalent($diffs, $ps[0]->diffs);

	// Long string with repeats.
	$text1 = '';
	for ($x = 0; $x < 100; $x++) {
		$text1 .= 'abcdef';
	}
	$text2 = $text1 . "123";
	$expectedPatch = "@@ -573,28 +573,31 @@\n cdefabcdefabcdefabcdefabcdef\n+123\n";
	$patches = dmp()->patch_make($text1, $text2);
	assertEquals($expectedPatch, dmp()->patch_toText($patches));
}

function testPatchSplitMax() {
	// Assumes that dmp()->Match_MaxBits is 32.
	$patches = dmp()->patch_make('abcdefghijklmnopqrstuvwxyz01234567890', 'XabXcdXefXghXijXklXmnXopXqrXstXuvXwxXyzX01X23X45X67X89X0');
	dmp()->patch_splitMax($patches);
	assertEquals("@@ -1,32 +1,46 @@\n+X\n ab\n+X\n cd\n+X\n ef\n+X\n gh\n+X\n ij\n+X\n kl\n+X\n mn\n+X\n op\n+X\n qr\n+X\n st\n+X\n uv\n+X\n wx\n+X\n yz\n+X\n 012345\n@@ -25,13 +39,18 @@\n zX01\n+X\n 23\n+X\n 45\n+X\n 67\n+X\n 89\n+X\n 0\n", dmp()->patch_toText($patches));

	$patches = dmp()->patch_make("abcdef1234567890123456789012345678901234567890123456789012345678901234567890uvwxyz", "abcdefuvwxyz");
	$oldToText = dmp()->patch_toText($patches);
	dmp()->patch_splitMax($patches);
	assertEquals($oldToText, dmp()->patch_toText($patches));

	$patches = dmp()->patch_make("1234567890123456789012345678901234567890123456789012345678901234567890", "abc");
	dmp()->patch_splitMax($patches);
	assertEquals("@@ -1,32 +1,4 @@\n-1234567890123456789012345678\n 9012\n@@ -29,32 +1,4 @@\n-9012345678901234567890123456\n 7890\n@@ -57,14 +1,3 @@\n-78901234567890\n+abc\n", dmp()->patch_toText($patches));

	$patches = dmp()->patch_make('abcdefghij , h : 0 , t : 1 abcdefghij , h : 0 , t : 1 abcdefghij , h : 0 , t : 1', 'abcdefghij , h : 1 , t : 1 abcdefghij , h : 1 , t : 1 abcdefghij , h : 0 , t : 1');
	dmp()->patch_splitMax($patches);
	assertEquals("@@ -2,32 +2,32 @@\n bcdefghij , h : \n-0\n+1\n  , t : 1 abcdef\n@@ -29,32 +29,32 @@\n bcdefghij , h : \n-0\n+1\n  , t : 1 abcdef\n", dmp()->patch_toText($patches));
}

function testPatchAddPadding() {
	// Both edges full.
	$patches = dmp()->patch_make('', 'test');
	assertEquals("@@ -0,0 +1,4 @@\n+test\n", dmp()->patch_toText($patches));
	dmp()->patch_addPadding($patches);
	assertEquals("@@ -1,8 +1,12 @@\n %01%02%03%04\n+test\n %01%02%03%04\n", dmp()->patch_toText($patches));

	// Both edges partial.
	$patches = dmp()->patch_make("XY", "XtestY");
	assertEquals("@@ -1,2 +1,6 @@\n X\n+test\n Y\n", dmp()->patch_toText($patches));
	dmp()->patch_addPadding($patches);
	assertEquals("@@ -2,8 +2,12 @@\n %02%03%04X\n+test\n Y%01%02%03\n", dmp()->patch_toText($patches));

	// Both edges none.
	$patches = dmp()->patch_make("XXXXYYYY", "XXXXtestYYYY");
	assertEquals("@@ -1,8 +1,12 @@\n XXXX\n+test\n YYYY\n", dmp()->patch_toText($patches));
	dmp()->patch_addPadding($patches);
	assertEquals("@@ -5,8 +5,12 @@\n XXXX\n+test\n YYYY\n", dmp()->patch_toText($patches));
}

function testPatchApply() {
	dmp()->Match_Distance = 1000;
	dmp()->Match_Threshold = 0.5;
	dmp()->Patch_DeleteThreshold = 0.5;

	// Exact match.
	$patches = dmp()->patch_make("The quick brown fox jumps over the lazy dog.", "That quick brown fox jumped over a lazy dog.");
	$results = dmp()->patch_apply($patches, "The quick brown fox jumps over the lazy dog.");
	assertEquivalent(array("That quick brown fox jumped over a lazy dog.", array(true, true)), $results);

	// Partial match.
	$results = dmp()->patch_apply($patches, "The quick red rabbit jumps over the tired tiger.");
	assertEquivalent(array("That quick red rabbit jumped over a tired tiger.", array(true, true)), $results);

	// Failed match.
	$results = dmp()->patch_apply($patches, "I am the very model of a modern major general.");
	assertEquivalent(array("I am the very model of a modern major general.", array(false, false)), $results);

	// Big delete, small change.
	$patches = dmp()->patch_make('x1234567890123456789012345678901234567890123456789012345678901234567890y', 'xabcy');
	$results = dmp()->patch_apply($patches, 'x123456789012345678901234567890-----++++++++++-----123456789012345678901234567890y');
	assertEquivalent(array('xabcy', array(true, true)), $results);

	// Big delete, big change 1.
	$patches = dmp()->patch_make('x1234567890123456789012345678901234567890123456789012345678901234567890y', 'xabcy');
	$results = dmp()->patch_apply($patches, 'x12345678901234567890---------------++++++++++---------------12345678901234567890y');
	assertEquivalent(array('xabc12345678901234567890---------------++++++++++---------------12345678901234567890y', array(false, true)), $results);

	// Big delete, big change 2.
	dmp()->Patch_DeleteThreshold = 0.6;
	$patches = dmp()->patch_make('x1234567890123456789012345678901234567890123456789012345678901234567890y', 'xabcy');
	$results = dmp()->patch_apply($patches, 'x12345678901234567890---------------++++++++++---------------12345678901234567890y');
	assertEquivalent(array('xabcy', array(true, true)), $results);
	dmp()->Patch_DeleteThreshold = 0.5;

	// Compensate for failed patch.
	dmp()->Match_Threshold = 0.0;
	dmp()->Match_Distance = 0;
	$patches = dmp()->patch_make('abcdefghijklmnopqrstuvwxyz--------------------1234567890', 'abcXXXXXXXXXXdefghijklmnopqrstuvwxyz--------------------1234567YYYYYYYYYY890');
	$results = dmp()->patch_apply($patches, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ--------------------1234567890');
	assertEquivalent(array('ABCDEFGHIJKLMNOPQRSTUVWXYZ--------------------1234567YYYYYYYYYY890', array(false, true)), $results);
	dmp()->Match_Threshold = 0.5;
	dmp()->Match_Distance = 1000;

	// No side effects.
	$patches = dmp()->patch_make('', 'test');
	$patchstr = dmp()->patch_toText($patches);
	dmp()->patch_apply($patches, '');
	assertEquals($patchstr, dmp()->patch_toText($patches));

	// No side effects with major delete.
	$patches = dmp()->patch_make('The quick brown fox jumps over the lazy dog.', 'Woof');
	$patchstr = dmp()->patch_toText($patches);
	dmp()->patch_apply($patches, 'The quick brown fox jumps over the lazy dog.');
	assertEquals($patchstr, dmp()->patch_toText($patches));

	// Edge exact match.
	$patches = dmp()->patch_make('', 'test');
	$results = dmp()->patch_apply($patches, '');
	assertEquivalent(array('test', array(true)), $results);

	// Near edge exact match.
	$patches = dmp()->patch_make('XY', 'XtestY');
	$results = dmp()->patch_apply($patches, 'XY');
	assertEquivalent(array('XtestY', array(true)), $results);

	// Edge partial match.
	$patches = dmp()->patch_make('y', 'y123');
	$results = dmp()->patch_apply($patches, 'x');
	assertEquivalent(array('x123', array(true)), $results);
}


?>