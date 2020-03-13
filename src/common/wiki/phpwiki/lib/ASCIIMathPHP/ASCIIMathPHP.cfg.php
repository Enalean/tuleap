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

$symbol_arr = array(

// Greek symbols
'alpha'    => array( 'input' => 'alpha','tag' => 'mi', 'output' => '&#' . hexdec('03B1') . ';'),
'beta'    => array( 'input' => 'beta','tag' => 'mi', 'output' => '&#' . hexdec('03B2') . ';'),
'chi'    => array( 'input' => 'chi','tag' => 'mi', 'output' => '&#' . hexdec('03C7') . ';'),
'delta'    => array( 'input' => 'delta','tag' => 'mi', 'output' => '&#' . hexdec('03B4') . ';'),
'Delta'    => array( 'input' => 'Delta','tag' => 'mo', 'output' => '&#' . hexdec('0394') . ';'),
'epsi'    => array( 'input' => 'epsi','tag' => 'mi', 'output' => '&#' . hexdec('03B5') . ';'),
'varepsilon'    => array( 'input' => 'varepsilon','tag' => 'mi', 'output' => '&#' . hexdec('025B') . ';'),
'eta'    => array( 'input' => 'eta','tag' => 'mi', 'output' => '&#' . hexdec('03B7') . ';'),
'gamma'    => array( 'input' => 'gamma','tag' => 'mi', 'output' => '&#' . hexdec('03B3') . ';'),
'Gamma'    => array( 'input' => 'Gamma','tag' => 'mi', 'output' => '&#' . hexdec('0393') . ';'),
'iota'    => array( 'input' => 'iota','tag' => 'mi', 'output' => '&#' . hexdec('03B9') . ';'),
'kappa'    => array( 'input' => 'kappa','tag' => 'mi', 'output' => '&#' . hexdec('03BA') . ';'),
'lambda'    => array( 'input' => 'lambda','tag' => 'mi', 'output' => '&#' . hexdec('03BB') . ';'),
'Lambda'    => array( 'input' => 'Lambda','tag' => 'mo', 'output' => '&#' . hexdec('039B') . ';'),
'mu'    => array( 'input' => 'mu','tag' => 'mi', 'output' => '&#' . hexdec('03BC') . ';'),
'nu'    => array( 'input' => 'nu','tag' => 'mi', 'output' => '&#' . hexdec('03BD') . ';'),
'omega'    => array( 'input' => 'omega','tag' => 'mi', 'output' => '&#' . hexdec('03C9') . ';'),
'Omega'    => array( 'input' => 'Omega','tag' => 'mo', 'output' => '&#' . hexdec('03A9') . ';'),
'phi'    => array( 'input' => 'phi','tag' => 'mi', 'output' => '&#' . hexdec('03C6') . ';'),
'varphi'    => array( 'input' => 'varphi','tag' => 'mi', 'output' => '&#' . hexdec('03D5') . ';'),
'Phi'    => array( 'input' => 'Phi','tag' => 'mo', 'output' => '&#' . hexdec('03A6') . ';'),
'pi'    => array( 'input' => 'pi','tag' => 'mi', 'output' => '&#' . hexdec('03C0') . ';'),
'Pi'    => array( 'input' => 'Pi','tag' => 'mo', 'output' => '&#' . hexdec('03A0') . ';'),
'psi'    => array( 'input' => 'psi','tag' => 'mi', 'output' => '&#' . hexdec('03C8') . ';'),
'rho'    => array( 'input' => 'rho','tag' => 'mi', 'output' => '&#' . hexdec('03C1') . ';'),
'sigma'    => array( 'input' => 'sigma','tag' => 'mi', 'output' => '&#' . hexdec('03C3') . ';'),
'Sigma'    => array( 'input' => 'Sigma','tag' => 'mo', 'output' => '&#' . hexdec('03A3') . ';'),
'tau'    => array( 'input' => 'tau','tag' => 'mi', 'output' => '&#' . hexdec('03C4') . ';'),
'theta'    => array( 'input' => 'theta','tag' => 'mi', 'output' => '&#' . hexdec('03B8') . ';'),
'vartheta'    => array( 'input' => 'vartheta','tag' => 'mi', 'output' => '&#' . hexdec('03D1') . ';'),
'Theta'    => array( 'input' => 'Theta','tag' => 'mo', 'output' => '&#' . hexdec('0398') . ';'),
'upsilon'    => array( 'input' => 'upsilon','tag' => 'mi', 'output' => '&#' . hexdec('03C5') . ';'),
'xi'    => array( 'input' => 'xi','tag' => 'mi', 'output' => '&#' . hexdec('03BE') . ';'),
'Xi'    => array( 'input' => 'alpha','tag' => 'mo', 'output' => '&#' . hexdec('039E') . ';'),
'zeta'    => array( 'input' => 'zeta','tag' => 'mi', 'output' => '&#' . hexdec('03B6') . ';'),

// Binary operation symbols
'*'        => array( 'input' => '*','tag' => 'mo', 'output' => '&#' . hexdec('22C5') . ';'),
'**'    => array( 'input' => '**','tag' => 'mo', 'output' => '&#' . hexdec('22C6') . ';'),
'//'    => array( 'input' => '//','tag' => 'mo', 'output' => '/'),
'\\\\'    => array( 'input' => '\\\\','tag' => 'mo', 'output' => '\\'),
'xx'    => array( 'input' => 'xx','tag' => 'mo', 'output' => '&#' . hexdec('00D7') . ';'),
'-:'    => array( 'input' => '-:','tag' => 'mo', 'output' => '&#' . hexdec('00F7') . ';'),
'@'        => array( 'input' => '@','tag' => 'mo', 'output' => '&#' . hexdec('2218') . ';'),
'o+'    => array( 'input' => 'o+','tag' => 'mo', 'output' => '&#' . hexdec('2295') . ';'),
'ox'    => array( 'input' => 'ox','tag' => 'mo', 'output' => '&#' . hexdec('2297') . ';'),
'sum'    => array( 'input' => 'sum','tag' => 'mo', 'output' => '&#' . hexdec('2211') . ';', 'underover' => true),
'prod'    => array( 'input' => 'prod','tag' => 'mo', 'output' => '&#' . hexdec('220F') . ';', 'underover' => true),
'^^'    => array( 'input' => '^^','tag' => 'mo', 'output' => '&#' . hexdec('2227') . ';'),
'^^^'    => array( 'input' => '^^^','tag' => 'mo', 'output' => '&#' . hexdec('22C0') . ';', 'underover' => true),
'vv'    => array( 'input' => 'vv','tag' => 'mo', 'output' => '&#' . hexdec('2228') . ';'),
'vvv'    => array( 'input' => 'vvv','tag' => 'mo', 'output' => '&#' . hexdec('22C1') . ';', 'underover' => true),
'nn'    => array( 'input' => 'nn','tag' => 'mo', 'output' => '&#' . hexdec('2229') . ';'),
'nnn'    => array( 'input' => 'nnn','tag' => 'mo', 'output' => '&#' . hexdec('22C5') . ';', 'underover' => true),
'uu'    => array( 'input' => 'uu','tag' => 'mo', 'output' => '&#' . hexdec('222A') . ';'),
'uuu'    => array( 'input' => 'uuu','tag' => 'mo', 'output' => '&#' . hexdec('22C3') . ';', 'underover' => true),

// Binary relation symbols
'!='    => array( 'input' => '!=','tag' => 'mo', 'output' => '&#' . hexdec('2260') . ';'),
'<'        => array( 'input' => '<','tag' => 'mo', 'output' => '&lt;'),
'<='    => array( 'input' => '<=','tag' => 'mo', 'output' => '&#' . hexdec('2264') . ';'),
'lt='    => array( 'input' => 'lt=','tag' => 'mo', 'output' => '&#' . hexdec('2264') . ';'),
'>'        => array( 'input' => '>','tag' => 'mo', 'output' => '&gt;'),
'>='    => array( 'input' => '>=','tag' => 'mo', 'output' => '&#' . hexdec('2265') . ';'),
'qeq'    => array( 'input' => 'geq','tag' => 'mo', 'output' => '&#' . hexdec('2265') . ';'),
'-<'    => array( 'input' => '-<','tag' => 'mo', 'output' => '&#' . hexdec('227A') . ';'),
'-lt'    => array( 'input' => '-lt','tag' => 'mo', 'output' => '&#' . hexdec('227A') . ';'),
'>-'    => array( 'input' => '>-','tag' => 'mo', 'output' => '&#' . hexdec('227B') . ';'),
'in'    => array( 'input' => 'in','tag' => 'mo', 'output' => '&#' . hexdec('2208') . ';'),
'!in'    => array( 'input' => '!in','tag' => 'mo', 'output' => '&#' . hexdec('2209') . ';'),
'sub'    => array( 'input' => 'sub','tag' => 'mo', 'output' => '&#' . hexdec('2282') . ';'),
'sup'    => array( 'input' => 'sup','tag' => 'mo', 'output' => '&#' . hexdec('2283') . ';'),
'sube'    => array( 'input' => 'sube','tag' => 'mo', 'output' => '&#' . hexdec('2286') . ';'),
'supe'    => array( 'input' => 'supe','tag' => 'mo', 'output' => '&#' . hexdec('2287') . ';'),
'-='    => array( 'input' => '-=','tag' => 'mo', 'output' => '&#' . hexdec('2261') . ';'),
'~='    => array( 'input' => '~=','tag' => 'mo', 'output' => '&#' . hexdec('2245') . ';'),
'~~'    => array( 'input' => '~~','tag' => 'mo', 'output' => '&#' . hexdec('2248') . ';'),
'prop'    => array( 'input' => 'prop','tag' => 'mo', 'output' => '&#' . hexdec('221D') . ';'),

// Logical symbols
'and'    => array( 'input' => 'and','tag' => 'mtext', 'output' => 'and', 'space' => '1ex'),
'or'    => array( 'input' => 'or','tag' => 'mtext', 'output' => 'or', 'space' => '1ex'),
'not'    => array( 'input' => 'not','tag' => 'mo', 'output' => '&#' . hexdec('00AC') . ';'),
'=>'    => array( 'input' => '=>','tag' => 'mo', 'output' => '&#' . hexdec('21D2') . ';'),
'if'    => array( 'input' => 'if','tag' => 'mo', 'output' => 'if', 'space' => '1ex'),
'iff'    => array( 'input' => 'iff','tag' => 'mo', 'output' => '&#' . hexdec('21D4') . ';'),
'AA'    => array( 'input' => 'AA','tag' => 'mo', 'output' => '&#' . hexdec('2200') . ';'),
'EE'    => array( 'input' => 'EE','tag' => 'mo', 'output' => '&#' . hexdec('2203') . ';'),
'_|_'    => array( 'input' => '_|_','tag' => 'mo', 'output' => '&#' . hexdec('22A5') . ';'),
'TT'    => array( 'input' => 'TT','tag' => 'mo', 'output' => '&#' . hexdec('22A4') . ';'),
'|-'    => array( 'input' => '|-','tag' => 'mo', 'output' => '&#' . hexdec('22A2') . ';'),
'|='    => array( 'input' => '|=','tag' => 'mo', 'output' => '&#' . hexdec('22A8') . ';'),

// Miscellaneous symbols
'int'    => array( 'input' => 'int','tag' => 'mo', 'output' => '&#' . hexdec('222B') . ';'),
'oint'    => array( 'input' => 'oint','tag' => 'mo', 'output' => '&#' . hexdec('222E') . ';'),
'del'    => array( 'input' => 'del','tag' => 'mo', 'output' => '&#' . hexdec('2202') . ';'),
'grad'    => array( 'input' => 'grad','tag' => 'mo', 'output' => '&#' . hexdec('2207') . ';'),
'+-'    => array( 'input' => '+-','tag' => 'mo', 'output' => '&#' . hexdec('00B1') . ';'),
'O/'    => array( 'input' => '0/','tag' => 'mo', 'output' => '&#' . hexdec('2205') . ';'),
'oo'    => array( 'input' => 'oo','tag' => 'mo', 'output' => '&#' . hexdec('221E') . ';'),
'aleph'    => array( 'input' => 'aleph','tag' => 'mo', 'output' => '&#' . hexdec('2135') . ';'),
'...'    => array( 'input' => 'int','tag' => 'mo', 'output' => '...'),
'~'    => array( 'input' => '!~','tag' => 'mo', 'output' => '&#' . hexdec('0020') . ';'),
'\\ '    => array( 'input' => '~','tag' => 'mo', 'output' => '&#' . hexdec('00A0') . ';'),
'quad'    => array( 'input' => 'quad','tag' => 'mo', 'output' => '&#' . hexdec('00A0') . ';&#' . hexdec('00A0') . ';'),
'qquad'    => array( 'input' => 'qquad','tag' => 'mo', 'output' =>    '&#' . hexdec('00A0') .
                                                            ';&#' . hexdec('00A0') .
                                                            ';&#' . hexdec('00A0') . ';'),

'cdots'    => array( 'input' => 'cdots','tag' => 'mo', 'output' => '&#' . hexdec('22EF') . ';'),
'diamond'    => array( 'input' => 'diamond','tag' => 'mo', 'output' => '&#' . hexdec('22C4') . ';'),
'square'    => array( 'input' => 'square','tag' => 'mo', 'output' => '&#' . hexdec('25A1') . ';'),
'|_'    => array( 'input' => '|_','tag' => 'mo', 'output' => '&#' . hexdec('230A') . ';'),
'_|'    => array( 'input' => '_|','tag' => 'mo', 'output' => '&#' . hexdec('230B') . ';'),
'|~'    => array( 'input' => '|~','tag' => 'mo', 'output' => '&#' . hexdec('2308') . ';'),
'~|'    => array( 'input' => '~|','tag' => 'mo', 'output' => '&#' . hexdec('2309') . ';'),
'CC'    => array( 'input' => 'CC','tag' => 'mo', 'output' => '&#' . hexdec('2102') . ';'),
'NN'    => array( 'input' => 'NN','tag' => 'mo', 'output' => '&#' . hexdec('2115') . ';'),
'QQ'    => array( 'input' => 'QQ','tag' => 'mo', 'output' => '&#' . hexdec('211A') . ';'),
'RR'    => array( 'input' => 'RR','tag' => 'mo', 'output' => '&#' . hexdec('211D') . ';'),
'ZZ'    => array( 'input' => 'ZZ','tag' => 'mo', 'output' => '&#' . hexdec('2124') . ';'),

// Standard functions
'lim'    => array( 'input' => 'lim','tag' => 'mo', 'output' => 'lim', 'underover' => true),
'sin'    => array( 'input' => 'sin','tag' => 'mo', 'output' => 'sin'),
'cos'    => array( 'input' => 'cos', 'tag' => 'mo', 'output' => 'cos'),
'tan'    => array( 'input' => 'tan', 'tag' => 'mo', 'output' => 'tan'),
'sinh'    => array( 'input' => 'sinh','tag' => 'mo', 'output' => 'sinh'),
'cosh'    => array( 'input' => 'cosh', 'tag' => 'mo', 'output' => 'cosh'),
'tanh'    => array( 'input' => 'tanh', 'tag' => 'mo', 'output' => 'tanh'),
'cot'    => array( 'input' => 'cot','tag' => 'mo', 'output' => 'cot'),
'sec'    => array( 'input' => 'sec', 'tag' => 'mo', 'output' => 'sec'),
'csc'    => array( 'input' => 'csc', 'tag' => 'mo', 'output' => 'cosec'),
'coth'    => array( 'input' => 'coth','tag' => 'mo', 'output' => 'coth'),
'sech'    => array( 'input' => 'sech', 'tag' => 'mo', 'output' => 'sech'),
'csch'    => array( 'input' => 'csch', 'tag' => 'mo', 'output' => 'cosech'),
'log'    => array( 'input' => 'log', 'tag' => 'mo', 'output' => 'log'),
'ln'    => array( 'input' => 'ln', 'tag' => 'mo', 'output' => 'ln'),
'det'    => array( 'input' => 'det', 'tag' => 'mo', 'output' => 'det'),
'dim'    => array( 'input' => 'dim', 'tag' => 'mo', 'output' => 'dim'),
'mod'    => array( 'input' => 'mod', 'tag' => 'mo', 'output' => 'mod'),
'gcd'    => array( 'input' => 'csc', 'tag' => 'mo', 'output' => 'gcd'),
'lcm'    => array( 'input' => 'csc', 'tag' => 'mo', 'output' => 'lcm'),

// Arrows
'uarr'    => array( 'input' => 'uarr', 'tag' => 'mo', 'output' => '&#' . hexdec('2191') . ';'),
'darr'    => array( 'input' => 'darr', 'tag' => 'mo', 'output' => '&#' . hexdec('2193') . ';'),
'rarr'    => array( 'input' => 'rarr', 'tag' => 'mo', 'output' => '&#' . hexdec('2192') . ';'),
'->'    => array( 'input' => '->', 'tag' => 'mo', 'output' => '&#' . hexdec('2192') . ';'),
'larr'    => array( 'input' => 'larr', 'tag' => 'mo', 'output' => '&#' . hexdec('2190') . ';'),
'harr'    => array( 'input' => 'harr', 'tag' => 'mo', 'output' => '&#' . hexdec('2194') . ';'),
'rArr'    => array( 'input' => 'rArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D2') . ';'),
'lArr'    => array( 'input' => 'lArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D0') . ';'),
'hArr'    => array( 'input' => 'hArr', 'tag' => 'mo', 'output' => '&#' . hexdec('21D4') . ';'),

