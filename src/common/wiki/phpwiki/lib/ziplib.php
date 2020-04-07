<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
/**
 * GZIP stuff.
 *
 * Note that we use gzopen()/gzwrite() instead of gzcompress() even if
 * gzcompress() is available.  Gzcompress() puts out data with
 * different headers --- in particular it includes an "adler-32"
 * checksum rather than a "CRC32" checksum. Since we need the CRC-32
 * checksum, and since not all PHP's have gzcompress(), we'll just
 * stick with gzopen().
 */
function gzip_cleanup()
{
    global $gzip_tmpfile;

    if ($gzip_tmpfile) {
        @unlink($gzip_tmpfile);
    }
}

function gzip_tempnam()
{
    global $gzip_tmpfile;

    if (!$gzip_tmpfile) {
        //FIXME: does this work on non-unix machines?
        if (is_writable("/tmp")) {
            $gzip_tmpfile = tempnam("/tmp", "wkzip");
        } else {
            $gzip_tmpfile = tempnam(TEMP_DIR, "wkzip");
        }
        register_shutdown_function("gzip_cleanup");
    }
    return $gzip_tmpfile;
}

function gzip_compress($data)
{
    $filename = gzip_tempnam();
    if (!($fp = gzopen($filename, "wb"))) {
        trigger_error(sprintf("%s failed", 'gzopen'), E_USER_ERROR);
    }
    gzwrite($fp, $data, strlen($data));
    if (!gzclose($fp)) {
        trigger_error(sprintf("%s failed", 'gzclose'), E_USER_ERROR);
    }
    $z = null;
    if (!($fp = fopen($filename, "rb"))) {
        trigger_error(sprintf("%s failed", 'fopen'), E_USER_ERROR);
    }
    while (!feof($fp)) {
        $z .= fread($fp, 1024);
    }
    if (!fclose($fp)) {
        trigger_error(sprintf("%s failed", 'fclose'), E_USER_ERROR);
    }
    unlink($filename);
    return $z;
}

function gzip_uncompress($data)
{
    $filename = gzip_tempnam();
    if (!($fp = fopen($filename, "wb"))) {
        trigger_error(sprintf("%s failed", 'fopen'), E_USER_ERROR);
    }
    fwrite($fp, $data, strlen($data));
    if (!fclose($fp)) {
        trigger_error(sprintf("%s failed", 'fclose'), E_USER_ERROR);
    }

    if (!($fp = gzopen($filename, "rb"))) {
        trigger_error(sprintf("%s failed", 'gzopen'), E_USER_ERROR);
    }
    $unz = '';
    while ($buf = gzread($fp, 4096)) {
        $unz .= $buf;
    }
    if (!gzclose($fp)) {
        trigger_error(sprintf("%s failed", 'gzclose'), E_USER_ERROR);
    }

    unlink($filename);
    return $unz;
}

/**
 * CRC32 computation.  Hacked from Info-zip's zip-2.3 source code.
 */

