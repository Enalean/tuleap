<?php
/*
$Id: createntlm.inc,v 1.4 2006/03/08 19:36:01 gruberroland Exp $

  This code is part of LDAP Account Manager (http://www.sourceforge.net/projects/lam)
  Copyright (C) 2004 - 2006 Roland Gruber

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
* This class provides functions to calculate Samba NT and LM hashes.
*
* The code is a conversion from createntlm.pl (Benjamin Kuit) and smbdes.c/md4.c (Andrew Tridgell).
*
* @author Roland Gruber
*
* @package modules
*/

/**
* Calculates NT and LM hashes.
*
* The important functions are lmhash($password) and nthash($password).
*
* @package modules
*/
class smbHash {

# Contants used in lanlam hash calculations
# Ported from SAMBA/source/libsmb/smbdes.c:perm1[56]
var $perm1 = array(57, 49, 41, 33, 25, 17,  9,
              1, 58, 50, 42, 34, 26, 18,
             10,  2, 59, 51, 43, 35, 27,
             19, 11,  3, 60, 52, 44, 36,
             63, 55, 47, 39, 31, 23, 15,
              7, 62, 54, 46, 38, 30, 22,
             14,  6, 61, 53, 45, 37, 29,
             21, 13,  5, 28, 20, 12,  4);
# Ported from SAMBA/source/libsmb/smbdes.c:perm2[48]
var $perm2 = array(14, 17, 11, 24,  1,  5,
              3, 28, 15,  6, 21, 10,
             23, 19, 12,  4, 26,  8,
             16,  7, 27, 20, 13,  2,
             41, 52, 31, 37, 47, 55,
             30, 40, 51, 45, 33, 48,
             44, 49, 39, 56, 34, 53,
             46, 42, 50, 36, 29, 32);
# Ported from SAMBA/source/libsmb/smbdes.c:perm3[64]
var $perm3 = array(58, 50, 42, 34, 26, 18, 10,  2,
             60, 52, 44, 36, 28, 20, 12,  4,
             62, 54, 46, 38, 30, 22, 14,  6,
             64, 56, 48, 40, 32, 24, 16,  8,
             57, 49, 41, 33, 25, 17,  9,  1,
             59, 51, 43, 35, 27, 19, 11,  3,
             61, 53, 45, 37, 29, 21, 13,  5,
             63, 55, 47, 39, 31, 23, 15,  7);
# Ported from SAMBA/source/libsmb/smbdes.c:perm4[48]
var $perm4 = array(32,  1,  2,  3,  4,  5,
                 4,  5,  6,  7,  8,  9,
                 8,  9, 10, 11, 12, 13,
                12, 13, 14, 15, 16, 17,
                16, 17, 18, 19, 20, 21,
                20, 21, 22, 23, 24, 25,
                24, 25, 26, 27, 28, 29,
                28, 29, 30, 31, 32,  1);
# Ported from SAMBA/source/libsmb/smbdes.c:perm5[32]
var $perm5 = array(16,  7, 20, 21,
                   29, 12, 28, 17,
                    1, 15, 23, 26,
                    5, 18, 31, 10,
                    2,  8, 24, 14,
                   32, 27,  3,  9,
                   19, 13, 30,  6,
                   22, 11,  4, 25);
# Ported from SAMBA/source/libsmb/smbdes.c:perm6[64]
var $perm6 = array(40,  8, 48, 16, 56, 24, 64, 32,
             39,  7, 47, 15, 55, 23, 63, 31,
             38,  6, 46, 14, 54, 22, 62, 30,
             37,  5, 45, 13, 53, 21, 61, 29,
             36,  4, 44, 12, 52, 20, 60, 28,
             35,  3, 43, 11, 51, 19, 59, 27,
             34,  2, 42, 10, 50, 18, 58, 26,
             33,  1, 41,  9, 49, 17, 57, 25);
# Ported from SAMBA/source/libsmb/smbdes.c:sc[16]
var $sc = array(1, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1);
# Ported from SAMBA/source/libsmb/smbdes.c:sbox[8][4][16]
# Side note, I used cut and paste for all these numbers, I did NOT
# type them all in =)
var $sbox = array(array(array(14,  4, 13,  1,  2, 15, 11,  8,  3, 10,  6, 12,  5,  9,  0,  7),
             array( 0, 15,  7,  4, 14,  2, 13,  1, 10,  6, 12, 11,  9,  5,  3,  8),
             array( 4,  1, 14,  8, 13,  6,  2, 11, 15, 12,  9,  7,  3, 10,  5,  0),
             array(15, 12,  8,  2,  4,  9,  1,  7,  5, 11,  3, 14, 10,  0,  6, 13)),
            array(array(15,  1,  8, 14,  6, 11,  3,  4,  9,  7,  2, 13, 12,  0,  5, 10),
             array( 3, 13,  4,  7, 15,  2,  8, 14, 12,  0,  1, 10,  6,  9, 11,  5),
             array( 0, 14,  7, 11, 10,  4, 13,  1,  5,  8, 12,  6,  9,  3,  2, 15),
             array(13,  8, 10,  1,  3, 15,  4,  2, 11,  6,  7, 12,  0,  5, 14,  9)),
            array(array(10,  0,  9, 14,  6,  3, 15,  5,  1, 13, 12,  7, 11,  4,  2,  8),
             array(13,  7,  0,  9,  3,  4,  6, 10,  2,  8,  5, 14, 12, 11, 15,  1),
             array(13,  6,  4,  9,  8, 15,  3,  0, 11,  1,  2, 12,  5, 10, 14,  7),
             array( 1, 10, 13,  0,  6,  9,  8,  7,  4, 15, 14,  3, 11,  5,  2, 12)),
            array(array( 7, 13, 14,  3,  0,  6,  9, 10,  1,  2,  8,  5, 11, 12,  4, 15),
             array(13,  8, 11,  5,  6, 15,  0,  3,  4,  7,  2, 12,  1, 10, 14,  9),
             array(10,  6,  9,  0, 12, 11,  7, 13, 15,  1,  3, 14,  5,  2,  8,  4),
             array( 3, 15,  0,  6, 10,  1, 13,  8,  9,  4,  5, 11, 12,  7,  2, 14)),
            array(array( 2, 12,  4,  1,  7, 10, 11,  6,  8,  5,  3, 15, 13,  0, 14,  9),
             array(14, 11,  2, 12,  4,  7, 13,  1,  5,  0, 15, 10,  3,  9,  8,  6),
             array( 4,  2,  1, 11, 10, 13,  7,  8, 15,  9, 12,  5,  6,  3,  0, 14),
             array(11,  8, 12,  7,  1, 14,  2, 13,  6, 15,  0,  9, 10,  4,  5,  3)),
            array(array(12,  1, 10, 15,  9,  2,  6,  8,  0, 13,  3,  4, 14,  7,  5, 11),
             array(10, 15,  4,  2,  7, 12,  9,  5,  6,  1, 13, 14,  0, 11,  3,  8),
             array( 9, 14, 15,  5,  2,  8, 12,  3,  7,  0,  4, 10,  1, 13, 11,  6),
             array( 4,  3,  2, 12,  9,  5, 15, 10, 11, 14,  1,  7,  6,  0,  8, 13)),
            array(array( 4, 11,  2, 14, 15,  0,  8, 13,  3, 12,  9,  7,  5, 10,  6,  1),
             array(13,  0, 11,  7,  4,  9,  1, 10, 14,  3,  5, 12,  2, 15,  8,  6),
             array( 1,  4, 11, 13, 12,  3,  7, 14, 10, 15,  6,  8,  0,  5,  9,  2),
             array( 6, 11, 13,  8,  1,  4, 10,  7,  9,  5,  0, 15, 14,  2,  3, 12)),
            array(array(13,  2,  8,  4,  6, 15, 11,  1, 10,  9,  3, 14,  5,  0, 12,  7),
             array( 1, 15, 13,  8, 10,  3,  7,  4, 12,  5,  6, 11,  0, 14,  9,  2),
             array( 7, 11,  4,  1,  9, 12, 14,  2,  0,  6, 10, 13, 15,  3,  5,  8),
             array( 2,  1, 14,  7,  4, 10,  8, 13, 15, 12,  9,  0,  3,  5,  6, 11)));

