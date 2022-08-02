<?php
/**
 * How to use:
 *
 * 1) Place this file into the root folder of your Joomla site (where your configuration.php file exists)
 * 2) Adjust the variables section below; use a size of 0 if you DO NOT want to process a specific image size
 * 3) Execute via terminal with "php -f rebuild.php" (it overwrites existing files without notice!)
 *
 * @version  1.0
 * @author   Robert Deutz <rdeutz@googlemail.com>
 *
 * @version  1.2
 * @author   JoomlaWorks Ltd.
 * @date     June 11th, 2019
 * 
 * @version  1.5
 * @author   Androutsos Alexandros - ComputerSpot.gr. <androutsos@computerspot.gr>
 * @date     June 27th, 2022
 *
 * === CHANGELOG ===
 * v1.5:
 * - Most important differentiation. This script now both Rebuilds the Cache and the Src folder. It Does that inside 2 entirely new folders which you can inspect BEFORE Replacing the originals.
 * - Fixed a minor bug (not affecting anything in real life since default values were used) in the buildImages function that was causing jpeg_quality not to pass in the function and only default values were used.\
 * - $resultsummary typo fixed
 * - Fixed a bug where if files had the same timestamp only one of them would be processed.

 * v1.2:
 * - IMPORTANT change: the script must now be executed from your Joomla site's root folder (where your configuration.php file exists).
 * - Conversion now works from the newest to the oldest source file. So your most recent images will be converted first (makes sense to do so).
 * - Add range option so you can work in batches. Set the $from & $to variables to the range you want converted.
 * - Added progress counter next to each source file name.
 *
 * v1.1:
 * - Updated codebase to work with latest K2. The script will be maintained by JoomlaWorks from now on - thank you Robert!
 *
 * === TO DO ===
 * - Add option to resize source image
 * - Implement as Joomla content or system plugin
 *
 * (end)
 */

// New images sizes (width in pixels)
$sizeXS = 80;
$sizeS  = 160;
$sizeM  = 320;
$sizeL  = 640;
$sizeXL = 900;
$sizeG  = 260;
$jpeg_quality = 70;
$src_max_width = 900; /*Max width of the source file. Larger src images will be scaled down using this to reduce image size. Smaller ones will not be scaled. Setting this value to 0 will disable the src rebuilding function and the code will only work to rebuild the cache.*/

// Set conversion range (set to 0 to disable - default action)
$from = 0;
$to   = 0;



/**
 * DO NOT CHANGE ANYTHING AFTER THIS LINE IF YOU DON'T KNOW WHAT YOU ARE DOING!
 */

// Load class.upload.php
$uploadclassfile = '';
$oldUploadClassLocation = dirname(__FILE__).'/administrator/components/com_k2/lib/class.upload.php';
$newUploadClassLocation = dirname(__FILE__).'/media/k2/assets/vendors/verot/class.upload.php/src/class.upload.php';

if (file_exists($oldUploadClassLocation) && is_readable($oldUploadClassLocation)) {
    $uploadclassfile = $oldUploadClassLocation;
}

if (file_exists($newUploadClassLocation) && is_readable($newUploadClassLocation)) {
    $uploadclassfile = $newUploadClassLocation;
}

if (!$uploadclassfile) {
    echo "Can't find class.upload.php! Is K2 installed? Did you copy rebuild.php to the root folder of your Joomla site?";
    exit;
}

define('_JEXEC', 1);
require_once($uploadclassfile);

// Helper functions
function buildImage($sourcefile, $targetfile, $size, $jpeg_quality=70)
{
    $handle = new Upload($sourcefile);
    $savepath = dirname($targetfile);
    $handle->image_resize = true;
    $handle->image_ratio_y = true;
    $handle->image_convert = 'jpg';
    $handle->jpeg_quality = $jpeg_quality;
    $handle->file_auto_rename = false;
    $handle->file_overwrite = true;
    $handle->file_new_name_body = basename($targetfile, '.jpg');
    $handle->image_x = (int) $size;
    return $handle->Process($savepath);
}

function buildImages($sourcefile, $targetdir, $sizes, $jpeg_quality=70)
{
    $resultsummary = true;
    foreach ($sizes as $key => $value) {
        if ($value != 0) {
            $filename = basename($sourcefile, '.jpg');
            $targetfile = $targetdir.'/'.$filename.'_'.$key.'.jpg';
            if (buildImage($sourcefile, $targetfile, $value, $jpeg_quality) !== true) { /* Author forgot here to enter the $jpeg_quality. Was using default only. */
                // Successful
                $resultdetails[$key] = true;
            } else {
                // Failed
                $resultsummary = false;
                $resultdetails[$key] = false;
            }
        }
    }

    return $resultsummary ? true : $resultdetails;
}

