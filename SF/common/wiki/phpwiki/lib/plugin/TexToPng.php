<?php // -*-php-*-
rcs_id('$Id: TexToPng.php,v 1.5 2004/06/19 10:06:38 rurban Exp $');
/**
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

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

// +---------------------------------------------------------------------+
// | TexToPng.php                                                        |
// +---------------------------------------------------------------------+
// | This is a WikiPlugin that surrounds tex commands given as parameter |
// | with a page description and renders it using several existing       |
// | engines into a gif, png or jpeg file.                               |
// | TexToPng is usage example for WikiPluginCached.                     |
// |                                                                     |
// | Copyright (C) 2002 Johannes Große (Johannes Gro&szlig;e)            |
// | You may copy this code freely under the conditions of the GPL       |
// +---------------------------------------------------------------------+

/*-----------------------------------------------------------------------
 | CONFIGURATION
 *----------------------------------------------------------------------*/
// needs (la)tex, dvips, gs, netpbm, libpng
// LaTeX2HTML ftp://ftp.dante.de/tex-archive/support/latex2html
$texbin = '/usr/bin/tex';
$dvipsbin = '/usr/bin/dvips';
$pstoimgbin = '/usr/bin/pstoimg';

// output mere debug messages (should be set to false in a stable 
// version)
   define('TexToPng_debug', false);                                          

/*-----------------------------------------------------------------------
 | OPTION DEFAULTS                                                      
 *----------------------------------------------------------------------*/ 
/*----
 | use antialias for rendering;
 | anitalias: blurs, _looks better_, needs twice space, renders slowlier
 |                                                                      */  
   define('TexToPng_antialias', true);    

/*----
 | Use transparent background; dont combine with antialias on a dark 
 | background. Seems to have a bug: produces strange effects for some 
 | ps-files (almost non readable,blurred output) even when directly 
 | invoked from shell. So its probably a pstoimg bug.
 |                                                                      */  
   define('TexToPng_transparent', false);

/*----
 | default value for rescaling
 | allowed range: 0 - 5 (integer) 
 |                                                                      */  
   define('TexToPng_magstep', 3);            


/*-----------------------------------------------------------------------
 |
 |  Source
 |
 *----------------------------------------------------------------------*/

// check boolean constants

   if (!defined('TexToPng_debug'))       { define('TexToPng_debug', false); }
   if (!defined('TexToPng_antialias'))   { define('TexToPng_antialias', false); }
   if (!defined('TexToPng_transparent')) { define('TexToPng_transparent', false); }

/*-----------------------------------------------------------------------
 | WikiPlugin_TexToPng
 *----------------------------------------------------------------------*/

require_once "lib/WikiPluginCached.php";

class WikiPlugin_TexToPng extends WikiPluginCached
{   
    /* --------- overwrite virtual or abstract methods ---------------- */ 

    function getPluginType() {
        return PLUGIN_CACHED_IMG_ONDEMAND;
    }
 
    function getName() {
        return "TexToPng";
    }