	/**
	* Fixes too large numbers
	*/
	function x($i) {
		if ($i < 0) return 4294967296 - $i;
		else return $i;
	}

	/**
	* @param integer count
	* @param array $data
	* @return array
	*/
	function lshift($count, $data) {
		$ret = array();
		for ($i = 0; $i < sizeof($data); $i++) {
			$ret[$i] = $data[($i + $count)%sizeof($data)];
		}
		return $ret;
	}

	/**
	* @param array in input data
	* @param array p permutation
	* @return array
	*/
	function permute($in, $p, $n) {
		$ret = array();
		for ($i = 0; $i < $n; $i++) {
			$ret[$i] = $in[$p[$i] - 1]?1:0;
		}
		return $ret;
	}

	/**
	* @param array $in1
	* @param array $in2
	* @return array
	*/
	function mxor($in1, $in2) {
		$ret = array();
		for ($i = 0; $i < sizeof($in1); $i++) {
			$ret[$i] = $in1[$i] ^ $in2[$i];
		}
		return $ret;
	}

	/**
	* @param array $in
	* @param array $key
	* @param boolean $forw
	* @return array
	*/
	function doHash($in, $key, $forw) {
		$ki = array();
	
		$pk1 = $this->permute($key, $this->perm1, 56);
		
		$c = array();
		$d = array();
		for ($i = 0; $i < 28; $i++) {
			$c[$i] = $pk1[$i];
			$d[$i] = $pk1[28 + $i];
		}
		
		for ($i = 0; $i < 16; $i++) {
			$c = $this->lshift($this->sc[$i], $c);
			$d = $this->lshift($this->sc[$i], $d);
			
			$cd = $c;
			for ($k = 0; $k < sizeof($d); $k++) $cd[] = $d[$k];
			$ki[$i] = $this->permute($cd, $this->perm2, 48);
		}
		
		$pd1 = $this->permute($in, $this->perm3, 64);
		
		$l = array();
		$r = array();
		for ($i = 0; $i < 32; $i++) {
			$l[$i] = $pd1[$i];
			$r[$i] = $pd1[32 + $i];
		}
		
		for ($i = 0; $i < 16; $i++) {
			$er = $this->permute($r, $this->perm4, 48);
			if ($forw) $erk = $this->mxor($er, $ki[$i]);
			else $erk = $this->mxor($er, $ki[15 - $i]);
			
			for ($j = 0; $j < 8; $j++) {
				for ($k = 0; $k < 6; $k++) {
					$b[$j][$k] = $erk[($j * 6) + $k];
				}
			}
			for ($j = 0; $j < 8; $j++) {
				$m = array();
				$n = array();
				$m = ($b[$j][0] << 1) | $b[$j][5];
				$n = ($b[$j][1] << 3) | ($b[$j][2] << 2) | ($b[$j][3] << 1) | $b[$j][4];
				
				for ($k = 0; $k < 4; $k++) {
					$b[$j][$k]=($this->sbox[$j][$m][$n] & (1 << (3-$k)))?1:0;
				}
			}
			
			for ($j = 0; $j < 8; $j++) {
				for ($k = 0; $k < 4; $k++) {
					$cb[($j * 4) + $k] = $b[$j][$k];
				}
			}
			$pcb = $this->permute($cb, $this->perm5, 32);
			$r2 = $this->mxor($l, $pcb);
			for ($k = 0; $k < 32; $k++) $l[$k] = $r[$k];
			for ($k = 0; $k < 32; $k++) $r[$k] = $r2[$k];
		}
		$rl = $r;
		for ($i = 0; $i < sizeof($l); $i++) $rl[] = $l[$i];
		return $this->permute($rl, $this->perm6, 64);
	}