function zip_crc32($str, $crc = 0)
{
    static $zip_crc_table;

    if (empty($zip_crc_table)) {
        /* NOTE: The range of PHP ints seems to be -0x80000000 to 0x7fffffff.
         * So, had to munge these constants.
         */
        $zip_crc_table
            = array (0x00000000,  0x77073096, -0x11f19ed4, -0x66f6ae46,  0x076dc419,
                     0x706af48f, -0x169c5acb, -0x619b6a5d,  0x0edb8832,  0x79dcb8a4,
                    -0x1f2a16e2, -0x682d2678,  0x09b64c2b,  0x7eb17cbd, -0x1847d2f9,
                    -0x6f40e26f,  0x1db71064,  0x6ab020f2, -0x0c468eb8, -0x7b41be22,
                     0x1adad47d,  0x6ddde4eb, -0x0b2b4aaf, -0x7c2c7a39,  0x136c9856,
                     0x646ba8c0, -0x029d0686, -0x759a3614,  0x14015c4f,  0x63066cd9,
                    -0x05f0c29d, -0x72f7f20b,  0x3b6e20c8,  0x4c69105e, -0x2a9fbe1c,
                    -0x5d988e8e,  0x3c03e4d1,  0x4b04d447, -0x2df27a03, -0x5af54a95,
                     0x35b5a8fa,  0x42b2986c, -0x2444362a, -0x534306c0,  0x32d86ce3,
                     0x45df5c75, -0x2329f231, -0x542ec2a7,  0x26d930ac,  0x51de003a,
                    -0x3728ae80, -0x402f9eea,  0x21b4f4b5,  0x56b3c423, -0x30456a67,
                    -0x47425af1,  0x2802b89e,  0x5f058808, -0x39f3264e, -0x4ef416dc,
                     0x2f6f7c87,  0x58684c11, -0x3e9ee255, -0x4999d2c3,  0x76dc4190,
                     0x01db7106, -0x672ddf44, -0x102aefd6,  0x71b18589,  0x06b6b51f,
                    -0x60401b5b, -0x17472bcd,  0x7807c9a2,  0x0f00f934, -0x69f65772,
                    -0x1ef167e8,  0x7f6a0dbb,  0x086d3d2d, -0x6e9b9369, -0x199ca3ff,
                     0x6b6b51f4,  0x1c6c6162, -0x7a9acf28, -0x0d9dffb2,  0x6c0695ed,
                     0x1b01a57b, -0x7df70b3f, -0x0af03ba9,  0x65b0d9c6,  0x12b7e950,
                    -0x74414716, -0x03467784,  0x62dd1ddf,  0x15da2d49, -0x732c830d,
                    -0x042bb39b,  0x4db26158,  0x3ab551ce, -0x5c43ff8c, -0x2b44cf1e,
                     0x4adfa541,  0x3dd895d7, -0x5b2e3b93, -0x2c290b05,  0x4369e96a,
                     0x346ed9fc, -0x529877ba, -0x259f4730,  0x44042d73,  0x33031de5,
                    -0x55f5b3a1, -0x22f28337,  0x5005713c,  0x270241aa, -0x41f4eff0,
                    -0x36f3df7a,  0x5768b525,  0x206f85b3, -0x46992bf7, -0x319e1b61,
                     0x5edef90e,  0x29d9c998, -0x4f2f67de, -0x3828574c,  0x59b33d17,
                     0x2eb40d81, -0x4842a3c5, -0x3f459353, -0x12477ce0, -0x65404c4a,
                     0x03b6e20c,  0x74b1d29a, -0x152ab8c7, -0x622d8851,  0x04db2615,
                     0x73dc1683, -0x1c9cf4ee, -0x6b9bc47c,  0x0d6d6a3e,  0x7a6a5aa8,
                    -0x1bf130f5, -0x6cf60063,  0x0a00ae27,  0x7d079eb1, -0x0ff06cbc,
                    -0x78f75c2e,  0x1e01f268,  0x6906c2fe, -0x089da8a3, -0x7f9a9835,
                     0x196c3671,  0x6e6b06e7, -0x012be48a, -0x762cd420,  0x10da7a5a,
                     0x67dd4acc, -0x06462091, -0x71411007,  0x17b7be43,  0x60b08ed5,
                    -0x29295c18, -0x5e2e6c82,  0x38d8c2c4,  0x4fdff252, -0x2e44980f,
                    -0x5943a899,  0x3fb506dd,  0x48b2364b, -0x27f2d426, -0x50f5e4b4,
                     0x36034af6,  0x41047a60, -0x209f103d, -0x579820ab,  0x316e8eef,
                     0x4669be79, -0x349e4c74, -0x43997ce6,  0x256fd2a0,  0x5268e236,
                    -0x33f3886b, -0x44f4b8fd,  0x220216b9,  0x5505262f, -0x3a45c442,
                    -0x4d42f4d8,  0x2bb45a92,  0x5cb36a04, -0x3d280059, -0x4a2f30cf,
                     0x2cd99e8b,  0x5bdeae1d, -0x649b3d50, -0x139c0dda,  0x756aa39c,
                     0x026d930a, -0x63f6f957, -0x14f1c9c1,  0x72076785,  0x05005713,
                    -0x6a40b57e, -0x1d4785ec,  0x7bb12bae,  0x0cb61b38, -0x6d2d7165,
                    -0x1a2a41f3,  0x7cdcefb7,  0x0bdbdf21, -0x792c2d2c, -0x0e2b1dbe,
                     0x68ddb3f8,  0x1fda836e, -0x7e41e933, -0x0946d9a5,  0x6fb077e1,
                     0x18b74777, -0x77f7a51a, -0x00f09590,  0x66063bca,  0x11010b5c,
                    -0x709a6101, -0x079d5197,  0x616bffd3,  0x166ccf45, -0x5ff51d88,
                    -0x28f22d12,  0x4e048354,  0x3903b3c2, -0x5898d99f, -0x2f9fe909,
                     0x4969474d,  0x3e6e77db, -0x512e95b6, -0x2629a524,  0x40df0b66,
                     0x37d83bf0, -0x564351ad, -0x2144613b,  0x47b2cf7f,  0x30b5ffe9,
                    -0x42420de4, -0x35453d76,  0x53b39330,  0x24b4a3a6, -0x452fc9fb,
                    -0x3228f96d,  0x54de5729,  0x23d967bf, -0x4c9985d2, -0x3b9eb548,
                     0x5d681b02,  0x2a6f2b94, -0x4bf441c9, -0x3cf3715f,  0x5a05df1b,
                     0x2d02ef8d);
    }

    $crc = ~$crc;
    for ($i = 0; $i < strlen($str); $i++) {
        $crc = ( $zip_crc_table[($crc ^ ord($str[$i])) & 0xff]
                 ^ (($crc >> 8) & 0xffffff) );
    }
    return ~$crc;
}

