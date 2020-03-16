<?php
// -*-php-*-
rcs_id('$Id: WikiFormRich.php,v 1.15 2004/11/26 18:25:33 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * This is another replacement for MagicPhpWikiURL forms.
 * Previously encoded with the "phpwiki:" syntax.
 *
 * Enhanced WikiForm to be more generic:
 * - editbox[]         name=.. value=.. text=..
 * - checkbox[]     name=.. value=0|1 checked text=..
 * - radio[]             name=.. value=.. text=..
 * - pulldown[]        name=.. value=.. selected=.. text=..
 * - combobox[]        name=.. method=.. value=.. selected=.. text=..
 * - hidden[]        name=.. value=..
 * - submit[]
 * - action, submit buttontext, optional cancel button (bool)
 * - method=get or post, Default: post.

 * @Author: Reini Urban

 * Values which are constants are evaluated.
 * The cancel button must be supported by the action.
 *   (just some wikiadmin actions so far)
 * improve layout by: nobr=1
 * some allow values as list from from <!plugin-list !>

 Samples:
   <?plugin WikiFormRich action=dumpserial method=get
            checkbox[] name=include value="all"
            editbox[] name=directory value=DEFAULT_DUMP_DIR
            editbox[] name=pages value=*
            editbox[] name=exclude value="" ?>
   <?plugin WikiFormRich action=dumphtml method=get
            editbox[] name=directory value=HTML_DUMP_DIR
            editbox[] name=pages value="*"
            editbox[] name=exclude value="" ?>
   <?plugin WikiFormRich action=loadfile method=get
            editbox[]  name=source value=DEFAULT_WIKI_PGSRC
            checkbox[] name=overwrite value=1
            editbox[]  name=exclude value="" ?>
  <?plugin WikiFormRich action=TitleSearch method=get class=wikiadmin nobr=1
         editbox[] name=s text=""
           submit[]
         checkbox[] name=case_exact
         checkbox[] name=regex ?>
  <?plugin WikiFormRich action=FullTextSearch method=get class=wikiadmin nobr=1
         editbox[] name=s text=""
           submit[]
         checkbox[] name=case_exact
         checkbox[] name=regex ?>
  <?plugin WikiFormRich action=FuzzyPages method=get class=wikiadmin nobr=1
         editbox[] name=s text=""
           submit[]
         checkbox[] name=case_exact ?>
  <?plugin WikiFormRich action=AppendText buttontext="AddPlugin"
         radio[] name=s value=<!plugin-list BackLinks page=WikiPlugin limit=10 !>
         ?>
  <?plugin WikiFormRich action=AppendText buttontext="AddPlugin"
         pulldown[] name=s text="Plugins: " value=<!plugin-list BackLinks page=WikiPlugin !>
         ?>
  <?plugin WikiFormRich action=AppendText buttontext="AddCategory"
         pulldown[] name=s text="Categories: " value=<!plugin-list TitleSearch s=Category !>
         ?>
*/

class WikiPlugin_WikiFormRich extends WikiPlugin
{
    public function getName()
    {
        return "WikiFormRich";
    }
    public function getDescription()
    {
        return _("Provide generic WikiForm input buttons");
    }
    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.15 $"
        );
    }
    public function getDefaultArguments()
    {
        return array('action' => false,     // required argument
                     'method' => 'post',    // or get
                     'class'  => 'wikiaction',
                     'buttontext' => false, // for the submit button. default: action
                     'cancel' => false,     // boolean if the action supports cancel also
                     'nobr' => false,       // "no break": linebreaks or not
                     );
    }

    /* TODO: support better block alignment: <br>, tables, indent
     */
    public function handle_plugin_args_cruft($argstr, $args)
    {
        $allowed = array("editbox", "hidden", "checkbox", "radiobutton"/*deprecated*/,
                 "radio", "pulldown", "submit", "reset", "combobox");
        // no editbox[] = array(...) allowed (space)
        $arg_array = preg_split("/\n/", $argstr);
        // for security we should check this better
        $arg = '';
        for ($i = 0; $i < count($arg_array); $i++) {
            //TODO: we require an name=value pair here, but submit may go without also.
            if (preg_match("/^\s*(" . join("|", $allowed) . ")\[\](.*)$/", $arg_array[$i], $m)) {
                $name = $m[1]; // one of the allowed input types
                $this->inputbox[][$name] = array();
                $j = count($this->inputbox) - 1;
                $curargs = trim($m[2]);
                // must match name=NAME and also value=<!plugin-list name !>
                while (preg_match(
                    "/^(\w+)=((?:\".*\")|(?:\w+)|(?:\"?<!plugin-list.+!>\"?))\s*/",
                    $curargs,
                    $m
                )) {
                       $attr = $m[1];
                    $value = $m[2];
                       $curargs = substr($curargs, strlen($m[0]));
                    if (preg_match("/^\"(.*)\"$/", $value, $m)) {
                        $value = $m[1];
                    }
                    if (in_array($name, array("pulldown","checkbox","radio","radiobutton","combobox"))
                            and preg_match('/^<!plugin-list.+!>$/', $value, $m)) {
                    // like pulldown[] name=test value=<!plugin-list BackLinks page=HomePage!>
                        $loader = new WikiPluginLoader();
                        $markup = null;
                        $basepage = null;
                        $plugin_str = preg_replace(array("/^<!/","/!>$/"), array("<?","?>"), $value);
                 // will return a pagelist object! pulldown,checkbox,radiobutton
                        $value = $loader->expandPI($plugin_str, $GLOBALS['request'], $markup, $basepage);
                        if (isa($value, 'PageList')) {
                            $value = $value->_pages;
                        } elseif (!is_array($value)) {
                            trigger_error(
                                sprintf("Invalid argument %s ignored", htmlentities($arg_array[$i])),
                                E_USER_WARNING
                            );
                        }
                    } elseif (defined($value)) {
                        $value = constant($value);
                    }
                        $this->inputbox[$j][$name][$attr] = $value;
                }
             //trigger_error("not yet finished");
                //eval('$this->inputbox[]["'.$m[1].'"]='.$m[2].';');
            } else {
                trigger_error(
                    sprintf("Invalid argument %s ignored", htmlentities($arg_array[$i])),
                    E_USER_WARNING
                );
            }
        }
        return;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        if (empty($action)) {
            return $this->error(fmt("A required argument '%s' is missing.", "action"));
        }
        $form = HTML::form(
            array('action' => $request->getPostURL(),
                                 'method' => strtolower($method),
                                 'class'  => 'wikiaction',
                                 'accept-charset' => $GLOBALS['charset']),
            HiddenInputs(array('action' => $action, 'group_id' => GROUP_ID))
        );
        $nbsp = HTML::Raw('&nbsp;');
        $already_submit = 0;
        foreach ($this->inputbox as $inputbox) {
            foreach ($inputbox as $inputtype => $input) {
                if ($inputtype == 'radiobutton') {
                    $inputtype = 'radio'; // convert from older versions
                }
                $input['type'] = $inputtype;
                $text = '';
                if ($inputtype != 'submit') {
                    if (empty($input['name'])) {
                        return $this->error(fmt(
                            "A required argument '%s' is missing.",
                            $inputtype . "[][name]"
                        ));
                    }
                    if (!isset($input['text'])) {
                        $input['text'] = gettext($input['name']);
                    }
                    $text = $input['text'];
                    unset($input['text']);
                }
                switch ($inputtype) {
                    case 'checkbox':
                    case 'radio':
                        if (empty($input['value'])) {
                            $input['value'] = 1;
                        }
                        if (is_array($input['value'])) {
                            $div = HTML::div(array('class' => $class));
                            $values = $input['value'];
                            $name = $input['name'];
                            $input['name'] = $inputtype == 'checkbox' ? $name . "[]" : $name;
                            foreach ($values as $val) {
                                $input['value'] = $val;
                                if ($request->getArg($name)) {
                                    if ($request->getArg($name) == $val) {
                                        $input['checked'] = 'checked';
                                    } else {
                                        unset($input['checked']);
                                    }
                                }
                                $div->pushContent(HTML::input($input), $nbsp, $val, $nbsp, "\n");
                                if (!$nobr) {
                                    $div->pushContent(HTML::br());
                                }
                            }
                            $form->pushContent($div);
                        } else {
                            if (empty($input['checked'])) {
                                if ($request->getArg($input['name'])) {
                                    $input['checked'] = 'checked';
                                }
                            } else {
                                $input['checked'] = 'checked';
                            }
                            if ($nobr) {
                                $form->pushContent(HTML::input($input), $nbsp, $text, $nbsp);
                            } else {
                                $form->pushContent(HTML::div(array('class' => $class), HTML::input($input), $text));
                            }
                        }
                        break;
                    case 'editbox':
                          $input['type'] = 'text';
                        if (empty($input['value']) and ($s = $request->getArg($input['name']))) {
                            $input['value'] = $s;
                        }
                        if ($nobr) {
                            $form->pushContent(HTML::input($input), $nbsp, $text, $nbsp);
                        } else {
                            $form->pushContent(HTML::div(array('class' => $class), HTML::input($input), $text));
                        }
                        break;
                    case 'combobox':
                          // TODO: moACDROPDOWN
                          $values = $input['value'];
                          unset($input['value']);
                          $input['type'] = 'text';
                        if (is_string($values)) {
                            $values = explode(",", $values);
                        }
                        if (empty($values)) {
                            if ($input['method']) {
                                $input['value'] = xmlrequest($input['method']);
                            } elseif ($s = $request->getArg($input['name'])) {
                                $input['value'] = $s;
                            }
                        } elseif (is_array($values)) {
                            $name = $input['name'];
                            unset($input['name']);
                            foreach ($values as $val) {
                                $input = array('value' => $val);
                                if ($request->getArg($name)) {
                                    if ($request->getArg($name) == $val) {
                                        $input['selected'] = 'selected';
                                    } else {
                                        unset($input['selected']);
                                    }
                                }
                                //$select->pushContent(HTML::option($input, $val));
                            }
                        }
                        if ($nobr) {
                            $form->pushContent(HTML::input($input), $nbsp, $text, $nbsp);
                        } else {
                            $form->pushContent(HTML::div(array('class' => $class), HTML::input($input), $text));
                        }
                        break;
                    case 'pulldown':
                          $values = $input['value'];
                          unset($input['value']);
                          unset($input['type']);
                          $select = HTML::select($input);
                        if (is_string($values)) {
                            $values = explode(",", $values);
                        }
                        if (empty($values) and ($s = $request->getArg($input['name']))) {
                            $select->pushContent(HTML::option(array('value' => $s), $s));
                        } elseif (is_array($values)) {
                            $name = $input['name'];
                            unset($input['name']);
                            foreach ($values as $val) {
                                $input = array('value' => $val);
                                if ($request->getArg($name)) {
                                    if ($request->getArg($name) == $val) {
                                        $input['selected'] = 'selected';
                                    } else {
                                        unset($input['selected']);
                                    }
                                }
                                $select->pushContent(HTML::option($input, $val));
                            }
                        }
                        $form->pushContent($text, $nbsp, $select);
                        break;
                    case 'reset':
                    case 'hidden':
                          $form->pushContent(HTML::input($input));
                        break;
                // change the order of inputs, by explicitly placing a submit button here.
                    case 'submit':
                          //$input['type'] = 'submit';
                        if (empty($input['value'])) {
                            $input['value'] = $buttontext ? $buttontext : $action;
                        }
                          unset($input['text']);
                        if (empty($input['class'])) {
                            $input['class'] = $class;
                        }
                        if ($nobr) {
                            $form->pushContent(HTML::input($input), $nbsp, $text, $nbsp);
                        } else {
                            $form->pushContent(HTML::div(array('class' => $class), HTML::input($input), $text));
                        }
                          // unset the default submit button
                          $already_submit = 1;
                        break;
                }
            }
        }
        if ($request->getArg('start_debug')) {
            $form->pushContent(HTML::input(array('name' => 'start_debug',
                                      'value' =>  $request->getArg('start_debug'),
            'type'  => 'hidden')));
        }
        if (!USE_PATH_INFO) {
            $form->pushContent(HiddenInputs(array('pagename' => $basepage)));
        }
        if (!$already_submit) {
            if (empty($buttontext)) {
                $buttontext = $action;
            }
            $submit = Button('submit:', $buttontext, $class);
            if ($cancel) {
                $form->pushContent(HTML::span(
                    array('class' => $class),
                    $submit,
                    Button('submit:cancel', _("Cancel"), $class)
                ));
            } else {
                $form->pushContent(HTML::span(
                    array('class' => $class),
                    $submit
                ));
            }
        }
        return $form;
    }
}

