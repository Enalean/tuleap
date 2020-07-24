<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

$symbol_arr = [

// Greek symbols
'alpha'    => [ 'input' => 'alpha','tag' => 'mi', 'output' => '&#' . hexdec('03B1') . ';'],
'beta'    => [ 'input' => 'beta','tag' => 'mi', 'output' => '&#' . hexdec('03B2') . ';'],
'chi'    => [ 'input' => 'chi','tag' => 'mi', 'output' => '&#' . hexdec('03C7') . ';'],
'delta'    => [ 'input' => 'delta','tag' => 'mi', 'output' => '&#' . hexdec('03B4') . ';'],
'Delta'    => [ 'input' => 'Delta','tag' => 'mo', 'output' => '&#' . hexdec('0394') . ';'],
'epsi'    => [ 'input' => 'epsi','tag' => 'mi', 'output' => '&#' . hexdec('03B5') . ';'],
'varepsilon'    => [ 'input' => 'varepsilon','tag' => 'mi', 'output' => '&#' . hexdec('025B') . ';'],
'eta'    => [ 'input' => 'eta','tag' => 'mi', 'output' => '&#' . hexdec('03B7') . ';'],
'gamma'    => [ 'input' => 'gamma','tag' => 'mi', 'output' => '&#' . hexdec('03B3') . ';'],
'Gamma'    => [ 'input' => 'Gamma','tag' => 'mi', 'output' => '&#' . hexdec('0393') . ';'],
'iota'    => [ 'input' => 'iota','tag' => 'mi', 'output' => '&#' . hexdec('03B9') . ';'],
'kappa'    => [ 'input' => 'kappa','tag' => 'mi', 'output' => '&#' . hexdec('03BA') . ';'],
'lambda'    => [ 'input' => 'lambda','tag' => 'mi', 'output' => '&#' . hexdec('03BB') . ';'],
'Lambda'    => [ 'input' => 'Lambda','tag' => 'mo', 'output' => '&#' . hexdec('039B') . ';'],
'mu'    => [ 'input' => 'mu','tag' => 'mi', 'output' => '&#' . hexdec('03BC') . ';'],
'nu'    => [ 'input' => 'nu','tag' => 'mi', 'output' => '&#' . hexdec('03BD') . ';'],
'omega'    => [ 'input' => 'omega','tag' => 'mi', 'output' => '&#' . hexdec('03C9') . ';'],
'Omega'    => [ 'input' => 'Omega','tag' => 'mo', 'output' => '&#' . hexdec('03A9') . ';'],
'phi'    => [ 'input' => 'phi','tag' => 'mi', 'output' => '&#' . hexdec('03C6') . ';'],
'varphi'    => [ 'input' => 'varphi','tag' => 'mi', 'output' => '&#' . hexdec('03D5') . ';'],
'Phi'    => [ 'input' => 'Phi','tag' => 'mo', 'output' => '&#' . hexdec('03A6') . ';'],
'pi'    => [ 'input' => 'pi','tag' => 'mi', 'output' => '&#' . hexdec('03C0') . ';'],
'Pi'    => [ 'input' => 'Pi','tag' => 'mo', 'output' => '&#' . hexdec('03A0') . ';'],
'psi'    => [ 'input' => 'psi','tag' => 'mi', 'output' => '&#' . hexdec('03C8') . ';'],
'rho'    => [ 'input' => 'rho','tag' => 'mi', 'output' => '&#' . hexdec('03C1') . ';'],
'sigma'    => [ 'input' => 'sigma','tag' => 'mi', 'output' => '&#' . hexdec('03C3') . ';'],
'Sigma'    => [ 'input' => 'Sigma','tag' => 'mo', 'output' => '&#' . hexdec('03A3') . ';'],
'tau'    => [ 'input' => 'tau','tag' => 'mi', 'output' => '&#' . hexdec('03C4') . ';'],
'theta'    => [ 'input' => 'theta','tag' => 'mi', 'output' => '&#' . hexdec('03B8') . ';'],
'vartheta'    => [ 'input' => 'vartheta','tag' => 'mi', 'output' => '&#' . hexdec('03D1') . ';'],
'Theta'    => [ 'input' => 'Theta','tag' => 'mo', 'output' => '&#' . hexdec('0398') . ';'],
'upsilon'    => [ 'input' => 'upsilon','tag' => 'mi', 'output' => '&#' . hexdec('03C5') . ';'],
'xi'    => [ 'input' => 'xi','tag' => 'mi', 'output' => '&#' . hexdec('03BE') . ';'],
'Xi'    => [ 'input' => 'alpha','tag' => 'mo', 'output' => '&#' . hexdec('039E') . ';'],
'zeta'    => [ 'input' => 'zeta','tag' => 'mi', 'output' => '&#' . hexdec('03B6') . ';'],

// Binary operation symbols
'*'        => [ 'input' => '*','tag' => 'mo', 'output' => '&#' . hexdec('22C5') . ';'],
'**'    => [ 'input' => '**','tag' => 'mo', 'output' => '&#' . hexdec('22C6') . ';'],
'//'    => [ 'input' => '//','tag' => 'mo', 'output' => '/'],
'\\\\'    => [ 'input' => '\\\\','tag' => 'mo', 'output' => '\\'],
'xx'    => [ 'input' => 'xx','tag' => 'mo', 'output' => '&#' . hexdec('00D7') . ';'],
'-:'    => [ 'input' => '-:','tag' => 'mo', 'output' => '&#' . hexdec('00F7') . ';'],
'@'        => [ 'input' => '@','tag' => 'mo', 'output' => '&#' . hexdec('2218') . ';'],
'o+'    => [ 'input' => 'o+','tag' => 'mo', 'output' => '&#' . hexdec('2295') . ';'],
'ox'    => [ 'input' => 'ox','tag' => 'mo', 'output' => '&#' . hexdec('2297') . ';'],
'sum'    => [ 'input' => 'sum','tag' => 'mo', 'output' => '&#' . hexdec('2211') . ';', 'underover' => true],
'prod'    => [ 'input' => 'prod','tag' => 'mo', 'output' => '&#' . hexdec('220F') . ';', 'underover' => true],
'^^'    => [ 'input' => '^^','tag' => 'mo', 'output' => '&#' . hexdec('2227') . ';'],
'^^^'    => [ 'input' => '^^^','tag' => 'mo', 'output' => '&#' . hexdec('22C0') . ';', 'underover' => true],
'vv'    => [ 'input' => 'vv','tag' => 'mo', 'output' => '&#' . hexdec('2228') . ';'],
'vvv'    => [ 'input' => 'vvv','tag' => 'mo', 'output' => '&#' . hexdec('22C1') . ';', 'underover' => true],
'nn'    => [ 'input' => 'nn','tag' => 'mo', 'output' => '&#' . hexdec('2229') . ';'],
'nnn'    => [ 'input' => 'nnn','tag' => 'mo', 'output' => '&#' . hexdec('22C5') . ';', 'underover' => true],
'uu'    => [ 'input' => 'uu','tag' => 'mo', 'output' => '&#' . hexdec('222A') . ';'],
'uuu'    => [ 'input' => 'uuu','tag' => 'mo', 'output' => '&#' . hexdec('22C3') . ';', 'underover' => true],

// Binary relation symbols
'!='    => [ 'input' => '!=','tag' => 'mo', 'output' => '&#' . hexdec('2260') . ';'],
'<'        => [ 'input' => '<','tag' => 'mo', 'output' => '&lt;'],
'<='    => [ 'input' => '<=','tag' => 'mo', 'output' => '&#' . hexdec('2264') . ';'],
'lt='    => [ 'input' => 'lt=','tag' => 'mo', 'output' => '&#' . hexdec('2264') . ';'],
'>'        => [ 'input' => '>','tag' => 'mo', 'output' => '&gt;'],
'>='    => [ 'input' => '>=','tag' => 'mo', 'output' => '&#' . hexdec('2265') . ';'],
'qeq'    => [ 'input' => 'geq','tag' => 'mo', 'output' => '&#' . hexdec('2265') . ';'],
'-<'    => [ 'input' => '-<','tag' => 'mo', 'output' => '&#' . hexdec('227A') . ';'],
'-lt'    => [ 'input' => '-lt','tag' => 'mo', 'output' => '&#' . hexdec('227A') . ';'],
'>-'    => [ 'input' => '>-','tag' => 'mo', 'output' => '&#' . hexdec('227B') . ';'],
'in'    => [ 'input' => 'in','tag' => 'mo', 'output' => '&#' . hexdec('2208') . ';'],
'!in'    => [ 'input' => '!in','tag' => 'mo', 'output' => '&#' . hexdec('2209') . ';'],
'sub'    => [ 'input' => 'sub','tag' => 'mo', 'output' => '&#' . hexdec('2282') . ';'],
'sup'    => [ 'input' => 'sup','tag' => 'mo', 'output' => '&#' . hexdec('2283') . ';'],
'sube'    => [ 'input' => 'sube','tag' => 'mo', 'output' => '&#' . hexdec('2286') . ';'],
'supe'    => [ 'input' => 'supe','tag' => 'mo', 'output' => '&#' . hexdec('2287') . ';'],
'-='    => [ 'input' => '-=','tag' => 'mo', 'output' => '&#' . hexdec('2261') . ';'],
'~='    => [ 'input' => '~=','tag' => 'mo', 'output' => '&#' . hexdec('2245') . ';'],
'~~'    => [ 'input' => '~~','tag' => 'mo', 'output' => '&#' . hexdec('2248') . ';'],
'prop'    => [ 'input' => 'prop','tag' => 'mo', 'output' => '&#' . hexdec('221D') . ';'],

// Logical symbols
'and'    => [ 'input' => 'and','tag' => 'mtext', 'output' => 'and', 'space' => '1ex'],
'or'    => [ 'input' => 'or','tag' => 'mtext', 'output' => 'or', 'space' => '1ex'],
'not'    => [ 'input' => 'not','tag' => 'mo', 'output' => '&#' . hexdec('00AC') . ';'],
'=>'    => [ 'input' => '=>','tag' => 'mo', 'output' => '&#' . hexdec('21D2') . ';'],
'if'    => [ 'input' => 'if','tag' => 'mo', 'output' => 'if', 'space' => '1ex'],
'iff'    => [ 'input' => 'iff','tag' => 'mo', 'output' => '&#' . hexdec('21D4') . ';'],
'AA'    => [ 'input' => 'AA','tag' => 'mo', 'output' => '&#' . hexdec('2200') . ';'],
'EE'    => [ 'input' => 'EE','tag' => 'mo', 'output' => '&#' . hexdec('2203') . ';'],
'_|_'    => [ 'input' => '_|_','tag' => 'mo', 'output' => '&#' . hexdec('22A5') . ';'],
'TT'    => [ 'input' => 'TT','tag' => 'mo', 'output' => '&#' . hexdec('22A4') . ';'],
'|-'    => [ 'input' => '|-','tag' => 'mo', 'output' => '&#' . hexdec('22A2') . ';'],
'|='    => [ 'input' => '|=','tag' => 'mo', 'output' => '&#' . hexdec('22A8') . ';'],

// Miscellaneous symbols
'int'    => [ 'input' => 'int','tag' => 'mo', 'output' => '&#' . hexdec('222B') . ';'],
'oint'    => [ 'input' => 'oint','tag' => 'mo', 'output' => '&#' . hexdec('222E') . ';'],
'del'    => [ 'input' => 'del','tag' => 'mo', 'output' => '&#' . hexdec('2202') . ';'],
'grad'    => [ 'input' => 'grad','tag' => 'mo', 'output' => '&#' . hexdec('2207') . ';'],
'+-'    => [ 'input' => '+-','tag' => 'mo', 'output' => '&#' . hexdec('00B1') . ';'],
'O/'    => [ 'input' => '0/','tag' => 'mo', 'output' => '&#' . hexdec('2205') . ';'],
'oo'    => [ 'input' => 'oo','tag' => 'mo', 'output' => '&#' . hexdec('221E') . ';'],
'aleph'    => [ 'input' => 'aleph','tag' => 'mo', 'output' => '&#' . hexdec('2135') . ';'],
'...'    => [ 'input' => 'int','tag' => 'mo', 'output' => '...'],
'~'    => [ 'input' => '!~','tag' => 'mo', 'output' => '&#' . hexdec('0020') . ';'],
'\\ '    => [ 'input' => '~','tag' => 'mo', 'output' => '&#' . hexdec('00A0') . ';'],
'quad'    => [ 'input' => 'quad','tag' => 'mo', 'output' => '&#' . hexdec('00A0') . ';&#' . hexdec('00A0') . ';'],
'qquad'    => [ 'input' => 'qquad','tag' => 'mo', 'output' =>    '&#' . hexdec('00A0') .
                                                            ';&#' . hexdec('00A0') .
                                                            ';&#' . hexdec('00A0') . ';'],

'cdots'    => [ 'input' => 'cdots','tag' => 'mo', 'output' => '&#' . hexdec('22EF') . ';'],
'diamond'    => [ 'input' => 'diamond','tag' => 'mo', 'output' => '&#' . hexdec('22C4') . ';'],
'square'    => [ 'input' => 'square','tag' => 'mo', 'output' => '&#' . hexdec('25A1') . ';'],
'|_'    => [ 'input' => '|_','tag' => 'mo', 'output' => '&#' . hexdec('230A') . ';'],
'_|'    => [ 'input' => '_|','tag' => 'mo', 'output' => '&#' . hexdec('230B') . ';'],
'|~'    => [ 'input' => '|~','tag' => 'mo', 'output' => '&#' . hexdec('2308') . ';'],
'~|'    => [ 'input' => '~|','tag' => 'mo', 'output' => '&#' . hexdec('2309') . ';'],
'CC'    => [ 'input' => 'CC','tag' => 'mo', 'output' => '&#' . hexdec('2102') . ';'],
'NN'    => [ 'input' => 'NN','tag' => 'mo', 'output' => '&#' . hexdec('2115') . ';'],
'QQ'    => [ 'input' => 'QQ','tag' => 'mo', 'output' => '&#' . hexdec('211A') . ';'],
'RR'    => [ 'input' => 'RR','tag' => 'mo', 'output' => '&#' . hexdec('211D') . ';'],
'ZZ'    => [ 'input' => 'ZZ','tag' => 'mo', 'output' => '&#' . hexdec('2124') . ';'],

// Standard functions
'lim'    => [ 'input' => 'lim','tag' => 'mo', 'output' => 'lim', 'underover' => true],
'sin'    => [ 'input' => 'sin','tag' => 'mo', 'output' => 'sin'],
'cos'    => [ 'input' => 'cos', 'tag' => 'mo', 'output' => 'cos'],
'tan'    => [ 'input' => 'tan', 'tag' => 'mo', 'output' => 'tan'],
'sinh'    => [ 'input' => 'sinh','tag' => 'mo', 'output' => 'sinh'],
'cosh'    => [ 'input' => 'cosh', 'tag' => 'mo', 'output' => 'cosh'],
'tanh'    => [ 'input' => 'tanh', 'tag' => 'mo', 'output' => 'tanh'],
'cot'    => [ 'input' => 'cot','tag' => 'mo', 'output' => 'cot'],
'sec'    => [ 'input' => 'sec', 'tag' => 'mo', 'output' => 'sec'],
'csc'    => [ 'input' => 'csc', 'tag' => 'mo', 'output' => 'cosec'],
'coth'    => [ 'input' => 'coth','tag' => 'mo', 'output' => 'coth'],
'sech'    => [ 'input' => 'sech', 'tag' => 'mo', 'output' => 'sech'],
'csch'    => [ 'input' => 'csch', 'tag' => 'mo', 'output' => 'cosech'],
'log'    => [ 'input' => 'log', 'tag' => 'mo', 'output' => 'log'],
'ln'    => [ 'input' => 'ln', 'tag' => 'mo', 'output' => 'ln'],
'det'    => [ 'input' => 'det', 'tag' => 'mo', 'output' => 'det'],
'dim'    => [ 'input' => 'dim', 'tag' => 'mo', 'output' => 'dim'],
'mod'    => [ 'input' => 'mod', 'tag' => 'mo', 'output' => 'mod'],
'gcd'    => [ 'input' => 'csc', 'tag' => 'mo', 'output' => 'gcd'],
'lcm'    => [ 'input' => 'csc', 'tag' => 'mo', 'output' => 'lcm'],

// Arrows
'uarr'    => [ 'input' => 'uarr', 'tag' => 'mo', 'output' => '&#' . hexdec('2191') . ';'],
'darr'    => [ 'input' => 'darr', 'tag' => 'mo', 'output' => '&#' . hexdec('2193') . ';'],
'rarr'    => [ 'input' => 'rarr', 'tag' => 'mo', 'output' => '&#' . hexdec('2192') . ';'],
'->'    => [ 'input' => '->', 'tag' => 'mo', 'output' => '&#' . hexdec('2192') . ';'],
'larr'    => [ 'input' => 'larr', 'tag' => 'mo', 'output' => '&#' . hexdec('2190') . ';'],
'harr'    => [ 'input' => 'harr', 'tag' => 'mo', 'output' => '&#' . hexdec('2194') . ';'],
'rArr'    => [ 'input' => 'rArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D2') . ';'],
'lArr'    => [ 'input' => 'lArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D0') . ';'],
'hArr'    => [ 'input' => 'hArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D4') . ';'],