define('GZIP_MAGIC', "\037\213");
define('GZIP_DEFLATE', 010);

function zip_deflate($content)
{
    // Compress content, and suck information from gzip header.
    if (function_exists('gzencode')) {
        $z = gzencode($content);
    } else {
        $z = gzip_compress($content);
    }

    // Suck OS type byte from gzip header. FIXME: this smells bad.

    extract(unpack("a2magic/Ccomp_type/Cflags/@9/Cos_type", $z));

    if ($magic != GZIP_MAGIC) {
        trigger_error(sprintf("Bad %s", "gzip magic"), E_USER_ERROR);
    }
    if ($comp_type != GZIP_DEFLATE) {
        trigger_error(sprintf("Bad %s", "gzip comp type"), E_USER_ERROR);
    }
    if (($flags & 0x3e) != 0) {
        trigger_error(
            sprintf("Bad %s", sprintf("flags (0x%02x)", $flags)),
            E_USER_ERROR
        );
    }

    $gz_header_len = 10;
    $gz_data_len = strlen($z) - $gz_header_len - 8;
    if ($gz_data_len < 0) {
        trigger_error("not enough gzip output?", E_USER_ERROR);
    }

    extract(unpack("Vcrc32", substr($z, $gz_header_len + $gz_data_len)));

    return array(substr($z, $gz_header_len, $gz_data_len), // gzipped data
                 $crc32,        // crc
                 $os_type        // OS type
                 );
}

function zip_inflate($data, $crc32, $uncomp_size)
{
    if (function_exists('gzinflate')) {
        $data = gzinflate($data);
        if (strlen($data) != $uncomp_size) {
            trigger_error("not enough output from gzinflate", E_USER_ERROR);
        }
        if (zip_crc32($data) != $crc32) {
            trigger_error("CRC32 mismatch", E_USER_ERROR);
        }
        return $data;
    }

    if (!function_exists('gzopen')) {
        global $request;
        $request->finish(_("Can't inflate data: zlib support not enabled in this PHP"));
    }

    // Reconstruct gzip header and ungzip the data.
    $mtime = time();        //(Bogus mtime)

    return gzip_uncompress(pack("a2CxV@10", GZIP_MAGIC, GZIP_DEFLATE, $mtime)
                            . $data
                            . pack("VV", $crc32, $uncomp_size));
}

function unixtime2dostime($unix_time)
{
    if ($unix_time % 1) {
        $unix_time++;        // Round up to even seconds.
    }

    list ($year,$month,$mday,$hour,$min,$sec)
        = explode(" ", date("Y n j G i s", $unix_time));

    if ($year < 1980) {
        list ($year,$month,$mday,$hour,$min,$sec) = array(1980, 1, 1, 0, 0, 0);
    }

    $dosdate = (($year - 1980) << 9) | ($month << 5) | $mday;
    $dostime = ($hour << 11) | ($min << 5) | ($sec >> 1);

    return array($dosdate, $dostime);
}

function dostime2unixtime($dosdate, $dostime)
{
    $mday  = $dosdate & 0x1f;
    $month = ($dosdate >> 5) & 0x0f;
    $year  = 1980 + (($dosdate >> 9) & 0x7f);

    $sec  = ($dostime & 0x1f) * 2;
    $min  = ($dostime >> 5) & 0x3f;
    $hour = ($dostime >> 11) & 0x1f;

    return mktime($hour, $min, $sec, $month, $mday, $year);
}


/**
 * Class for zipfile creation.
 */
define('ZIP_DEFLATE', GZIP_DEFLATE);
define('ZIP_STORE', 0);
define('ZIP_CENTHEAD_MAGIC', "PK\001\002");
define('ZIP_LOCHEAD_MAGIC', "PK\003\004");
define('ZIP_ENDDIR_MAGIC', "PK\005\006");

class ZipWriter
{
    public function __construct($comment = "", $zipname = "archive.zip")
    {
        $this->comment = $comment;
        $this->nfiles = 0;
        $this->dir = "";        // "Central directory block"
        $this->offset = 0;        // Current file position.

        $zipname = addslashes($zipname);
        header("Content-Type: application/zip; name=\"$zipname\"");
        header("Content-Disposition: attachment; filename=\"$zipname\"");
    }