	function str_to_key($str) {
		$key[0] = $this->unsigned_shift_r($str[0], 1);
		$key[1] = (($str[0]&0x01)<<6) | $this->unsigned_shift_r($str[1], 2);
		$key[2] = (($str[1]&0x03)<<5) | $this->unsigned_shift_r($str[2], 3);
		$key[3] = (($str[2]&0x07)<<4) | $this->unsigned_shift_r($str[3], 4);
		$key[4] = (($str[3]&0x0F)<<3) | $this->unsigned_shift_r($str[4], 5);
		$key[5] = (($str[4]&0x1F)<<2) | $this->unsigned_shift_r($str[5], 6);
		$key[6] = (($str[5]&0x3F)<<1) | $this->unsigned_shift_r($str[6], 7);
		$key[7] = $str[6]&0x7F;
		for ($i = 0; $i < 8; $i++) {
			$key[$i] = ($key[$i] << 1);
		}
		return $key;
	}

	function smb_hash($in, $key, $forw){
		$key2 = $this->str_to_key($key);
	
		for ($i = 0; $i < 64; $i++) {
			$inb[$i] = ($in[$i/8] & (1<<(7-($i%8)))) ? 1:0;
			$keyb[$i] = ($key2[$i/8] & (1<<(7-($i%8)))) ? 1:0;
			$outb[$i] = 0;
		}
		$outb = $this->dohash($inb, $keyb, $forw);
		for ($i = 0; $i < 8; $i++) {
			$out[$i] = 0;
		}
		for ($i = 0; $i < 65; $i++) {
			if ( $outb[$i] )  {
				$out[$i/8] |= (1<<(7-($i%8)));
			}
		}
		return $out;
	}