// Commands with argument
'sqrt'    => [ 'input' => 'sqrt', 'tag' => 'msqrt', 'output' => 'sqrt', 'unary' => true ],
'root'    => [ 'input' => 'root', 'tag' => 'mroot', 'output' => 'root', 'binary' => true ],
'frac'    => [ 'input' => 'frac', 'tag' => 'mfrac', 'output' => '/', 'binary' => true],
'/'        => [ 'input' => '/', 'tag' => 'mfrac', 'output' => '/', 'infix' => true],
'_'        => [ 'input' => '_', 'tag' => 'msub', 'output' => '_', 'infix' => true],
'^'        => [ 'input' => '^', 'tag' => 'msup', 'output' => '^', 'infix' => true],
'hat'    => [ 'input' => 'hat', 'tag' => 'mover', 'output' => '&#' . hexdec('005E') . ';', 'unary' => true, 'acc' => true],
'bar'    => [ 'input' => 'bar', 'tag' => 'mover', 'output' => '&#' . hexdec('00AF') . ';', 'unary' => true, 'acc' => true],
'vec'    => [ 'input' => 'vec', 'tag' => 'mover', 'output' => '&#' . hexdec('2192') . ';', 'unary' => true, 'acc' => true],
'dot'    => [ 'input' => 'dot', 'tag' => 'mover', 'output' => '.', 'unary' => true, 'acc' => true],
'ddot'    => [ 'input' => 'ddot', 'tag' => 'mover', 'output' => '..', 'unary' => true, 'acc' => true],
'ul'    => [ 'input' => 'ul', 'tag' => 'munder', 'output' => '&#' . hexdec('0332') . ';', 'unary' => true, 'acc' => true],
'avec'    => [ 'input' => 'avec', 'tag' => 'munder', 'output' => '~', 'unary' => true, 'acc' => true],
'text'    => [ 'input' => 'text', 'tag' => 'mtext', 'output' => 'text', 'unary' => true],
'mbox'    => [ 'input' => 'mbox', 'tag' => 'mtext', 'output' => 'mbox', 'unary' => true],