    public function addRegularFile($filename, $content, $attrib = false)
    {
        if (!$attrib) {
            $attrib = array();
        }

        $size = strlen($content);
        if (function_exists('gzopen')) {
            list ($data, $crc32, $os_type) = zip_deflate($content);
            if (strlen($data) < $size) {
                $content = $data;    // Use compressed data.
                $comp_type = ZIP_DEFLATE;
            } else {
                unset($crc32);    // force plain store.
            }
        } else {
            // Punt:
            $os_type = 3;     // 0 = FAT --- hopefully this is good enough.
            /* (Another choice might be 3 = Unix) */
        }

        if (!isset($crc32)) {
            $comp_type = ZIP_STORE;
            $crc32 = zip_crc32($content);
        }

        if (!empty($attrib['write_protected'])) {
            $atx = (0100444 << 16) | 1; // S_IFREG + read permissions to
        } else { // everybody.
            $atx = (0100644 << 16); // Add owner write perms.
        }

        $ati = $attrib['is_ascii'] ? 1 : 0;

        if (empty($attrib['mtime'])) {
            $attrib['mtime'] = time();
        }
        list ($mod_date, $mod_time) = unixtime2dostime($attrib['mtime']);

        // Construct parts common to "Local file header" and "Central
        // directory file header."
        if (!isset($attrib['extra_field'])) {
            $attrib['extra_field'] = '';
        }
        if (!isset($attrib['file_comment'])) {
            $attrib['file_comment'] = '';
        }

        $head = pack(
            "vvvvvVVVvv",
            20,    // Version needed to extract (FIXME: is this right?)
            0,    // Gen purp bit flag
            $comp_type,
            $mod_time,
            $mod_date,
            $crc32,
            strlen($content),
            $size,
            strlen($filename),
            strlen($attrib['extra_field'])
        );

        // Construct the "Local file header"
        $lheader = ZIP_LOCHEAD_MAGIC . $head . $filename
          . $attrib['extra_field'];

        // Construct the "central directory file header"
        $this->dir .= pack(
            "a4CC",
            ZIP_CENTHEAD_MAGIC,
            23,    // Version made by (FIXME: is this right?)
            $os_type
        );
        $this->dir .= $head;
        $this->dir .= pack(
            "vvvVV",
            strlen($attrib['file_comment']),
            0,              // Disk number start
            $ati,             // Internal file attributes
            $atx,             // External file attributes
            $this->offset
        ); // Relative offset of local header
        $this->dir .= $filename . $attrib['extra_field']
          . $attrib['file_comment'];

        // Output the "Local file header" and file contents.
        echo $lheader;
        echo $content;

        $this->offset += strlen($lheader) + strlen($content);
        $this->nfiles++;
    }

    public function finish()
    {
        // Output the central directory
        echo $this->dir;

        // Construct the "End of central directory record"
        echo ZIP_ENDDIR_MAGIC;
        echo pack(
            "vvvvVVv",
            0,                  // Number of this disk.
            0,                  // Number of disk with start of c dir
            $this->nfiles,      // Number entries on this disk
            $this->nfiles,        // Number entries
            strlen($this->dir), // Size of central directory
            $this->offset,        // Offset of central directory
            strlen($this->comment)
        );
        echo $this->comment;
    }
}


/**
 * Class for reading zip files. Handles buffers also.
 *
 * BUGS:
 *
 * Many of the ExitWiki()'s should probably be warn()'s (eg. CRC mismatch).
 *
 * Only a subset of zip formats is recognized. (I think that
 * unsupported formats will be recognized as such rather than silently
 * munged.)
 *
 * We don't read the central directory. This means we don't see the
 * file attributes (text? read-only?), or file comments.
 *
 * Right now we ignore the file mod date and time, since we don't need it.
 */
class ZipReader
{
    public function __construct($zipfile)
    {
        if (!is_string($zipfile)) { // filepointer: File already open
            $this->fp = $zipfile;
            $zipfile = null;
        } elseif (
            ((ord($zipfile[0]) * 256 + ord($zipfile[1])) % 31 == 0) // buffer
            and (substr($zipfile, 0, 2) == "\037\213")
            or (substr($zipfile, 0, 2) == "x\332")
        ) {  // 120, 218
            $this->fp = null;
            $this->buf = $zipfile;
            $zipfile = null;
        }
        if ($zipfile) {
            $this->zipfile = $zipfile;
            if (!($this->fp = fopen($zipfile, "rb"))) {
                trigger_error(sprintf(
                    _("Can't open zip file '%s' for reading"),
                    $zipfile
                ), E_USER_ERROR);
            }
        }
    }

