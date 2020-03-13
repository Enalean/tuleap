<?php
// -*-php-*-
rcs_id('$Id: PhotoAlbum.php,v 1.14 2005/10/12 06:19:07 rurban Exp $');
/*
 Copyright 2003, 2004, 2005 $ThePhpWikiProgrammingTeam

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
 * Display an album of a set of photos with optional descriptions.
 *
 * @author: Ted Vinke <teddy@jouwfeestje.com>
 *          Reini Urban (local fs)
 *          Thomas Harding (slides mode, real thumbnails)
 *
 * Usage:
 * <?plugin PhotoAlbum
 *          src="http://server/textfile" or localfile or localdir
 *          mode=[normal|column|row|thumbs|tiles|list|slide]
 *          desc=true
 *          numcols=3
 *          height=50%
 *          width=50%
 *          thumbswidth=80
 *          align=[center|left|right]
 *          duration=6
 * ?>
 *
 * "src": textfile of images or directory of images or a single image (local or remote)
 *      Local or remote e.g. http://myserver/images/MyPhotos.txt or http://myserver/images/
 *      Possible content of a valid textfile:
 *     photo-01.jpg; Me and my girlfriend
 *     photo-02.jpg
 *     christmas.gif; Merry Christmas!
 *
 *     Inside textfile, filenames and optional descriptions are seperated by
 *     semi-colon on each line. Listed files must be in same directory as textfile
 *     itself, so don't use relative paths inside textfile.
 *
 * "url": defines the the webpath to the srcdir directory (formerly called weblocation)
 */

/**
 * TODO:
 * - specify picture(s) as parameter(s)
 * - limit amount of pictures on one page
 * - use PHP to really resize or greyscale images (only where GD library supports it)
 *   (quite done for resize with "ImageTile.php")
 *
 * KNOWN ISSUES:
 * - reading height and width from images with spaces in their names fails.
 *
 * Fixed album location idea by Philip J. Hollenback. Thanks!
 */

class ImageTile extends HtmlElement
{
    public function image_tile(/*...*/)
    {
        $el = new HTML('img');
        $tag = func_get_args();
        $params = "<img src='../ImageTile.php?url=" . $tag[0]['src'];
        if (!@empty($tag[0]['width'])) {
            $params .= "&width=" . $tag[0]['width'];
        }
        if (!@empty($tag[0]['height'])) {
            $params .= "&height=" . $tag[0]['height'];
        }
        if (!@empty($tag[0]['width'])) {
            $params .= "' width='" . $tag[0]['width'];
        }
        if (!@empty($tag[0]['height'])) {
            $params .= "' height='" . $tag[0]['height'];
        }

        $params .= "' alt='" . $tag[0]['alt'] . "' />";
        return $el->raw($params);
    }
}

class WikiPlugin_PhotoAlbum extends WikiPlugin
{
    public function getName()
    {
        return _("PhotoAlbum");
    }

    public function getDescription()
    {
        return _("Displays a set of photos listed in a text file with optional descriptions");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.14 $"
        );
    }