// Grouping brackets
'('        => [ 'input' => '(', 'tag' => 'mo', 'output' => '(', 'left_bracket' => true],
')'        => [ 'input' => ')', 'tag' => 'mo', 'output' => ')', 'right_bracket' => true],
'['        => [ 'input' => '[', 'tag' => 'mo', 'output' => '[', 'left_bracket' => true],
']'        => [ 'input' => ']', 'tag' => 'mo', 'output' => ']', 'right_bracket' => true],
'{'        => [ 'input' => '{', 'tag' => 'mo', 'output' => '{', 'left_bracket' => true],
'}'        => [ 'input' => '}', 'tag' => 'mo', 'output' => '}', 'right_bracket' => true],
'(:'    => [ 'input' => '(:', 'tag' => 'mo', 'output' => '&#' . hexdec('2329') . ';', 'left_bracket' => true],
':)'    => [ 'input' => ':)', 'tag' => 'mo', 'output' => '&#' . hexdec('232A') . ';', 'right_bracket' => true],
'{:'    => [ 'input' => '{:', 'tag' => 'mo', 'output' => '{:', 'left_bracket' => true, 'invisible' => true],
':}'    => [ 'input' => ':}', 'tag' => 'mo', 'output' => ':}', 'right_bracket' => true ,'invisible' => true]
];