// Commands with argument
'sqrt'    => array( 'input' => 'sqrt', 'tag' => 'msqrt', 'output' => 'sqrt', 'unary' => true ),
'root'    => array( 'input' => 'root', 'tag' => 'mroot', 'output' => 'root', 'binary' => true ),
'frac'    => array( 'input' => 'frac', 'tag' => 'mfrac', 'output' => '/', 'binary' => true),
'/'        => array( 'input' => '/', 'tag' => 'mfrac', 'output' => '/', 'infix' => true),
'_'        => array( 'input' => '_', 'tag' => 'msub', 'output' => '_', 'infix' => true),
'^'        => array( 'input' => '^', 'tag' => 'msup', 'output' => '^', 'infix' => true),
'hat'    => array( 'input' => 'hat', 'tag' => 'mover', 'output' => '&#' . hexdec('005E') . ';', 'unary' => true, 'acc' => true),
'bar'    => array( 'input' => 'bar', 'tag' => 'mover', 'output' => '&#' . hexdec('00AF') . ';', 'unary' => true, 'acc' => true),
'vec'    => array( 'input' => 'vec', 'tag' => 'mover', 'output' => '&#' . hexdec('2192') . ';', 'unary' => true, 'acc' => true),
'dot'    => array( 'input' => 'dot', 'tag' => 'mover', 'output' => '.', 'unary' => true, 'acc' => true),
'ddot'    => array( 'input' => 'ddot', 'tag' => 'mover', 'output' => '..', 'unary' => true, 'acc' => true),
'ul'    => array( 'input' => 'ul', 'tag' => 'munder', 'output' => '&#' . hexdec('0332') . ';', 'unary' => true, 'acc' => true),
'avec'    => array( 'input' => 'avec', 'tag' => 'munder', 'output' => '~', 'unary' => true, 'acc' => true),
'text'    => array( 'input' => 'text', 'tag' => 'mtext', 'output' => 'text', 'unary' => true),
'mbox'    => array( 'input' => 'mbox', 'tag' => 'mtext', 'output' => 'mbox', 'unary' => true),