// Avoid nameclash, so it's disabled. We allow any url.
// define('allow_album_location', true);
// define('album_location', 'http://kw.jouwfeestje.com/foto/redactie');
// define('album_default_extension', '.jpg');
// define('desc_separator', ';');

    public function getDefaultArguments()
    {
        return array('src'      => '',          // textfile of image list, or local dir.
                     'url'      => '',          // if src=localfs, url prefix (webroot for the links)
                     'mode'    => 'normal',     // normal|thumbs|tiles|list
                         // "normal" - Normal table which shows photos full-size
                         // "thumbs" - WinXP thumbnail style
                         // "tiles"  - WinXP tiles style
                         // "list"   - WinXP list style
                         // "row"    - inline thumbnails
                         // "column" - photos full-size, displayed in 1 column
                         // "slide"  - slideshow mode, needs javascript on client
                     'numcols'    => 3,        // photos per row, columns
                     'showdesc'    => 'both',    // none|name|desc|both
                         // "none"   - No descriptions next to photos
                         // "name"   - Only filename shown
                         // "desc"   - Only description (from textfile) shown
                         // "both"     - If no description found, then filename will be used
                     'link'    => true,     // show link to original sized photo
                         // If true, each image will be hyperlinked to a page where the single
                         // photo will be shown full-size. Only works when mode != 'normal'
                     'attrib'    => '',        // 'sort, nowrap, alt'
                         // attrib arg allows multiple attributes: attrib=sort,nowrap,alt
                         // 'sort' sorts alphabetically, 'nowrap' for cells, 'alt' to use
                        // descs instead of filenames in image ALT-tags
                     'bgcolor'  => '#eae8e8',    // cell bgcolor (lightgrey)
                     'hlcolor'    => '#c0c0ff',    // highlight color (lightblue)
                     'align'    => 'center',    // alignment of table
                     'height'   => 'auto',    // image height (auto|75|100%)
                     'width'    => 'auto',    // image width (auto|75|100%)
                     // Size of shown photos. Either absolute value (e.g. "50") or
                     // HTML style percentage (e.g. "75%") or "auto" for no special
                     // action.
                     'cellwidth' => 'image',    // cell (auto|equal|image|75|100%)
                     // Width of cells in table. Either absolute value in pixels, HTML
                     // style percentage, "auto" (no special action), "equal" (where
                     // all columns are equally sized) or "image" (take height and
                     // width of the photo in that cell).
                     'tablewidth' => false,    // table (75|100%)
                     'p'    => false,     // "displaythissinglephoto.jpg"
                     'h'    => false,     // "highlightcolorofthisphoto.jpg"
                     'duration' => 6, // in slide mode, in seconds
                     'thumbswidth' => 80 //width of thumbnails
                     );
    }
    // descriptions (instead of filenames) for image alt-tags

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));

        $attributes = $attrib ? explode(",", $attrib) : array();
        $photos = array();
        $html = HTML();
        $count = 0;
        // check all parameters
        // what type do we have?
        if (!$src) {
            $showdesc  = 'none';
            $src   = $request->getArg('pagename');
            $error = $this->fromLocation($src, $photos);
        } else {
            $error = $this->fromFile($src, $photos, $url);
        }
        if ($error) {
            return $this->error($error);
        }

        if ($numcols < 1) {
            $numcols = 1;
        }
        if ($align != 'left' && $align != 'center' && $align != 'right') {
            $align = 'center';
        }
        if (count($photos) == 0) {
            return;
        }

        if (in_array("sort", $attributes)) {
            sort($photos);
        }

        if ($p) {
            $mode = "normal";
        }

        if ($mode == "column") {
            $mode = "normal";
            $numcols = "1";
        }

        // set some fixed properties for each $mode
        if ($mode == 'thumbs' || $mode == 'tiles') {
            $attributes = array_merge($attributes, "alt");
            $attributes = array_merge($attributes, "nowrap");
            $cellwidth  = 'auto'; // else cell won't nowrap
            $width      = 50;
        } elseif ($mode == 'list') {
            $numcols    = 1;
            $cellwidth  = "auto";
            $width = 50;
        } elseif ($mode == 'slide') {
            $tableheight = 0;
            $cell_width = 0;
            $numcols = count($photos);
            $keep = $photos;
            foreach ($photos as $value) {
                list($x,$y,$s,$t) = @getimagesize($value['src']);
                if ($height != 'auto') {
                    $y = $this->newSize($y, $height);
                }
                if ($width != 'auto') {
                    $y = round($y * $this->newSize($x, $width) / $x);
                }
                if ($x > $cell_width) {
                    $cell_width = $x;
                }
                if ($y > $tableheight) {
                    $tableheight = $y;
                }
            }
            $tableheight += 50;
            $photos = $keep;
            unset($x, $y, $s, $t, $key, $value, $keep);
        }

        $row = HTML();
        $duration = 1000 * $duration;
        if ($mode == 'slide') {
            $row->pushContent(JavaScript("
i = 0;
function display_slides() {
  j = i - 1;
  cell0 = document.getElementsByName('wikislide' + j);
  cell = document.getElementsByName('wikislide' + i);
  if (cell0.item(0) != null)
    cell0.item(0).style.display='none';
  if (cell.item(0) != null)
    cell.item(0).style.display='block';
  i += 1;
  if (cell.item(0) == null) i = 0;
  setTimeout('display_slides()',$duration);
}
display_slides();"));
        }

        foreach ($photos as $key => $value) {
            if ($p && basename($value["name"]) != "$p") {
                continue;
            }
            if ($h && basename($value["name"]) == "$h") {
                $color = $hlcolor ? $hlcolor : $bgcolor;
            } else {
                $color = $bgcolor;
            }
            // $params will be used for each <img > tag
            $params = array('src'    => $value["name"],
                            'src_tile' => $value["name_tile"],
                            'border' => "0",
                            'alt'    => ($value["desc"] != "" and in_array("alt", $attributes))
                                    ? $value["desc"]
                                    : basename($value["name"]));
            if (!@empty($value['location'])) {
                $params = array_merge($params, array("location" => $value['location']));
            }
            // check description
            switch ($showdesc) {
                case 'none':
                    $value["desc"] = '';
                    break;
                case 'name':
                    $value["desc"] = basename($value["name"]);
                    break;
                case 'desc':
                    break;
                default: // 'both'
                    if (!$value["desc"]) {
                        $value["desc"] = basename($value["name"]);
                    }
                    break;
            }

            // FIXME: get getimagesize to work with names with spaces in it.
            // convert $value["name"] from webpath to local path
            $size = @getimagesize($value["name"]); // try " " => "\\ "
            if (!$size and !empty($value["src"])) {
                $size = @getimagesize($value["src"]);
                if (!$size) {
                    trigger_error(
                        "Unable to getimagesize(" . $value["name"] . ")",
                        E_USER_NOTICE
                    );
                }
            }

            $newwidth = $this->newSize($size[0], $width);
            if (($mode == 'thumbs' || $mode == 'tiles' || $mode == 'list')) {
                if (!empty($size[0])) {
                    $newheight = round(50 * $size[1] / $size[0]);
                } else {
                    $newheight = '';
                }
                if ($height == 'auto') {
                    $height = 150;
                }
            } else {
                $newheight = $this->newSize($size[1], $height);
            }

            if ($width != 'auto' && $newwidth > 0) {
                $params = array_merge($params, array("width" => $newwidth));
            }
            if ($height != 'auto' && $newheight > 0) {
                $params = array_merge($params, array("height" => $newheight));
            }

            // cell operations
            $cell = array('align'   => "center",
                          'valign'  => "top",
                          'bgcolor' => "$color");
            if ($cellwidth != 'auto') {
                if ($cellwidth == 'equal') {
                    $newcellwidth = round(100 / $numcols) . "%";
                } elseif ($cellwidth == 'image') {
                    $newcellwidth = $newwidth;
                } else {
                    $newcellwidth = $cellwidth;
                }
                $cell = array_merge($cell, array("width" => $newcellwidth));
            }
            if (in_array("nowrap", $attributes)) {
                $cell = array_merge($cell, array("nowrap" => "nowrap"));
            }
            //create url to display single larger version of image on page
            $url     = WikiURL(
                $request->getPage(),
                array("p" => basename($value["name"]))
            )
                . "#"
                . basename($value["name"]);

            $b_url    = WikiURL(
                $request->getPage(),
                array("h" => basename($value["name"]))
            )
                . "#"
                . basename($value["name"]);
            $url_text   = $link
                ? HTML::a(array("href" => "$url"), basename($value["desc"]))
                : basename($value["name"]);
            if (! $p) {
                if ($mode == 'normal' || $mode == 'slide') {
                    if (!@empty($params['location'])) {
                        $params['src'] = $params['location'];
                    }
                    unset($params['location'], $params['src_tile']);
                    $url_image = $link ? HTML::a(
                        array("id" => basename($value["name"])),
                        HTML::a(array("href" => "$url"), HTML::img($params))
                    ) :  HTML::img($params);
                } else {
                    $keep = $params;
                    if (!@empty($params['src_tile'])) {
                        $params['src'] = $params['src_tile'];
                    }
                    unset($params['location'], $params['src_tile']);
                    $url_image = $link ? HTML::a(
                        array("id" => basename($value["name"])),
                        HTML::a(
                            array("href" => "$url"),
                            ImageTile::image_tile($params)
                        )
                    ) : HTML::img($params);
                    $params = $keep;
                    unset($keep);
                }
            } else {
                if (!@empty($params['location'])) {
                    $params['src'] = $params['location'];
                }
                unset($params['location'], $params['src_tile']);
                $url_image = $link ? HTML::a(
                    array("id" =>  basename($value["name"])),
                    HTML::a(array("href" => "$b_url"), HTML::img($params))
                ) : HTML::img($params);
            }
            if ($mode == 'list') {
                $url_text = HTML::a(
                    array("id" => basename($value["name"])),
                    $url_text
                );
            }
            // here we use different modes
            if ($mode == 'tiles') {
                $row->pushContent(
                    HTML::td(
                        $cell,
                        HTML::table(
                            array("cellpadding" => 1, "border" => 0),
                            HTML::tr(
                                HTML::td(
                                    array("valign" => "top", "rowspan" => 2),
                                    $url_image
                                ),
                                HTML::td(
                                    array("valign" => "top", "nowrap" => 0),
                                    HTML::span(
                                        array('class' => 'boldsmall'),
                                        ($url_text)
                                    ),
                                    HTML::br(),
                                    HTML::span(
                                        array('class' => 'gensmall'),
                                        ($size[0] .
                                                       " x " .
                                                       $size[1] .
                                        " pixels")
                                    )
                                )
                            )
                        )
                    )
                );
            } elseif ($mode == 'list') {
                $desc = ($showdesc != 'none') ? $value["desc"] : '';
                $row->pushContent(
                    HTML::td(
                        array("valign"  => "top",
                                   "nowrap"  => 0,
                                   "bgcolor" => $color),
                        HTML::span(array('class' => 'boldsmall'), ($url_text))
                    )
                );
                $row->pushContent(
                    HTML::td(
                        array("valign"  => "top",
                                   "nowrap"  => 0,
                                   "bgcolor" => $color),
                        HTML::span(
                            array('class' => 'gensmall'),
                            ($size[0] .
                                               " x " .
                                               $size[1] .
                            " pixels")
                        )
                    )
                );

                if ($desc != '') {
                    $row->pushContent(
                        HTML::td(
                            array("valign"  => "top",
                                       "nowrap"  => 0,
                                       "bgcolor" => $color),
                            HTML::span(array('class' => 'gensmall'), $desc)
                        )
                    );
                }
            } elseif ($mode == 'thumbs') {
                $desc = ($showdesc != 'none') ?
                            HTML::p(HTML::a(
                                array("href" => "$url"),
                                $url_text
                            )) : '';
                $row->pushContent(
                    (HTML::td(
                        $cell,
                        $url_image,
                        // FIXME: no HtmlElement for fontsizes?
                                  // rurban: use ->setAttr("style","font-size:small;")
                                  //         but better use a css class
                                  HTML::span(array('class' => 'gensmall'), $desc)
                    ))
                );
            } elseif ($mode == 'normal') {
                $desc = ($showdesc != 'none') ? HTML::p($value["desc"]) : '';
                $row->pushContent(
                    (HTML::td(
                        $cell,
                        $url_image,
                        // FIXME: no HtmlElement for fontsizes?
                                  HTML::span(array('class' => 'gensmall'), $desc)
                    ))
                );
            } elseif ($mode == 'slide') {
                if ($newwidth == 'auto' || !$newwidth) {
                    $newwidth = $this->newSize($size[0], $width);
                }
                if ($newwidth == 'auto' || !$newwidth) {
                    $newwidth = $size[0];
                }
                if ($newheight != 'auto') {
                    $newwidth = round($size[0] *  $newheight / $size[1]);
                }
                $desc = ($showdesc != 'none') ? HTML::p($value["desc"]) : '';
                if ($count == 0) {
                    $cell = array('style' => 'display: block; '
                                . 'position: absolute; '
                                . 'left: 50% ; '
                                . 'margin-left: -' . round($newwidth / 2) . 'px;'
                                . 'text-align: center; '
                                . 'vertical-align: top',
                                'name' => "wikislide" . $count);
                } else {
                    $cell = array('style' => 'display: none; '
                                . 'position: absolute ;'
                                . 'left: 50% ;'
                                . 'margin-left: -' . round($newwidth / 2) . 'px;'
                                . 'text-align: center; '
                                . 'vertical-align: top',
                                'name' => "wikislide" . $count);
                }
                if ($align == 'left' || $align == 'right') {
                    if ($count == 0) {
                        $cell = array('style' => 'display: block; '
                                              . 'position: absolute; '
                                              . $align . ': 50px; '
                                              . 'vertical-align: top',
                                    'name' => "wikislide" . $count);
                    } else {
                        $cell = array('style' => 'display: none; '
                                              . 'position: absolute; '
                                              . $align . ': 50px; '
                                              . 'vertical-align: top',
                                    'name' => "wikislide" . $count);
                    }
                }
                $row->pushContent(
                    (HTML::td(
                        $cell,
                        $url_image,
                        HTML::span(array('class' => 'gensmall'), $desc)
                    ))
                );
                $count ++;
            } elseif ($mode == 'row') {
                $desc = ($showdesc != 'none') ? HTML::p($value["desc"]) : '';
                $row->pushContent(
                    HTML::table(
                        array("style" => "display: inline"),
                        HTML::tr(HTML::td($url_image)),
                        HTML::tr(HTML::td(
                            array("class" => "gensmall",
                                                      "style" => "text-align: center; "
                                                                . "background-color: $color"),
                            $desc
                        ))
                    )
                );
            } else {
                return $this->error(fmt("Invalid argument: %s=%s", 'mode', $mode));
            }

            // no more images in one row as defined by $numcols
            if (($key + 1) % $numcols == 0 ||
                 ($key + 1) == count($photos) ||
                 $p) {
                if ($mode == 'row') {
                    $html->pushcontent(HTML::span($row));
                } else {
                    $html->pushcontent(HTML::tr($row));
                }
                    unset($row);
                    $row = HTML();
            }
        }

        //create main table
        $table_attributes = array("border"      => 0,
                                  "cellpadding" => 5,
                                  "cellspacing" => 2,
                                  "width"       => $tablewidth);

        if (!@empty($tableheight)) {
            $table_attributes = array_merge(
                $table_attributes,
                array("height"  => $tableheight)
            );
        }
        if ($mode != 'row') {
            $html = HTML::table($table_attributes, $html);
        }
        // align all
        return HTML::div(array("align" => $align), $html);
    }

    /**
     * Calculate the new size in pixels when the original size
     * with a value is given.
     *
     * @param int $oldSize Absolute no. of pixels
     * @param mixed $value Either absolute no. or HTML percentage e.g. '50%'
     * @return int New size in pixels
     */
    public function newSize($oldSize, $value)
    {
        if (trim(substr($value, strlen($value) - 1)) != "%") {
            return $value;
        }
        $value = str_replace("%", "", $value);
        return round(($oldSize * $value) / 100);
    }

    /**
    * fromLocation - read only one picture from fixed album_location
    * and return it in array $photos
    *
    * @param string $src Name of page
    * @param array $photos
    * @return string Error if fixed location is not allowed
    */
    public function fromLocation($src, &$photos)
    {
        /*if (!allow_album_location) {
            return $this->error(_("Fixed album location is not allowed. Please specify parameter src."));
        }*/
        //FIXME!
        if (! IsSafeURL($src)) {
            return $this->error(_("Bad url in src: remove all of <, >, \""));
        }
        $photos[] = array ("name" => $src, //album_location."/$src".album_default_extension,
                           "desc" => "");
    }

    /**
     * fromFile - read pictures & descriptions (separated by ;)
     *            from $src and return it in array $photos
     *
     * @param string $src path to dir or textfile (local or remote)
     * @param array $photos
     * @return string Error when bad url or file couldn't be opened
     */
    public function fromFile($src, &$photos, $webpath = '')
    {
        $src_bak = $src;
        //there has a big security hole... as loading config/config.ini !
        if (!preg_match('/(\.csv|\.jpg|\.jpeg|\.png|\.gif|\/)$/', $src)) {
            return $this->error(_("File extension for csv file has to be '.csv'"));
        }
        if (! IsSafeURL($src)) {
            return $this->error(_("Bad url in src: remove all of <, >, \""));
        }
        if (preg_match('/^(http|ftp|https):\/\//i', $src)) {
            $contents = url_get_contents($src);
            $web_location = 1;
        } else {
            $web_location = 0;
        }
        if (!file_exists($src) and @file_exists(PHPWIKI_DIR . "/$src")) {
            $src = PHPWIKI_DIR . "/$src";
        }
        // check if src is a directory
        if (file_exists($src) and filetype($src) == 'dir') {
            //all images
            $list = array();
            foreach (array('jpeg','jpg','png','gif') as $ext) {
                $fileset = new fileSet($src, "*.$ext");
                $list = array_merge($list, $fileset->getFiles());
            }
            // convert dirname($src) (local fs path) to web path
            natcasesort($list);
            if (! $webpath) {
                // assume relative src. default: "themes/Hawaiian/images/pictures"
                $webpath = DATA_PATH . '/' . $src_bak;
            }
            foreach ($list as $file) {
                // convert local path to webpath
                $photos[] = array ("src" => $file,
                                   "name" => $webpath . "/$file",
                                   "name_tile" =>  $src . "/$file",
                                   "src"  => $src . "/$file",
                                   "desc" => "");
            }
            return;
        }
        // check if $src is an image
        foreach (array('jpeg','jpg','png','gif') as $ext) {
            if (preg_match("/\.$ext$/", $src)) {
                if (!file_exists($src) and @file_exists(PHPWIKI_DIR . "/$src")) {
                    $src = PHPWIKI_DIR . "/$src";
                }
                if ($web_location == 1 and !empty($contents)) {
                    $photos[] = array ("src" => $src,
                                       "name" => $src,
                                       "name_tile" => $src,
                                       "src"  => $src,
                                       "desc" => "");
                    return;
                }
                if (!file_exists($src)) {
                    return $this->error(fmt("Unable to find src='%s'", $src));
                }
                $photos[] = array ("src" => $src,
                                   "name" => "../" . $src,
                                   "name_tile" =>  $src,
                                   "src"  => $src,
                                   "desc" => "");
                return;
            }
        }
        if ($web_location == 0) {
            $fp = @fopen($src, "r");
            if (!$fp) {
                return $this->error(fmt("Unable to read src='%s'", $src));
            }
            while ($data = fgetcsv($fp, 1024, ';')) {
                if (count($data) == 0 || empty($data[0])
                                      || preg_match('/^#/', $data[0])
                                      || preg_match('/^[[:space:]]*$/', $data[0])) {
                    continue;
                }
                if (empty($data[1])) {
                    $data[1] = '';
                }
                $photos[] = array ("name" => dirname($src) . "/" . trim($data[0]),
                                   "location" => "../" . dirname($src) . "/" . trim($data[0]),
                                   "desc" => trim($data[1]),
                                   "name_tile" => dirname($src) . "/" . trim($data[0]));
            }
            fclose($fp);
        } elseif ($web_location == 1) {
            //TODO: checks if the file is an image
            $contents = preg_split('/\n/', $contents);
            foreach ($contents as $value) {
                $data = preg_split('/\;/', $value);
                if (count($data) == 0 || empty($data[0])
                                      || preg_match('/^#/', $data[0])
                                      || preg_match('/^[[:space:]]*$/', $data[0])) {
                    continue;
                }
                if (empty($data[1])) {
                    $data[1] = '';
                }
                $photos[] = array ("name" => dirname($src) . "/" . trim($data[0]),
                                   "src" => dirname($src) . "/" . trim($data[0]),
                                   "desc" => trim($data[1]),
                                   "name_tile" => dirname($src) . "/" . trim($data[0]));
            }
        }
    }
}