	function E_P16($in) {
		$p14 = array_values(unpack("C*",$in));
		$sp8 = array(0x4b, 0x47, 0x53, 0x21, 0x40, 0x23, 0x24, 0x25);
		$p14_1 = array();
		$p14_2 = array();
		for ($i = 0; $i < 7; $i++) {
			$p14_1[$i] = $p14[$i];
			$p14_2[$i] = $p14[$i + 7];
		}
		$p16_1 = $this->smb_hash($sp8, $p14_1, true);
		$p16_2 = $this->smb_hash($sp8, $p14_2, true);
		$p16 = $p16_1;
		for ($i = 0; $i < sizeof($p16_2); $i++) {
			$p16[] = $p16_2[$i];
		}
		return $p16;
	}

	/**
	* Calculates the LM hash of a given password.
	*
	* @param string $password password
	* @return string hash value
	*/
	function lmhash($password = "") {
		$password = strtoupper($password);
		$password = substr($password,0,14);
		$password = str_pad($password, 14, chr(0));
		$p16 = $this->E_P16($password);
		for ($i = 0; $i < sizeof($p16); $i++) {
			$p16[$i] = sprintf("%02X", $p16[$i]);
		}
		return join("", $p16);
	}

	/**
	* Calculates the NT hash of a given password.
	*
	* @param string $password password
	* @return string hash value
	*/
	function nthash($password = "") {
		$password = substr($password,0,128);
		$password2 = "";
		for ($i = 0; $i < strlen($password); $i++) $password2 .= $password[$i] . chr(0);
		$password = $password2;
		$hex = $this->mdfour($password);
		for ($i = 0; $i < sizeof($hex); $i++) {
			$hex[$i] = sprintf("%02X", $hex[$i]);
		}
		return join("", $hex);
	}

	# Support functions
	# Ported from SAMBA/source/lib/md4.c:F,G and H respectfully
	function F($X, $Y, $Z) {
		$ret = (($X&$Y) | ((~((int)$X))&$Z));
		if ($this->x($ret) > 4294967296) {
			$ret = (2*4294967296) - $this->x($ret);
		}
		return $ret;
	}

	function G($X, $Y, $Z) {
		return ($X&$Y) | ($X&$Z) | ($Y&$Z);
	}
	
	function H($X, $Y, $Z) {
		return $X^$Y^$Z;
	}

	# Ported from SAMBA/source/lib/md4.c:mdfour
	function mdfour($in) {
		$in = unpack("C*",$in);
		$in = array_values($in);
		$b = sizeof($in) * 8;
		$A = array(0x67452301, 0xefcdab89, 0x98badcfe, 0x10325476);
		while (sizeof($in) > 64 ) {
			$M = $this->copy64($in);
			$this->mdfour64($A[0], $A[1], $A[2], $A[3], $M);
			$new_in = array();
			for ($i = 64; $i < sizeof($in); $i++) $new_in[] = $in[$i];
			$in = $new_in;
		}
		$buf = $in;
		$buf[] = 0x80;
		for ($i = sizeof($buf) - 1; $i < 127; $i++) $buf[] = 0;
		if ( sizeof($in) <= 55 ) {
			$temp = $this->copy4($b);
			$buf[56] = $temp[0];
			$buf[57] = $temp[1];
			$buf[58] = $temp[2];
			$buf[59] = $temp[3];
			$M = $this->copy64($buf);
			$this->mdfour64($A[0], $A[1], $A[2], $A[3], $M);
		}
		else {
			$temp = $this->copy4($b);
			$buf[120] = $temp[0];
			$buf[121] = $temp[1];
			$buf[122] = $temp[2];
			$buf[123] = $temp[3];
			$M = $this->copy64($buf);
			$this->mdfour64($A[0], $A[1], $A[2], $A[3], $M);
			$temp = array();
			for ($i = 64; $i < sizeof($buf); $i++) $temp[] = $buf[$i];
			$M = $this->copy64($temp);
			$this->mdfour64($A[0], $A[1], $A[2], $A[3], $M);
		}
		$out = array();
		$temp = $this->copy4($A[0]);
		for ($i = 0; $i < 4; $i++) $out[] = $temp[$i];
		$temp = $this->copy4($A[1]);
		for ($i = 0; $i < 4; $i++) $out[] = $temp[$i];
		$temp = $this->copy4($A[2]);
		for ($i = 0; $i < 4; $i++) $out[] = $temp[$i];
		$temp = $this->copy4($A[3]);
		for ($i = 0; $i < 4; $i++) $out[] = $temp[$i];
		return $out;
	}