    public function _read($nbytes)
    {
        if ($this->fp) {
            $chunk = fread($this->fp, $nbytes);
            if (strlen($chunk) != $nbytes) {
                trigger_error(_("Unexpected EOF in zip file"), E_USER_ERROR);
            }
            return $chunk;
        } elseif ($this->buf) {
            if (strlen($this->buf) < $nbytes) {
                trigger_error(_("Unexpected EOF in zip file"), E_USER_ERROR);
            }
            $chunk = substr($this->buf, 0, $nbytes);
            $this->buf = substr($this->buf, $nbytes);
            return $chunk;
        }
    }

    public function done()
    {
        if ($this->fp) {
            fclose($this->fp);
        } else {
            $this->buf = '';
        }
        return false;
    }

    public function readFile()
    {
        $head = $this->_read(30); // FIXME: This is bad for gzip compressed buffers

        extract(unpack(
            "a4magic/vreq_version/vflags/vcomp_type"
                     . "/vmod_time/vmod_date"
                     . "/Vcrc32/Vcomp_size/Vuncomp_size"
                     . "/vfilename_len/vextrafld_len",
            $head
        ));

        if ($magic != ZIP_LOCHEAD_MAGIC) {
            // maybe gzip?
            //$x = substr($magic,0,3);
            if (substr($magic, 0, 3) == "\037\213\225") {
              //and (substr($magic,3,1) & 0x3e) == 0)
                if ($this->fp) {
                    fclose($this->fp);
                    $this->fp = fopen($this->zipfile, "rb");
                    $content = $this->_read(filesize($this->fp));
                } else {
                    $content = $this->buf;
                }
                // TODO...
                $data = zip_deflate($content);
                return array($filename, $data, $attrib);
            }
            if ($magic != ZIP_CENTHEAD_MAGIC) {
              // FIXME: better message?
                ExitWiki(sprintf("Unsupported ZIP header type: %s", $magic));
            }
            return $this->done();
        }
        if (($flags & 0x21) != 0) {
            ExitWiki("Encryption and/or zip patches not supported.");
        }
        if (($flags & 0x08) != 0) {
          // FIXME: better message?
            ExitWiki("Postponed CRC not yet supported.");
        }

        $filename = $this->_read($filename_len);
        //FIXME: we should probably check $req_version.
        $attrib['mtime'] = dostime2unixtime($mod_date, $mod_time);
        if ($extrafld_len != 0) {
            $attrib['extra_field'] = $this->_read($extrafld_len);
        }

        $data = $this->_read($comp_size);

        if ($comp_type == ZIP_DEFLATE) {
            $data = zip_inflate($data, $crc32, $uncomp_size);
        } elseif ($comp_type == ZIP_STORE) {
            $crc = zip_crc32($data);
            if ($crc32 != $crc) {
                ExitWiki(sprintf("CRC mismatch %x != %x", $crc, $crc32));
            }
        } else {
            ExitWiki(sprintf(
                "Compression method %s unsupported",
                $comp_method
            ));
        }

        if (strlen($data) != $uncomp_size) {
            ExitWiki(sprintf(
                "Uncompressed size mismatch %d != %d",
                strlen($data),
                $uncomp_size
            ));
        }

        return array($filename, $data, $attrib);
    }
}

/**
 * Routines for Mime mailification of pages.
 */
//FIXME: these should go elsewhere (libmime?).

/**
 * Routines for quoted-printable en/decoding.
 */
function QuotedPrintableEncode($string)
{
    // Quote special characters in line.
    $quoted = "";
    while ($string) {
        // The complicated regexp is to force quoting of trailing spaces.
        preg_match('/^([ !-<>-~]*)(?:([!-<>-~]$)|(.))/s', $string, $match);
        $quoted .= $match[1] . $match[2];
        if (!empty($match[3])) {
            $quoted .= sprintf("=%02X", ord($match[3]));
        }
        $string = substr($string, strlen($match[0]));
    }
    // Split line.
    // This splits the line (preferably after white-space) into lines
    // which are no longer than 76 chars (after adding trailing '=' for
    // soft line break, but before adding \r\n.)
    return preg_replace(
        '/(?=.{77})(.{10,74}[ \t]|.{71,73}[^=][^=])/s',
        "\\1=\r\n",
        $quoted
    );
}

function QuotedPrintableDecode($string)
{
    // Eliminate soft line-breaks.
    $string = preg_replace('/=[ \t\r]*\n/', '', $string);
    return quoted_printable_decode($string);
}

define('MIME_TOKEN_REGEXP', "[-!#-'*+.0-9A-Z^-~]+");