function buildImage_src($sourcefile, $targetfile_src, $max_width=900, $jpeg_quality=70)
{
    list($width, $height, $type, $attr) = getimagesize($sourcefile);
    $handle = new Upload($sourcefile);
    $savepath = dirname($targetfile_src);
    $handle->image_resize = true;
    $handle->image_ratio_y = true;
    $handle->image_convert = 'jpg';
    $handle->jpeg_quality = $jpeg_quality;
    $handle->file_auto_rename = false;
    $handle->file_overwrite = true;
    $handle->file_new_name_body = basename($targetfile_src, '.jpg');
    if($width > $max_width ) {
        $handle->image_x = (int) $max_width;
    } else {
        $handle->image_x = (int) $width;
    }
    
    return $handle->Process($savepath);
}

function buildImages_src($sourcefile, $targetdir_src, $max_width=900, $jpeg_quality=70)
{
    $resultsummary = true;
    if ($max_width > 0) { /* Setting the value $max_width to 0 or below will disable this part of the program completely and */
        $filename = basename($sourcefile, '.jpg');
        $targetfile = $targetdir_src.'/'.$filename.'.jpg';
        if (buildImage_src($sourcefile, $targetfile, $max_width, $jpeg_quality) !== true) { /* Author forgot here to enter the $jpeg_quality. Was using default only. */
            // Successful
            $resultdetails[$key] = true;
        } else {
            // Failed
            $resultsummary = false;
            $resultdetails[$key] = false;
        }
    }

    return $resultsummary ? true : $resultdetails;
}

// Set directories and image sizes
$sourcedir = dirname(__FILE__).'/media/k2/items/src';
$targetdir = dirname(__FILE__).'/media/k2/items/cache_rebuilt'; /* instead of '/media/k2/items/cache' to avoid replacing images.*/
if (!file_exists($targetdir)) {
    mkdir($targetdir, 0755, true); /* Make sure that the target dir is there for it to get images in... */
}
$targetdir_src = dirname(__FILE__).'/media/k2/items/src_rebuilt'; /* will be used to create the new source images. */
if (!file_exists($targetdir_src)) {
    mkdir($targetdir_src, 0755, true); /* Make sure that the target dir is there for it to get images in... */
}

$sizes = array(
    'XS'      => $sizeXS,
    'S'       => $sizeS,
    'M'       => $sizeM,
    'L'       => $sizeL,
    'XL'      => $sizeXL,
    'Generic' => $sizeG
);

// Count total images
$all = count(glob($sourcedir."/*.jpg"));

// --- Convert the images ---
$filesByDateModified = array();
$count = 0;
$aa = 0; /* Trying to fix the timestamp bug here */

if ($fhandle = opendir($sourcedir)) {
    while (false !== ($entry = readdir($fhandle))) {
        $aa++;
        $file = $sourcedir.'/'.$entry;
        if (is_file($file) && $entry != "." && $entry != "..") {
            $filesByDateModified[filemtime($file)."_".$aa] = $file; /* Added a numbering at the end of each timestamp to avoid have 2 exactly equal timestamps */
        }
    }
    closedir($fhandle);

    // Reverse sort source image files by date modified (to begin converting the newest ones)
    krsort($filesByDateModified); 

    foreach ($filesByDateModified as $timestamp => $file) { /* $timestamp is a key that contains both the timestamp AND numbering to avoid duplicates */
        echo "<br>File: " . $file . ":<br>";
        $count++;
        if ($from > 0 && $count < $from) {
            continue;
        }
        if ($to > 0 && $count > $to) {
            break;
        }

        $entry = str_replace($sourcedir.'/', '', $file);
        $r = buildImages($file, $targetdir, $sizes, $jpeg_quality);
        if ($r === true) {
            echo "Source file {$count}/{$all}: ".$entry . " [OK]\n";
        } else {
            echo "Source file {$count}/{$all}: ".$entry . " [FAILED]\n";
            echo "Details:\n";
            foreach ($sizes as $key => $value) {
                $result = 'Success';
                if (array_key_exists($key, $r)) {
                    $result = 'Failed';
                }
                echo "Size $key ({$value}px): ".$result."\n";
            }
        }

        $r_src = buildImages_src($file, $targetdir_src, $src_max_width, $jpeg_quality);
        if ($r_src === true) {
            echo "Source file original {$count}/{$all}: ".$entry . " [OK]\n";
        } else {
            echo "Source file original {$count}/{$all}: ".$entry . " [FAILED]\n";
        }
    }
}
