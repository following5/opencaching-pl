<?php

/**
 * class autoloader
 */
require_once __DIR__ . '/ClassPathDictionary.php';

use Utils\Database\XDb;
use Utils\Database\OcDb;
use Utils\View\View;
use Utils\Uri\Uri;
use Utils\I18n\I18n;
use Utils\I18n\Languages;

session_start();

//kojoty: do we need no-ob check ???
//if ((!isset($GLOBALS['no-ob'])) || ($GLOBALS['no-ob'] == false))
ob_start();

//kojoty: do we need it ???
//if ((!isset($GLOBALS['oc_waypoint'])) && isset($GLOBALS['ocWP']))
//    $GLOBALS['oc_waypoint'] = $GLOBALS['ocWP'];


if (!isset($rootpath)){
    if(isset($GLOBALS['rootpath'])){
        $rootpath =  $GLOBALS['rootpath'];
    }else{
        $rootpath = "./";
    }
}

require_once($rootpath . 'lib/settings.inc.php');

// TODO: kojoty: it should be removed after config refactoring
// now if common.inc.php is not loaded in global context settings are not accessible
$GLOBALS['config'] = $config;
$GLOBALS['lang'] = $lang;
$GLOBALS['style'] = $style;

require_once($rootpath . 'lib/common_tpl_funcs.php'); // template engine
require_once($rootpath . 'lib/cookie.class.php');     // class used to deal with cookies
require_once($rootpath . 'lib/language.inc.php');     // main translation funcs
require_once($rootpath . 'lib/auth.inc.php');         // authentication funcs

// yepp, we will use UTF-8
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
mb_language('uni');


//detecting errors
//TODO: this is never set and should be removed but it needs to touch hungreds of files...
$error = false;

//site in service?
if ($site_in_service == false) {
    header('Content-type: text/html; charset=utf-8');
    $page_content = file_get_contents($rootpath . 'html/outofservice.tpl.php');
    die($page_content);
}

initTemplateSystem();

processAuthentication();

loadTranslation();



function processAuthentication(){

    $db = OcDb::instance();

    //user authenification from cookie
    auth_user();

    global $view;

    if ($GLOBALS['usr'] == false) { // no-user-logged-in

        $view->setVar('_isUserLogged', false);
        $view->setVar('_target',Uri::getCurrentUri());

    } else { // user-logged-in

        // check for user_id in session
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $GLOBALS['usr']['userid'];
        }

        if($GLOBALS['config']['checkRulesConfirmation']){
            // check for rules confirmation
            $rules_confirmed = $db->multiVariableQueryValue(
                "SELECT `rules_confirmed` FROM `user` WHERE `user_id` = :1", 0, $GLOBALS['usr']['userid']);

            if ($rules_confirmed == 0) {
                if (!isset($_SESSION['called_from_confirm']))
                    header("Location: confirm.php");
                    else
                        unset($_SESSION['called_from_confirm']);
            }
        }

        if (!(isset($_SESSION['logout_cookie']))) {
            $_SESSION['logout_cookie'] = mt_rand(1000, 9999) . mt_rand(1000, 9999);
        }

        $view->setVar('_isUserLogged', true);
        $view->setVar('_username', $GLOBALS['usr']['username']);
        $view->setVar('_logoutCookie', $_SESSION['logout_cookie']);


        $GLOBALS['usr']['admin'] = $db->multiVariableQueryValue(
            'SELECT admin FROM user WHERE user_id=:1', 0, $GLOBALS['usr']['userid']);

    }
}

function initTemplateSystem(){

    global $rootpath, $style;

    // set up the style path
    // TODO: in fact we have only one style: stdstyle
    // so we can drop it in future
    if (!isset($GLOBALS['stylepath'])){
        $GLOBALS['stylepath'] = $rootpath . 'tpl/' . $style;
    }

    // create global view variable (used in templates)
    // TODO: it should be moved to context..
    $GLOBALS['view'] = new View();

    //by default, use start template
    if (!isset($GLOBALS['tplname'])){
        $GLOBALS['tplname'] = 'start';
    }


    // load vars from settings...
    //global $site_name, $contact_mail, $wikiLinks;

    tpl_set_var('site_name', $GLOBALS['site_name']);
    tpl_set_var('contact_mail', $GLOBALS['contact_mail']);

    foreach($GLOBALS['wikiLinks'] as $key => $value){
        tpl_set_var('wiki_link_'.$key, $value);
    }

    tpl_set_var('title', htmlspecialchars($GLOBALS['pagetitle'], ENT_COMPAT, 'UTF-8'));
    tpl_set_var('lang', $GLOBALS['lang']);
    tpl_set_var('style', $GLOBALS['style']);
    tpl_set_var('bodyMod', '');
    tpl_set_var('cachemap_header', '');
    tpl_set_var('htmlheaders', '');


    $GLOBALS['tpl_subtitle'] = '';
}

function loadTranslation(){

        global $lang, $cookie;
        if ($cookie->is_set('lang')) {
            $lang = $cookie->get('lang');
        }

        //language changed?
        if(isset($_REQUEST['lang'])){
            $lang = $_REQUEST['lang'];
        }

        //check if $lang is supported by site
        if(!I18n::isTranslationSupported($lang)){

            // requested language is not supported - display error...

            tpl_set_tplname('error/langNotSupported');
            header("HTTP/1.0 404 Not Found");

            $view->loadJQuery();
            $view->setVar("localCss",
                Uri::getLinkWithModificationTime('/tpl/stdstyle/error/error.css'));
            $view->setVar('requestedLang', $lang);
            $lang = 'en'; //English must be always supported

            $view->setVar('allLanguageFlags', I18n::getLanguagesFlagsData());
            load_language_file($lang);

            tpl_BuildTemplate();
            exit;
        }

        // load language settings
        load_language_file($lang);
        Languages::setLocale($lang);

}