function MimeContentTypeHeader($type, $subtype, $params)
{
    $header = "Content-Type: $type/$subtype";
    foreach ($params as $key => $val) {
        //FIXME:  what about non-ascii printables in $val?
        if (!preg_match('/^' . MIME_TOKEN_REGEXP . '$/', $val)) {
            $val = '"' . addslashes($val) . '"';
        }
        $header .= ";\r\n  $key=$val";
    }
    return "$header\r\n";
}

function MimeMultipart($parts)
{
    global $mime_multipart_count;

    // The string "=_" can not occur in quoted-printable encoded data.
    $boundary = "=_multipart_boundary_" . ++$mime_multipart_count;

    $head = MimeContentTypeHeader(
        'multipart',
        'mixed',
        array('boundary' => $boundary)
    );

    $sep = "\r\n--$boundary\r\n";

    return $head . $sep . implode($sep, $parts) . "\r\n--${boundary}--\r\n";
}

/**
 * For reference see:
 * http://www.nacs.uci.edu/indiv/ehood/MIME/2045/rfc2045.html
 * http://www.faqs.org/rfcs/rfc2045.html
 * (RFC 1521 has been superceeded by RFC 2045 & others).
 *
 * Also see http://www.faqs.org/rfcs/rfc2822.html
 *
 *
 * Notes on content-transfer-encoding.
 *
 * "7bit" means short lines of US-ASCII.
 * "8bit" means short lines of octets with (possibly) the high-order bit set.
 * "binary" means lines are not necessarily short enough for SMTP
 * transport, and non-ASCII characters may be present.
 *
 * Only "7bit", "quoted-printable", and "base64" are universally safe
 * for transport via e-mail.  (Though many MTAs can/will be configured to
 * automatically convert encodings to a safe type if they receive
 * mail encoded in '8bit' and/or 'binary' encodings.
 */
function MimeifyPageRevision(&$page, &$revision)
{
    // $wikidb =& $revision->_wikidb;
    // $page = $wikidb->getPage($revision->getName());
    // FIXME: add 'hits' to $params
    $params = array('pagename'     => $page->getName(),
                    'flags'        => "",
                    'author'       => $revision->get('author'),
                    'version'      => $revision->getVersion(),
                    'lastmodified' => $revision->get('mtime'));

    if ($page->get('mtime')) {
        $params['created'] = $page->get('mtime');
    }
    if ($page->get('locked')) {
        $params['flags'] = 'PAGE_LOCKED';
    }
    if ($revision->get('author_id')) {
        $params['author_id'] = $revision->get('author_id');
    }
    if ($revision->get('markup')) { // what is the default? we must use 1
        $params['markup'] = $revision->get('markup');
    }
    if ($revision->get('summary')) {
        $params['summary'] = $revision->get('summary');
    }
    if ($page->get('hits')) {
        $params['hits'] = $page->get('hits');
    }
    if ($page->get('owner')) {
        $params['owner'] = $page->get('owner');
    }
    if ($page->get('perm') and class_exists('PagePermission')) {
        $acl = getPagePermissions($page);
        $params['acl'] = $acl->asAclLines();
        //TODO: convert to multiple lines? acl-view => groups,...; acl-edit => groups,...
    }

    $params['charset'] = $GLOBALS['charset'];

    // Non-US-ASCII is not allowed in Mime headers (at least not without
    // special handling) --- so we urlencode all parameter values.
    foreach ($params as $key => $val) {
        $params[$key] = rawurlencode($val);
    }
    if (isset($params['acl'])) {
        // default: "view:_EVERY; edit:_AUTHENTICATED; create:_AUTHENTICATED,_BOGOUSER; ".
        //          "list:_EVERY; remove:_ADMIN,_OWNER; change:_ADMIN,_OWNER; dump:_EVERY; "
        $params['acl'] = str_replace(array("%3A","%3B%20","%2C"), array(":","; ",","), $params['acl']);
    }

    $out = MimeContentTypeHeader('application', 'x-phpwiki', $params);
    $out .= sprintf(
        "Content-Transfer-Encoding: %s\r\n",
        STRICT_MAILABLE_PAGEDUMPS ? 'quoted-printable' : 'binary'
    );

    $out .= "\r\n";

    foreach ($revision->getContent() as $line) {
        // This is a dirty hack to allow saving binary text files. See above.
        $line = rtrim($line);
        if (STRICT_MAILABLE_PAGEDUMPS) {
            $line = QuotedPrintableEncode(rtrim($line));
        }
        $out .= "$line\r\n";
    }
    return $out;
}

/**
 * Routines for parsing Mime-ified phpwiki pages.
 */