// Grouping brackets
'('        => array( 'input' => '(', 'tag' => 'mo', 'output' => '(', 'left_bracket' => true),
')'        => array( 'input' => ')', 'tag' => 'mo', 'output' => ')', 'right_bracket' => true),
'['        => array( 'input' => '[', 'tag' => 'mo', 'output' => '[', 'left_bracket' => true),
']'        => array( 'input' => ']', 'tag' => 'mo', 'output' => ']', 'right_bracket' => true),
'{'        => array( 'input' => '{', 'tag' => 'mo', 'output' => '{', 'left_bracket' => true),
'}'        => array( 'input' => '}', 'tag' => 'mo', 'output' => '}', 'right_bracket' => true),
'(:'    => array( 'input' => '(:', 'tag' => 'mo', 'output' => '&#' . hexdec('2329') . ';', 'left_bracket' => true),
':)'    => array( 'input' => ':)', 'tag' => 'mo', 'output' => '&#' . hexdec('232A') . ';', 'right_bracket' => true),
'{:'    => array( 'input' => '{:', 'tag' => 'mo', 'output' => '{:', 'left_bracket' => true, 'invisible' => true),
':}'    => array( 'input' => ':}', 'tag' => 'mo', 'output' => ':}', 'right_bracket' => true ,'invisible' => true)
);