// $Log: PhotoAlbum.php,v $
// Revision 1.14  2005/10/12 06:19:07  rurban
// protect unsafe calls
//
// Revision 1.13  2005/09/26 06:39:55  rurban
// re-add lost mode=column|row. by Thomas Harding
//
// Revision 1.12  2005/09/20 19:34:51  rurban
// slide and thumbs mode by Thomas Harding
//
//
// Revision 1.14  2005/09/19 23:49:00 tharding
// added slide mode, correct url retrieving with url_get_contents
//
// Revision 1.13  2005/09/17 18:17:00 tharding
// add resized thumbnails (see ImageTile.php at top-level)
// comment url_get_contents (fopen can open a web location)
//
// Revision 1.11  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.10  2004/12/01 19:34:13  rurban
// Cleanup of CONSTANT pollution.
// renamed weblocation to url.
// allow any url.
// use fixed ";" CSV seperator
// fix substr_replace usage bug.
//
// Revision 1.9  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.8  2004/06/01 15:28:01  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.7  2004/05/03 20:44:55  rurban
// fixed gettext strings
// new SqlResult plugin
// _WikiTranslation: fixed init_locale
//
// Revision 1.6  2004/04/18 00:19:30  rurban
// better default example with local src, don't require weblocation for
// the default setup, better docs, fixed ini_get => get_cfg_var("allow_url_fopen"),
// no HttpClient lib yet.
//
// Revision 1.5  2004/03/09 12:10:23  rurban
// fixed getimagesize problem with local dir.
//
// Revision 1.4  2004/02/28 21:14:08  rurban
// generally more PHPDOC docs
//   see http://xarch.tu-graz.ac.at/home/rurban/phpwiki/xref/
// fxied WikiUserNew pref handling: empty theme not stored, save only
//   changed prefs, sql prefs improved, fixed password update,
//   removed REPLACE sql (dangerous)
// moved gettext init after the locale was guessed
// + some minor changes
//
// Revision 1.3  2004/02/27 08:03:35  rurban
// Update from version 1.2 by Ted Vinke
// implemented the localdir support
//
// Revision 1.2  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.1  2003/01/05 04:21:06  carstenklapp
// New plugin by Ted Vinke (sf tracker patch #661189)
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