/*
 * TODO: Remove all functions below from here!
 */




// decimal longitude to string E/W hhh°mm.mmm
function help_lonToDegreeStr($lon, $type = 1)
{
    if ($lon < 0) {
        $retval = 'W ';
        $lon = -$lon;
    } else {
        $retval = 'E ';
    }


    if ($type == 1) {
        $retval = $retval . sprintf("%02d", floor($lon)) . '° ';
        $lon = $lon - floor($lon);
        $retval = $retval . sprintf("%06.3f", round($lon * 60, 3)) . '\'';
    } else if ($type == 0) {
        $retval .= sprintf("%.5f", $lon) . '° ';
    } else if ($type == 2) {
        $retval = $retval . sprintf("%02d", floor($lon)) . '° ';
        $lon = $lon - floor($lon);
        $lon *= 60;
        $retval = $retval . sprintf("%02d", floor($lon)) . '\' ';

        $lonmin = $lon - floor($lon);
        $retval = $retval . sprintf("%02.02f", $lonmin * 60) . '\'\'';
    }

    return $retval;
}

// decimal latitude to string N/S hh°mm.mmm
function help_latToDegreeStr($lat, $type = 1)
{
    if ($lat < 0) {
        $retval = 'S ';
        $lat = -$lat;
    } else {
        $retval = 'N ';
    }

    if ($type == 1) {
        $retval = $retval . sprintf("%02d", floor($lat)) . '° ';
        $lat = $lat - floor($lat);
        $retval = $retval . sprintf("%06.3f", round($lat * 60, 3)) . '\'';
    } else if ($type == 0) {
        $retval .= sprintf("%.5f", $lat) . '° ';
    } else if ($type == 2) {
        $retval = $retval . sprintf("%02d", floor($lat)) . '° ';
        $lat = $lat - floor($lat);
        $lat *= 60;
        $retval = $retval . sprintf("%02d", floor($lat)) . '\' ';

        $latmin = $lat - floor($lat);
        $retval = $retval . sprintf("%02.02f", $latmin * 60) . '\'\'';
    }

    return $retval;
}

/**
 * This function checks if given table contains column of given name
 * @param unknown $tableName
 * @param unknown $columnName
 * @return 1 on success 0 in failure
 */
function checkField($tableName, $columnName)
{
    $tableName = XDb::xEscape($tableName);
    $stmt = XDb::xSql("SHOW COLUMNS FROM $tableName" );
    while( $column = XDb::xFetchArray($stmt)){
        if( $column['Field'] == $columnName ){
            return 1;
        }
    }
    return 0;
}

function fixPlMonth($string)
{
    $string = str_ireplace('styczeń', 'stycznia', $string);
    $string = str_ireplace('luty', 'lutego', $string);
    $string = str_ireplace('marzec', 'marca', $string);
    $string = str_ireplace('kwiecień', 'kwietnia', $string);
    $string = str_ireplace('maj', 'maja', $string);
    $string = str_ireplace('czerwiec', 'czerwca', $string);
    $string = str_ireplace('lipiec', 'lipca', $string);
    $string = str_ireplace('sierpień', 'sierpnia', $string);
    $string = str_ireplace('wrzesień', 'września', $string);
    $string = str_ireplace('październik', 'października', $string);
    $string = str_ireplace('listopad', 'listopada', $string);
    $string = str_ireplace('grudzień', 'grudnia', $string);
    return $string;
}

/**
 * class witch common methods
 */
class common
{

    /**
     * add slashes to each element of $array.
     * @param array $array
     */
    public static function sanitize(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::sanitize($value);
            } else {
                $array[$key] = addslashes(htmlspecialchars($value));
            }
        }
    }

    public static function buildCacheSizeSelector($sel_type, $sel_size)
    {
        $cache = cache::instance();
        $cacheSizes = $cache->getCacheSizes();

        $sizes = '<option value="-1" disabled selected="selected">' . tr('select_one') . '</option>';
        foreach ($cacheSizes as $size) {
            if ($sel_type == cache::TYPE_EVENT || $sel_type == cache::TYPE_VIRTUAL || $sel_type == cache::TYPE_WEBCAM) {
                if ($size['id'] == cache::SIZE_NOCONTAINER) {
                    $sizes .= '<option value="' . $size['id'] . '" selected="selected">' . tr($size['translation']) . '</option>';
                } else {
                    $sizes .= '<option value="' . $size['id'] . '">' . tr($size['translation']) . '</option>';
                }
            } elseif ($size['id'] != cache::SIZE_NOCONTAINER) {
                if ($size['id'] == $sel_size) {
                    $sizes .= '<option value="' . $size['id'] . '" selected="selected">' . tr($size['translation']) . '</option>';
                } else {
                    $sizes .= '<option value="' . $size['id'] . '">' . tr($size['translation']) . '</option>';
                }
            }
        }
        return $sizes;
    }

    /**
     * @param type $db
     */
    public static function getUserActiveCacheCountByType($db, $userId)
    {
        $query = 'SELECT type, count(*) as cacheCount FROM `caches` WHERE `user_id` = :1 AND STATUS !=3 GROUP by type';
        $s = $db->multiVariableQuery($query, $userId);
        $userCacheCountByType = $db->dbResultFetchAll($s);
        $cacheLimitByTypePerUser = array();
        foreach ($userCacheCountByType as $cacheCount) {
            $cacheLimitByTypePerUser[$cacheCount['type']] = $cacheCount['cacheCount'];
        }
        return $cacheLimitByTypePerUser;
    }

}