	# Ported from SAMBA/source/lib/md4.c:copy4
	function copy4($x) {
		$out = array();
		$out[0] = $x&0xFF;
		$out[1] = $this->unsigned_shift_r($x, 8)&0xFF;
		$out[2] = $this->unsigned_shift_r($x, 16)&0xFF;
		$out[3] = $this->unsigned_shift_r($x, 24)&0xFF;
		return $out;
	}

	# Ported from SAMBA/source/lib/md4.c:copy64
	function copy64($in) {
		for ($i = 0; $i < 16; $i++) {
			$M[$i] = ($in[$i*4+3]<<24) | ($in[$i*4+2]<<16) | ($in[$i*4+1]<<8) | ($in[$i*4+0]<<0);
		}
		return $M;
	}

	# Ported from SAMBA/source/lib/md4.c:mdfour64
	function mdfour64(&$A, &$B, &$C, &$D, $M) {
		$X = array();
		for ($i = 0; $i < 16; $i++) $X[] = $M[$i];
		$AA=$A;
		$BB=$B;
		$CC=$C;
		$DD=$D;
		$this->ROUND1($A,$B,$C,$D,  0,  3, $X);
		$this->ROUND1($D,$A,$B,$C,  1,  7, $X);
		$this->ROUND1($C,$D,$A,$B,  2, 11, $X);
		$this->ROUND1($B,$C,$D,$A,  3, 19, $X);
		$this->ROUND1($A,$B,$C,$D,  4,  3, $X);  $this->ROUND1($D,$A,$B,$C,  5,  7, $X);
		$this->ROUND1($C,$D,$A,$B,  6, 11, $X);  $this->ROUND1($B,$C,$D,$A,  7, 19, $X);
		$this->ROUND1($A,$B,$C,$D,  8,  3, $X);  $this->ROUND1($D,$A,$B,$C,  9,  7, $X);
		$this->ROUND1($C,$D,$A,$B, 10, 11, $X);  $this->ROUND1($B,$C,$D,$A, 11, 19, $X);
		$this->ROUND1($A,$B,$C,$D, 12,  3, $X);  $this->ROUND1($D,$A,$B,$C, 13,  7, $X);
		$this->ROUND1($C,$D,$A,$B, 14, 11, $X);  $this->ROUND1($B,$C,$D,$A, 15, 19, $X);
		$this->ROUND2($A,$B,$C,$D,  0,  3, $X);  $this->ROUND2($D,$A,$B,$C,  4,  5, $X);
		$this->ROUND2($C,$D,$A,$B,  8,  9, $X);  $this->ROUND2($B,$C,$D,$A, 12, 13, $X);
		$this->ROUND2($A,$B,$C,$D,  1,  3, $X);  $this->ROUND2($D,$A,$B,$C,  5,  5, $X);
		$this->ROUND2($C,$D,$A,$B,  9,  9, $X);  $this->ROUND2($B,$C,$D,$A, 13, 13, $X);
		$this->ROUND2($A,$B,$C,$D,  2,  3, $X);  $this->ROUND2($D,$A,$B,$C,  6,  5, $X);
		$this->ROUND2($C,$D,$A,$B, 10,  9, $X);  $this->ROUND2($B,$C,$D,$A, 14, 13, $X);
		$this->ROUND2($A,$B,$C,$D,  3,  3, $X);  $this->ROUND2($D,$A,$B,$C,  7,  5, $X);
		$this->ROUND2($C,$D,$A,$B, 11,  9, $X);  $this->ROUND2($B,$C,$D,$A, 15, 13, $X);
		$this->ROUND3($A,$B,$C,$D,  0,  3, $X);  $this->ROUND3($D,$A,$B,$C,  8,  9, $X);
		$this->ROUND3($C,$D,$A,$B,  4, 11, $X);  $this->ROUND3($B,$C,$D,$A, 12, 15, $X);
		$this->ROUND3($A,$B,$C,$D,  2,  3, $X);  $this->ROUND3($D,$A,$B,$C, 10,  9, $X);
		$this->ROUND3($C,$D,$A,$B,  6, 11, $X);  $this->ROUND3($B,$C,$D,$A, 14, 15, $X);
		$this->ROUND3($A,$B,$C,$D,  1,  3, $X);  $this->ROUND3($D,$A,$B,$C,  9,  9, $X);
		$this->ROUND3($C,$D,$A,$B,  5, 11, $X);  $this->ROUND3($B,$C,$D,$A, 13, 15, $X);
		$this->ROUND3($A,$B,$C,$D,  3,  3, $X);  $this->ROUND3($D,$A,$B,$C, 11,  9, $X);
		$this->ROUND3($C,$D,$A,$B,  7, 11, $X);  $this->ROUND3($B,$C,$D,$A, 15, 15, $X);
		
		$A = $this->add32(array($A, $AA)); $B = $this->add32(array($B, $BB));
		$C = $this->add32(array($C, $CC)); $D = $this->add32(array($D, $DD));
	}

