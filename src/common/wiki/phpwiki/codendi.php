<?php
//apd_set_pprof_trace();
define('PHPWIKI_NOMAIN', true);

/**
 * Say if we can display the remove button on a wiki page
 *
 * The wiki page may be driven by another item in the forge (eg a docman document),
 * therefore wiki administrator shouldn't be able to remove the page.
 *
 * @return bool
 */
function display_remove_button($pagename)
{
    $display_remove_button = true;
    $em                    = EventManager::instance();
    $em->processEvent(
        Event::WIKI_DISPLAY_REMOVE_BUTTON,
        [
            'display_remove_button' => &$display_remove_button,
            'group_id'              => GROUP_ID,
            'wiki_page'             => $pagename,
        ]
    );
    return $display_remove_button;
}

function codendi_main()
{
    validateSessionPath();

    global $request;
    if ((DEBUG & _DEBUG_APD) and extension_loaded("apd")) {
        apd_set_session_trace(9);
    }

    // Postpone warnings
    global $ErrorManager;
    if (defined('E_STRICT')) { // and (E_ALL & E_STRICT)) // strict php5?
        $ErrorManager->setPostponedErrorMask(E_NOTICE | E_USER_NOTICE | E_USER_WARNING | E_WARNING | E_STRICT);
    } else {
        $ErrorManager->setPostponedErrorMask(E_NOTICE | E_USER_NOTICE | E_USER_WARNING | E_WARNING);
    }
    $request = new WikiRequest();

    /*
     * Allow for disabling of markup cache.
     * (Mostly for debugging ... hopefully.)
     *
     * See also <?plugin WikiAdminUtils action=purge-cache ?>
     */
    if (! defined('WIKIDB_NOCACHE_MARKUP')) {
        if ($request->getArg('nocache')) { // 1 or purge
            define('WIKIDB_NOCACHE_MARKUP', $request->getArg('nocache'));
        } else {
            define('WIKIDB_NOCACHE_MARKUP', false); // redundant, but explicit
        }
    }

    // Initialize with system defaults in case user not logged in.
    // Should this go into constructor?
    $request->initializeTheme();

    $request->updateAuthAndPrefs();
    $request->initializeLang();

    //FIXME:
    //if ($user->is_authenticated())
    //  $LogEntry->user = $user->getId();

    // Memory optimization:
    // http://www.procata.com/blog/archives/2004/05/27/rephlux-and-php-memory-usage/
    // kill the global PEAR _PEAR_destructor_object_list
    if (! empty($_PEAR_destructor_object_list)) {
        $_PEAR_destructor_object_list = [];
    }
    require_once __DIR__ . '/lib/prepend.php';
    $request->possiblyDeflowerVirginWiki();

    $validators = ['wikiname' => WIKI_NAME,
        'args'     => wikihash($request->getArgs()),
        'prefs'    => wikihash($request->getPrefs()),
    ];
    if (CACHE_CONTROL == 'STRICT') {
        $dbi                  = $request->getDbh();
        $timestamp            = $dbi->getTimestamp();
        $validators['mtime']  = $timestamp;
        $validators['%mtime'] = (int) $timestamp;
    }
    // FIXME: we should try to generate strong validators when possible,
    // but for now, our validator is weak, since equal validators do not
    // indicate byte-level equality of content.  (Due to DEBUG timing output, etc...)
    //
    // (If DEBUG if off, this may be a strong validator, but I'm going
    // to go the paranoid route here pending further study and testing.)
    $validators['%weak'] = true;
    $request->setValidators($validators);

    $request->handleAction();

    if (DEBUG and DEBUG & _DEBUG_INFO) {
        phpinfo(INFO_VARIABLES | INFO_MODULES);
    }
    $request->finish();
}

include_once(PHPWIKI_DIR . "/lib/main.php");

codendi_main();
