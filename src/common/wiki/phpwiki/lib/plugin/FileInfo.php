<?php
// -*-php-*-
rcs_id('$Id: FileInfo.php,v 1.4 2005/10/29 14:18:47 rurban Exp $');
/*
 Copyright 2005 $ThePhpWikiProgrammingTeam

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
 * This plugin displays the version, date, size, perms of an uploaded file.
 * Only files relative and below to the uploads path can be handled.
 *
 * Usage:
 *   <?plugin FileVersion file=uploads/setup.exe display=version,date ?>
 *   <?plugin FileVersion file=uploads/setup.exe display=name,version,date
 *                        format="%s (version: %s, date: %s)" ?>
 *
 * @author: ReiniUrban
 */

class WikiPlugin_FileInfo extends WikiPlugin
{
    public function getName()
    {
        return _("FileInfo");
    }

    public function getDescription()
    {
        return _("Display file information like version,size,date,... of uploaded files.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.4 $"
        );
    }

    public function getDefaultArguments()
    {
        return array(
                     'file'      => false, // relative path from PHPWIKI_DIR. (required)
                     'display'   => false, // version,size,date,mtime,owner,name,path,dirname,link.  (required)
                     'format'    => false, // printf format string with %s only, all display modes
                            // from above vars return strings (optional)
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        if (!$file) {
            return $this->error(sprintf(_("A required argument '%s' is missing."), 'file'));
        }
        if (!$display) {
            return $this->error(sprintf(_("A required argument '%s' is missing."), 'display'));
        }

        $dir = getcwd();
        chdir(PHPWIKI_DIR);
    // sanify $file name
        if (!file_exists($file)) {
            trigger_error("file \"$file\" not found", E_USER_WARNING);
        }
        $realfile = realpath($file);
        if (!string_starts_with($realfile, realpath(getUploadDataPath()))) {
            return $this->error("invalid path \"$file\"");
        } else {
            $isuploaded = 1;
        }
        $s = array();
        $modes = explode(",", $display);
        foreach ($modes as $mode) {
            switch ($mode) {
                case 'version':
                    $s[] = $this->exeversion($file);
                    break;
                case 'size':
                    $s[] = filesize($file);
                    break;
                case 'phonysize':
                    $s[] = $this->phonysize(filesize($file));
                    break;
                case 'date':
                    $s[] = strftime("%x %X", filemtime($file));
                    break;
                case 'mtime':
                    $s[] = filemtime($file);
                    break;
                case 'name':
                    $s[] = basename($file);
                    break;
                case 'path':
                    $s[] = $file;
                    break;
                case 'dirname':
                    $s[] = dirname($file);
                    break;
                case 'magic':
                    $s[] = $this->magic($file);
                    break;
                case 'mime-typ':
                    $s[] = $this->mime_type($file);
                    break;
                case 'link':
                    if ($isuploaded) {
                              $s[] = "[Upload:" . basename($file) . "]";
                    } else {
                                $s[] = "[" . basename($file) . "]";
                    }
                    break;
                default:
                    return $this->error(sprintf(_("Unsupported argument: %s=%s"), 'display', $mode));
                break;
            }
        }
        chdir($dir);
        if (!$format) {
            $format = '';
            foreach ($s as $x) {
                $format .= " %s";
            }
        }
        array_unshift($s, $format);
    // $x, array($i,$j) => sprintf($x, $i, $j)
        $result = call_user_func_array("sprintf", $s);
        if (in_array('link', $modes)) {
            require_once("lib/InlineParser.php");
            return TransformInline($result);
        } else {
            return $result;
        }
    }

    public function magic($file)
    {
        if (function_exists('finfo_file') or loadPhpExtension('fileinfo')) {
            // Valid finfo_open (i.e. libmagic) options:
            // FILEINFO_NONE | FILEINFO_SYMLINK | FILEINFO_MIME | FILEINFO_COMPRESS | FILEINFO_DEVICES |
            // FILEINFO_CONTINUE | FILEINFO_PRESERVE_ATIME | FILEINFO_RAW
            $f = finfo_open(/*FILEINFO_MIME*/);
            $result = finfo_file(realpath($file));
            finfo_close($res);
            return $result;
        }
        return '';
    }

    public function mime_type($file)
    {
        return '';
    }

    public function _formatsize($n, $factor, $suffix = '')
    {
        if ($n > $factor) {
            $b = $n / $factor;
            $n -= floor($factor * $b);
            return number_format($b, $n ? 3 : 0) . $suffix;
        }
    }
    public function phonysize($a)
    {
        $factor = 1024 * 1024 * 1000;
        if ($a > $factor) {
            return $this->_formatsize($a, $factor, ' GB');
        }
        $factor = 1024 * 1000;
        if ($a > $factor) {
            return $this->_formatsize($a, $factor, ' MB');
        }
        $factor = 1024;
        if ($a > $factor) {
            return $this->_formatsize($a, $factor, ' KB');
        }
        if ($a > 1) {
            return $this->_formatsize($a, 1, ' byte');
        } else {
            return $a;
        }
    }

    public function exeversion($file)
    {
        if (!isWindows()) {
            return "?";
        }
        if (function_exists('res_list_type') or loadPhpExtension('win32std')) {
            return $this->exeversion_resopen($file);
        }
        return exeversion_showver($file);
        return '';
    }

    // http://www.codeproject.com/dll/showver.asp
    public function exeversion_showver($file)
    {
        $path = realpath($file);
        $result = `showver $path`;
        return "?";
    }

    // Read "RT_VERSION/VERSIONINFO" exe/dll resource info for MSWin32 binaries
    // The "win32std" extension is not ready yet to pass back a VERSIONINFO struct
    public function exeversion_resopen($file)
    {
        if (function_exists('res_list_type') or loadPhpExtension('win32std')) {
            // See http://msdn.microsoft.com/workshop/networking/predefined/res.asp
            $v = file_get_contents('res://' . realpath($file) . urlencode('/RT_VERSION/#1'));
            if ($v) {
             // This is really a binary VERSIONINFO block, with lots of
             // nul bytes (widechar) which cannot be transported as string.
                return "$v";
            } else {
                $h = res_open(realpath($file));
                $v = res_get($h, 'RT_VERSION', 'FileVersion');
                res_close($h);
                if ($v) {
                    return $v;
                }

                $h = res_open(realpath($file));
                $v = res_get($h, '#1', 'RT_VERSION', 1);
                res_close($h);
                if ($v) {
                    return $v;
                }
            }

            /* The version consists of two 32-bit integers, defined by four 16-bit integers.
               For example, "FILEVERSION 3,10,0,61" is translated into two doublewords:
               0x0003000a and 0x0000003d, in that order. */
       /*
        $h = res_open(realpath($file));

        echo "Res list of '$file': \n";
        $list= res_list_type($h, true);
        if( $list===FALSE ) err( "Can't list type" );

        for( $i= 0; $i<count($list); $i++ ) {
         echo $list[$i]."\n";
         $res= res_list($h, $list[$i]);
         for( $j= 0; $j<count($res); $j++ ) {
          echo "\t".$res[$j]."\n";
         }
        }
        echo "Res get: ".res_get( $h, 'A_TYPE', 'A_RC_NAME' )."\n\n";
        res_close( $h );
       */
            if ($v) {
                return "$v";
            } else {
                return "";
            }
        } else {
            return "";
        }
    }
}

/*
 $Log: FileInfo.php,v $
 Revision 1.4  2005/10/29 14:18:47  rurban
 add display=phonysize

 Revision 1.3  2005/10/29 13:35:00  rurban
 fix Log:, add chdir() if not in PHPWIKI_DIR, fix ->warning


*/

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
