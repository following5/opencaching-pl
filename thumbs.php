<?php
/**
 * Produce thumbs for pictures
 *
 * @param uuid
 *
 */

use src\Utils\Database\XDb;
use src\Models\Pictures\Thumbnail;
use src\Models\OcConfig\OcConfig;

require_once (__DIR__.'/lib/common.inc.php');

$uuid = isset($_REQUEST['uuid']) ? $_REQUEST['uuid'] : '';
$debug = isset($_REQUEST['debug']) ? $_REQUEST['debug'] : 0;

// thumbs-dir/url
global $picurl;
$thumbdir = OcConfig::getPicUploadFolder(true) . '/thumbs';
$thumburl = $picurl . '/thumbs';


if ($error == false) {

    $rs = XDb::xSql(
        "SELECT `local`, `spoiler`, `url`, `thumb_last_generated`, `last_modified`, `unknown_format`, `uuid`, `thumb_url`
        FROM `pictures` WHERE `uuid`= ? LIMIT 1", $uuid);

    if ( $r = XDb::xFetchArray($rs) ) {

        if ($r['local'] == 0)
            if ($debug == 1)
                die('Debug: line ' . __LINE__);
            else
                tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::EXTERN));

        if ( ($r['spoiler'] == 1) &&
            (!isset($_REQUEST['showspoiler']) || $_REQUEST['showspoiler'] != '1') ){
            if ($debug == 1)
                die('Debug: line ' . __LINE__);
            else
                tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::SPOILER));
        }

        $imgurl = $r['url'];
        $urlparts = mb_split('/', $imgurl);
        if (!file_exists(OcConfig::getPicUploadFolder(true) . '/' . $urlparts[count($urlparts) - 1]))
            if ($debug == 1)
                die('Debug: line ' . __LINE__);
            else
                tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::ERROR_INTERN));

        // create new thumb?
        $bGenerate = false;
        if (strtotime($r['thumb_last_generated']) < strtotime($r['last_modified']))
            $bGenerate = true;

        if (!file_exists($thumbdir . '/' . mb_substr($urlparts[count($urlparts) - 1], 0, 1) . '/' . mb_substr($urlparts[count($urlparts) - 1], 1, 1) . '/' . $urlparts[count($urlparts) - 1]))
            $bGenerate = true;

        if ($bGenerate) {
            // create picture

            if ($r['unknown_format'] == 1)
                if ($debug == 1)
                    die('Debug: line ' . __LINE__);
                else
                    tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::ERROR_FORMAT));

            // ok, let's see if the file format is supported
            $filename = $urlparts[count($urlparts) - 1];
            $filenameparts = mb_split('\\.', $filename);
            $extension = mb_strtolower($filenameparts[count($filenameparts) - 1]);

            if (mb_strpos($config['limits']['image']['extension'], ';' . $extension . ';') === false) {
                XDb::xSql(
                    "UPDATE `pictures` SET `unknown_format`=1 WHERE `uuid`= ? LIMIT 1", $r['uuid']);

                if ($debug == 1)
                    die('Debug: line ' . __LINE__);
                else
                    tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::ERROR_FORMAT));
            }

            if ($extension == 'jpeg') {
                $extension = 'jpg';
            }
            try {
                switch ($extension) {
                    case 'jpg':
                        $im = imagecreatefromjpeg(OcConfig::getPicUploadFolder(true) . '/' . $filename);
                        break;

                    case 'gif':
                        $im = imagecreatefromgif(OcConfig::getPicUploadFolder(true) . '/' . $filename);
                        break;

                    case 'png':
                        $im = imagecreatefrompng(OcConfig::getPicUploadFolder(true) . '/' . $filename);
                        break;

                    case 'bmp':
                        require(__DIR__.'/lib/imagebmp.inc.php'); //TO REMOVE!
                        $im = imagecreatefrombmp(OcConfig::getPicUploadFolder(true) . '/' . $filename);
                        break;
                }
            } catch (\Exception $e) {
                // invalid file format
                $im = '';
            }

            if ($im == '') {
                XDb::xSql(
                    "UPDATE `pictures` SET `unknown_format`=1 WHERE `uuid`= ? LIMIT 1", $r['uuid']);

                if ($debug == 1)
                    die('Debug: line ' . __LINE__);
                else
                    tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::ERROR_FORMAT));
            }

            $imheight = imagesy($im);
            $imwidth = imagesx($im);

            list($thumb_max_width, $thumb_max_height) = OcConfig::getPicMediumThumbnailSize();

            if (($imheight > $thumb_max_height) || ($imwidth > $thumb_max_width)) {
                if ($imheight > $imwidth) {
                    $thumbheight = $thumb_max_height;
                    $thumbwidth = $imwidth * ($thumbheight / $imheight);
                } else {
                    $thumbwidth = $thumb_max_width;
                    $thumbheight = $imheight * ($thumbwidth / $imwidth);
                }
            } else {
                $thumbwidth = $imwidth;
                $thumbheight = $imheight;
            }

            // create and save thumb
            $thumbimage = imagecreatetruecolor($thumbwidth, $thumbheight);
            imagecopyresampled($thumbimage, $im, 0, 0, 0, 0, $thumbwidth, $thumbheight, $imwidth, $imheight);

            // create directory
            if (!file_exists($thumbdir . '/' . mb_substr($filename, 0, 1)))
                mkdir($thumbdir . '/' . mb_substr($filename, 0, 1));
            if (!file_exists($thumbdir . '/' . mb_substr($filename, 0, 1) . '/' . mb_substr($filename, 1, 1)))
                mkdir($thumbdir . '/' . mb_substr($filename, 0, 1) . '/' . mb_substr($filename, 1, 1));

            $savedir = $thumbdir . '/' . mb_substr($filename, 0, 1) . '/' . mb_substr($filename, 1, 1);

            switch ($extension) {
                case 'jpg':
                    imagejpeg($thumbimage, $savedir . '/' . $filename);
                    break;

                case 'gif':
                    imagegif($thumbimage, $savedir . '/' . $filename);
                    break;

                case 'png':
                    imagepng($thumbimage, $savedir . '/' . $filename);
                    break;

                case 'bmp':
                    imagebmp($thumbimage, $savedir . '/' . $filename);
                    break;
            }

            XDb::xSql(
                "UPDATE `pictures` SET `thumb_last_generated`=NOW(), `thumb_url`= ?
                WHERE `uuid`= ? LIMIT 1",
                $thumburl . '/' . mb_substr($filename, 0, 1) . '/' . mb_substr($filename, 1, 1) . '/' . $filename, $r['uuid']);

            if ($debug == 1)
                die($thumburl . '/' . $filename);
            else
                tpl_redirect_absolute($thumburl . '/' . mb_substr($filename, 0, 1) . '/' . mb_substr($filename, 1, 1) . '/' . $filename);
        }
        else {
            if ($debug == 1)
                die($thumburl . '/' . $filename);
            else
                tpl_redirect_absolute($r['thumb_url']);
        }
    }
    else {

        if ($debug == 1)
            die('Debug: line ' . __LINE__);
        else
            tpl_redirect_absolute(Thumbnail::placeholderUri(Thumbnail::ERROR_404));
    }
    exit;
}

tpl_BuildTemplate();