// $Log: WikiFormRich.php,v $
// Revision 1.15  2004/11/26 18:25:33  rurban
// pulldown[] values="val1,val2,val3,..." simple comma seperated values
//
// Revision 1.14  2004/11/25 17:20:52  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.13  2004/11/25 12:04:17  rurban
// support extra submit[] and reste[] buttons to place it before. renamed radiobutton to radio
//
// Revision 1.12  2004/11/24 15:21:19  rurban
// docs
//
// Revision 1.11  2004/11/24 15:19:57  rurban
// allow whitespace in quoted text args
//
// Revision 1.10  2004/11/24 15:07:49  rurban
// added pulldown support, fixed plugin-list whitespace splitting
//
// Revision 1.9  2004/11/24 13:55:42  rurban
// omit unneccessary pagename arg
//
// Revision 1.8  2004/11/24 10:58:50  rurban
// just docs
//
// Revision 1.7  2004/11/24 10:40:04  rurban
// better nobr, allow empty text=""
//
// Revision 1.5  2004/11/24 10:14:36  rurban
// fill-in request args as with plugin-form
//
// Revision 1.4  2004/11/23 15:17:20  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.3  2004/07/09 13:05:34  rurban
// just aesthetics
//
// Revision 1.2  2004/07/09 10:25:52  rurban
// fix the args parser
//
// Revision 1.1  2004/07/02 11:03:53  rurban
// renamed WikiFormMore to WikiFormRich: better syntax, no eval (safer)
//
// Revision 1.3  2004/07/01 13:59:25  rurban
// enhanced to allow arbitrary order of args and stricter eval checking
//
// Revision 1.2  2004/07/01 13:14:01  rurban
// desc only
//
// Revision 1.1  2004/07/01 13:11:53  rurban
// more generic forms
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