	# Needed? because perl seems to choke on overflowing when doing bitwise
	# operations on numbers larger than 32 bits. Well, it did on my machine =)
	function add32($v) {
		for ($i = 0; $i < sizeof($v); $i++) {
			$v[$i] = array($this->unsigned_shift_r(($v[$i]&0xffff0000), 16), ($v[$i]&0xffff));
		}
		for ($i = 0; $i < sizeof($v); $i++) {
			$sum[0] += $v[$i][0];
			$sum[1] += $v[$i][1];
		}
		$sum[0] += ($sum[1]&0xffff0000)>>16;
		$sum[1] &= 0xffff;
		$sum[0] &= 0xffff;
		$ret = ($sum[0]<<16) | $sum[1];
		if ($this->x($ret) > 4294967296) {
			$ret = (2*4294967296) - $this->x($ret);
		}
		return $ret;
	}

	# Ported from SAMBA/source/lib/md4.c:ROUND1
	function ROUND1(&$a,$b,$c,$d,$k,$s,$X) {
		$a = $this->md4lshift($this->add32(array($a, $this->F($b,$c,$d), $X[$k])), $s);
		return $a;
	}

	# Ported from SAMBA/source/lib/md4.c:ROUND2
	function ROUND2(&$a,$b,$c,$d,$k,$s,$X) {
		$a = $this->md4lshift($this->add32(array($a, $this->G($b,$c,$d), $X[$k] + 0x5A827999)), $s);
		return $a;
	}

	# Ported from SAMBA/source/lib/md4.c:ROUND3
	function ROUND3(&$a,$b,$c,$d,$k,$s,$X) {
		$a = $this->md4lshift($this->add32(array($a + $this->H($b,$c,$d) + $X[$k] + 0x6ED9EBA1)), $s);
		return $a;
	}

	# Ported from SAMBA/source/lib/md4.c:lshift
	# Renamed to prevent clash with SAMBA/source/libsmb/smbdes.c:lshift
	function md4lshift($x, $s) {
		$x &= 0xFFFFFFFF;
		if ($this->x($x) > 4294967296) {
			$x = (2*4294967296) - $this->x($x);
		}
		$ret = ((($x<<$s)&0xFFFFFFFF) | $this->unsigned_shift_r($x, (32-$s)));
		if ($this->x($ret) > 4294967296) {
			$ret = (2*4294967296) - $this->x($ret);
		}
		return $ret;
	}

	/**
	* Unsigned shift operation for 32bit values.
	*
	* PHP 4 only supports signed shifts by default.
	*/
	function unsigned_shift_r($a, $b) { 
		$z = 0x80000000; 
		if ($z & $a) { 
			$a = ($a >> 1); 
			$a &= (~$z); 
			$a |= 0x40000000; 
			$a = ($a >> ($b - 1)); 
		} 
		else { 
			$a = ($a >> $b); 
		} 
		return $a; 
	} 

}

?>