    function getDescription() {
        return _("Converts TeX to an image. May be used to embed formulas in PhpWiki.");
    }
    
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.5 $");
    }

    function getDefaultArguments() {
        return array('tex'          => "",
                     'magstep'      => TexToPng_magstep,
                     'img'          => 'png',
                     'subslash'     => 'off',
                     'antialias'    => TexToPng_antialias   ? 'on' : 'off',
                     'transparent'  => TexToPng_transparent ? 'on' : 'off', 
                     'center'       => 'off');
    }

    function getImage($dbi, $argarray, $request) {
        extract($argarray);
        $this->checkParams($tex, $magstep, $subslash, $antialias, $transparent);
        return $this->TexToImg($tex, $magstep, $antialias, $transparent);
    } // run

    function getExpire($dbi, $argarray, $request) {
        return '0';
    }

    function getImageType($dbi, $argarray, $request) {
        extract($argarray);
        return $img;
    }

    function getAlt($dbi, $argarray, $request) {
        extract($argarray); 
        return $tex; 
    }

    function embedImg($url,$dbi,$argarray,$request) {
        $html = HTML::img( array( 
            'src'   => $url,
            'alt'   => htmlspecialchars($this->getAlt($dbi,$argarray,$request))
            )); 
        if ($argarray['center']=='on')
            return HTML::div( array('style' => 'text-align:center;'), $html);
        return $html;
    }

    /* -------------------- error handling ---------------------------- */

    function dbg( $out ) {
        // test if verbose debug info is selected
        if (TexToPng_debug) {
            $this->complain( $out."\n" );
        } else {
            if (!$this->_errortext) {
                // yeah, I've been told to be quiet, but obviously 
                // an error occured. So at least complain silently.
                $this->complain(' ');
            }
        }

    } // dbg

    /* -------------------- parameter handling ------------------------ */

    function helptext() {
        $aa= TexToPng_antialias  ?'on(default)$|$off':'on$|$off(default)';
        $tp= TexToPng_transparent?'on(default)$|$off':'on$|$off(default)';
        $help = 
          '/settabs/+/indent&$<$?plugin /bf{Tex} & [{/tt transparent}] & = "png(default)$|$jpeg$|$gif"& /cr'."\n".
          '/+&$<$?plugin /bf{TexToPng} & /hfill {/tt tex}           & = "/TeX/  commands"& /cr'."\n".
          '/+&                         & /hfill [{/tt img}]         & = "png(default)$|$jpeg$|$gif"& /cr'."\n".
          '/+&                         & /hfill [{/tt magstep}]     & = "0 to 5 ('.TexToPng_magstep.' default)"& /cr'."\n".
          '/+&                         & /hfill [{/tt center}]      & = "on$|$off(default)"& /cr'."\n".
          '/+&                         & /hfill [{/tt subslash}]    & = "on$|$off(default)"& /cr'."\n".
          '/+&                         & /hfill [{/tt antialias}]   & = "'.$aa.'"& /cr'."\n".
          '/+&                         & /hfill [{/tt transparent}] & = "'.$tp.'"&?$>$ /cr'."\n";
      
        return strtr($help, '/', '\\' );
    } // helptext    


    function checkParams( &$tex, &$magstep, $subslash, &$aalias, &$transp ) {  

        if ($subslash=='on') {
            // WORKAROUND for backslashes
            $tex = strtr($tex,'/','\\');
        }

        // ------- check parameters
        $def = $this->getDefaultArguments();

        if ($tex=='') { $tex = $this->helptext(); }

        if ($magstep < 0 || $magstep > 5 ) { $magstep = $def["magstep"]; }
        // calculate magnification factor
        $magstep = floor(10*pow(1.2,$magstep))/10; 

        $aalias = $aalias != 'off';
        $transp = $transp != 'off';

    } // checkParams

    /* ------------------ image creation ------------------------------ */

    function execute($cmd,$complainvisibly=false) {
        exec($cmd, $errortxt, $returnval);
        $ok = $returnval == 0;
        
        if (!$ok) {
            if (!$complainvisibly) {
                 $this->dbg('Error during execution of '.$cmd );                         
            };
            while (list($key,$value)=each($errortxt)) {
                if ($complainvisibly) { 
                    $this->complain( $value."\n" );
                } else {
                    $this->dbg( $value );             
                }
            }
        }
        return $ok;
    } // execute

    /* ---------------------------------------------------------------- */

    function createTexFile($texfile,$texstr) {
        if ($ok=($fp=fopen($texfile, 'w'))!=0 ) {
            // prepare .tex file
            $texcommands = 
                '\nopagenumbers'   . "\n" .
                '\hoffset=0cm'     . "\n" .
                '\voffset=0cm'     . "\n" . 
            //    '\hsize=20cm'    . "\n" .
            //    '\vsize=10ex'    . "\n" .
                $texstr            . "\n" .
                '\vfill\eject'     . "\n" .
                '\end'             . "\n\n";
            
            $ok = fwrite($fp, $texcommands);
            $ok = fclose($fp) && $ok;  // close anyway
        }
        if (!$ok) {
            $this->dbg('could not write .tex file: ' . $texstr);
        }
        return $ok;
    } // createTexFile

    /* ---------------------------------------------------------------- */            

    function TexToImg($texstr, $scale, $aalias, $transp) {
        //$cacheparams = $GLOBALS['CacheParams'];        
        $tempfiles = $this->tempnam('TexToPng');
        $img = 0; // $size = 0;

        // procuce options for pstoimg
        $options = 
           ($aalias ? '-aaliastext -color 8 ' : '-color 1 ') .
           ($transp ? '-transparent ' : '') .
           '-scale ' . $scale . ' ' .
           '-type png -crop btlr -geometry 600x150 -margins 0,0';

        // rely on intelligent bool interpretation 
        $ok= $tempfiles &&
             $this->createTexFile($tempfiles.'.tex',$texstr) &&
             $this->execute('cd '.$cacheparams['cache_dir'].'; '.
                            "$texbin ".$tempfiles.'.tex',true) &&                  
             $this->execute("$dvipsbin -o".$tempfiles.'.ps '.$tempfiles.'.dvi') &&  
             $this->execute("$pstoimgbin $options"
                            .' -out '.$tempfiles.'.png '.
                            $tempfiles.'.ps'               ) &&
             file_exists( $tempfiles.'.png' );

        if ($ok) {
            if (!($img = ImageCreateFromPNG( $tempfiles.'.png' ))) {
                $this->dbg("Could not open just created image file: $tempfiles");
                $ok = false;
            }     
        }

        // clean up tmpdir; in debug mode only if no error occured

        if ( !TexToPng_debug || (TexToPng_debug && $ok))  {
            if ($tempfiles) {
                unlink($tempfiles);
                unlink($tempfiles . '.ps');        
                unlink($tempfiles . '.tex');
                //unlink($tempfiles . '.aux');
                unlink($tempfiles . '.dvi');
                unlink($tempfiles . '.log');
                unlink($tempfiles . '.png');
            }
        }

        if ($ok) {
            return $img; 
        }
        return false;
    } // TexToImg
} // WikiPlugin_TexToPng

// $Log: TexToPng.php,v $
// Revision 1.5  2004/06/19 10:06:38  rurban
// Moved lib/plugincache-config.php to config/*.ini
// use PLUGIN_CACHED_* constants instead of global $CacheParams
//
// Revision 1.4  2003/01/18 22:08:01  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