function ParseRFC822Headers(&$string)
{
    if (preg_match("/^From (.*)\r?\n/", $string, $match)) {
        $headers['from '] = preg_replace('/^\s+|\s+$/', '', $match[1]);
        $string = substr($string, strlen($match[0]));
    }

    while (
        preg_match(
            '/^([!-9;-~]+) [ \t]* : [ \t]* '
                      . '( .* \r?\n (?: [ \t] .* \r?\n)* )/x',
            $string,
            $match
        )
    ) {
        $headers[strtolower($match[1])]
            = preg_replace('/^\s+|\s+$/', '', $match[2]);
        $string = substr($string, strlen($match[0]));
    }

    if (empty($headers)) {
        return false;
    }

    if (strlen($string) > 0) {
        if (! preg_match("/^\r?\n/", $string, $match)) {
            // No blank line after headers.
            return false;
        }
        $string = substr($string, strlen($match[0]));
    }

    return $headers;
}


function ParseMimeContentType($string)
{
    // FIXME: Remove (RFC822 style comments).

    // Get type/subtype
    if (
        !preg_match(
            ':^\s*(' . MIME_TOKEN_REGEXP . ')\s*'
                    . '/'
                    . '\s*(' . MIME_TOKEN_REGEXP . ')\s*:x',
            $string,
            $match
        )
    ) {
        ExitWiki(sprintf("Bad %s", 'MIME content-type'));
    }

    $type    = strtolower($match[1]);
    $subtype = strtolower($match[2]);
    $string  = substr($string, strlen($match[0]));

    $param = array();
    while (
        preg_match(
            '/^;\s*(' . MIME_TOKEN_REGEXP . ')\s*=\s*'
                      . '(?:(' . MIME_TOKEN_REGEXP . ')|"((?:[^"\\\\]|\\.)*)") \s*/sx',
            $string,
            $match
        )
    ) {
        //" <--kludge for brain-dead syntax coloring
        if (strlen($match[2])) {
            $val = $match[2];
        } else {
            $val = preg_replace('/[\\\\](.)/s', '\\1', $match[3]);
        }

        $param[strtolower($match[1])] = $val;

        $string = substr($string, strlen($match[0]));
    }

    return array($type, $subtype, $param);
}

function ParseMimeMultipart($data, $boundary)
{
    if (!$boundary) {
        ExitWiki("No boundary?");
    }

    $boundary = preg_quote($boundary);

    while (
        preg_match(
            "/^(|.*?\n)--$boundary((?:--)?)[^\n]*\n/s",
            $data,
            $match
        )
    ) {
        $data = substr($data, strlen($match[0]));
        if (! isset($parts)) {
            $parts = array();  // First time through: discard leading chaff
        } else {
            if ($content = ParseMimeifiedPages($match[1])) {
                for (reset($content); $p = current($content); next($content)) {
                    $parts[] = $p;
                }
            }
        }

        if ($match[2]) {
            return $parts;    // End boundary found.
        }
    }
    ExitWiki("No end boundary?");
}

function GenerateFootnotesFromRefs($params)
{
    $footnotes = array();
    foreach ($params as $p => $reference) {
        if (preg_match('/^ref([1-9][0-9]*)$/', $p, $m)) {
            $footnotes[$m[1]] = sprintf(
                _("[%d] See [%s]"),
                $m[1],
                rawurldecode($reference)
            );
        }
    }

    if (sizeof($footnotes) > 0) {
        ksort($footnotes);
        return "-----\n"
            . "!" . _("References") . "\n"
            . join("\n%%%\n", $footnotes) . "\n";
    } else {
        return "";
    }
}

// counterpart to $acl->asAclLines() and rawurl undecode
// default: "view:_EVERY; edit:_AUTHENTICATED; create:_AUTHENTICATED,_BOGOUSER; ".
//          "list:_EVERY; remove:_ADMIN,_OWNER; change:_ADMIN,_OWNER; dump:_EVERY; "
function ParseMimeifiedPerm($string)
{
    if (!class_exists('PagePermission')) {
        return '';
    }
    $hash = array();
    foreach (preg_split("/;/D", trim($string)) as $accessgroup) {
        list($access,$groupstring) = preg_split("/:/D", trim($accessgroup));
        $access = trim($access);
        $groups = preg_split("/,/D", trim($groupstring));
        foreach ($groups as $group) {
            $group = trim($group);
            $bool = (bool) (substr($group, 0, 1) != '-');
            if (substr($group, 0, 1) == '-' or substr($group, 0, 1) == '+') {
                $group = substr($group, 1);
            }
            $hash[$access][$group] = $bool;
        }
    }
    $perm = new PagePermission($hash);
    $perm->sanify();
    return serialize($perm->perm);
}

// Convert references in meta-data to footnotes.
// Only zip archives generated by phpwiki 1.2.x or earlier should have
// references.
function ParseMimeifiedPages($data)
{
    if (
        !($headers = ParseRFC822Headers($data))
        || empty($headers['content-type'])
    ) {
        //trigger_error( sprintf(_("Can't find %s"),'content-type header'),
        //               E_USER_WARNING );
        return false;
    }
    $typeheader = $headers['content-type'];

    if (!(list ($type, $subtype, $params) = ParseMimeContentType($typeheader))) {
        trigger_error(
            sprintf(
                "Can't parse %s: (%s)",
                'content-type',
                $typeheader
            ),
            E_USER_WARNING
        );
        return false;
    }
    if ("$type/$subtype" == 'multipart/mixed') {
        return ParseMimeMultipart($data, $params['boundary']);
    } elseif ("$type/$subtype" != 'application/x-phpwiki') {
        trigger_error(
            sprintf("Bad %s", "content-type: $type/$subtype"),
            E_USER_WARNING
        );
        return false;
    }

    // FIXME: more sanity checking?
    $page        = array();
    $pagedata    = array();
    $versiondata = array();
    $pagedata['date'] = strtotime($headers['date']);

    //DONE: support owner and acl
    foreach ($params as $key => $value) {
        if (empty($value)) {
            continue;
        }
        $value = rawurldecode($value);
        switch ($key) {
            case 'pagename':
            case 'version':
                $page[$key] = $value;
                break;
            case 'flags':
                if (preg_match('/PAGE_LOCKED/', $value)) {
                    $pagedata['locked'] = 'yes';
                }
                break;
            case 'owner':
            case 'created':
            case 'hits':
                $pagedata[$key] = $value;
                break;
            case 'acl':
            case 'perm':
                if (class_exists('PagePermission')) {
                    $pagedata['perm'] = ParseMimeifiedPerm($value);
                }
                break;
            case 'lastmodified':
                $versiondata['mtime'] = $value;
                break;
            case 'author':
            case 'author_id':
            case 'summary':
            case 'markup':
            case 'pagetype':
                $versiondata[$key] = $value;
                break;
        }
    }

    // FIXME: do we need to try harder to find a pagename if we
    //        haven't got one yet?
    if (!isset($versiondata['author'])) {
        global $request;
        if (is_object($request)) {
            $user = $request->getUser();
            $versiondata['author'] = $user->getId(); //FIXME:?
        }
    }

    $encoding = strtolower($headers['content-transfer-encoding']);
    if ($encoding == 'quoted-printable') {
        $data = QuotedPrintableDecode($data);
    } elseif ($encoding && $encoding != 'binary') {
        ExitWiki(sprintf("Unknown %s", 'encoding type: $encoding'));
    }

    $data .= GenerateFootnotesFromRefs($params);

    $page['content'] = preg_replace('/[ \t\r]*\n/', "\n", chop($data));
    $page['pagedata'] = $pagedata;
    $page['versiondata'] = $versiondata;

    return array($page);
}

// $Log: ziplib.php,v $
// Revision 1.45  2005/12/27 18:05:48  rurban
// start with GZIP reading within ZipReader (not yet ready)
//
// Revision 1.44  2005/01/31 00:28:48  rurban
// fix bug 1044945: pgsrc upgrade problem if mime wo/body
//
// Revision 1.43  2005/01/25 08:00:09  rurban
// use unix type to support subdirs with forward slash and long filenames
//
// Revision 1.42  2004/11/16 16:17:51  rurban
// support ENABLE_PAGEPERM=false mime load/save
//
// Revision 1.41  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.40  2004/06/19 12:32:37  rurban
// new TEMP_DIR for ziplib
//
// Revision 1.39  2004/06/08 10:54:47  rurban
// better acl dump representation, read back acl and owner
//
// Revision 1.38  2004/06/08 10:05:11  rurban
// simplified admin action shortcuts
//
// Revision 1.37  2004/06/07 22:28:04  rurban
// add acl field to mimified dump
//
// Revision 1.36  2004/06/07 19:50:40  rurban
// add owner field to mimified dump
//
// Revision 1.35  2004/05/02 21:26:38  rurban
// limit user session data (HomePageHandle and auth_dbi have to invalidated anyway)
//   because they will not survive db sessions, if too large.
// extended action=upgrade
// some WikiTranslation button work
// revert WIKIAUTH_UNOBTAINABLE (need it for main.php)
// some temp. session debug statements
//
// Revision 1.34  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.33  2004/04/12 13:04:50  rurban
// added auth_create: self-registering Db users
// fixed IMAP auth
// removed rating recommendations
// ziplib reformatting
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
