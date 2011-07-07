<?php
// This script reads all the directories on your Kindle and creates
// a collection for each directory and stores the correspondiing 
// documents in these directories. 
//
// So you only need to create the directory structure that you want
// on your kindle, run this script to sync the collections to the 
// directoires. You no longer need to create all the collections 
// on your kindle.
//
// Usage:
// 
// 1) Connect your Kindle to your mac, making sure it's mounted as
//    /Volumnes/Kindle
//
// 2) Run this script , this will create the new json collecitons file
//    this output will need to be put on the kindle:
//
//    $ php collections.php > /Volumes/Kindle/system/collections.json
//
// 3) Disconnect the Kindle from your mac 
//
// 4) With the Kindle now on the book index turn it off and hold the
//    Kindle power slider in the off-position for 15 seconds
//
// 5) After 15 seconds release the power slider, the Kindle will reboot
//    shortly and re-initialize, loading the new JSON collections file.


error_reporting(E_ALL);

// Directory where kindle in mounted (e.g.: "/Volumes/Kindle", or "/mnt/us" or "e:\\"
define('KINDLE_DIR', "/Volumes/Kindle");

// Category that will be assigned to books in the root
define('FALLBACK_CATEGORY', 'Ongecategoriseerd');

function getFilesRecursive($dir) {
    $return = array();

    $files = scandir($dir);
    foreach($files as $file) {
        if ($file == '..' || $file == '.') continue;

        if (is_dir($dir .'/' . $file)) {
            $subFiles = getFilesRecursive($dir . '/' . $file);
            $return = array_merge($return, $subFiles);
        } else {
            $return[] = $dir . "/" . $file;
        }
    }

    return $return;
}

function getKindleFiles() {
    $return = array();
    $files = getFilesRecursive(KINDLE_DIR . '/documents');

    foreach($files as $file) {
        $extensions = "\.(pdf|azw|mobi)";

        if (preg_match("/{$extensions}$/", $file)) {
            $pad = str_replace(KINDLE_DIR, "/mnt/us", $file);
            $pad = str_replace("//", "/", $pad);
            $pad = str_replace("//", "/", $pad);
            $pad = str_replace("//", "/", $pad);
            $pad = str_replace("//", "/", $pad);

            // Strip the document root
            $category = str_replace("/mnt/us/documents/", "", substr($pad, 0, strrpos($pad, "/")));

            // Fallback to default category
            if ($category == '/mnt/us/documents') $category = FALLBACK_CATEGORY;

            // And make the sub-categories better readable
            $category = str_replace("/", " / ", $category);
    
            if (!isset($return[$category])) $return[$category] = array();

            if (preg_match('/-asin_(.*)-type_([A-Z]{4})-/', $pad, $match)) {
                // example: #B003O86F4U^EBOK, #B002RI9ZQ8^EBSP, #ThankYouLetter_A3P5ROKL5A1OLE^PSNL
                $return[$category][] = "#" . $match[1] . '^' . $match[2];
            } else {
                // example: *9e97434880f115813437c1910b6dba7398c12e52
                $return[$category][] = "*" . sha1($pad);
            }
        }
    }

    ksort($return); // sort by category
    return $return;
}


if (!is_dir(KINDLE_DIR)) { 
    echo "Kindle is not found. Are you sure it is connected?\n"; 
} else {
    $collection = '';

    $kindleFiles = getKindleFiles();
    $lastAccessTime = time() - 3600;
    foreach($kindleFiles as $category => $files) {
        $kindleCategory = $category . "@en-US";
        $collection->$kindleCategory = (object) array(
            'items' => $files, 
            'lastAccess' => $lastAccessTime--
        );
    }

    $json = json_encode($collection);

    echo $json;
}


if (false) {
    // DEBUG original file
    $jsonFile = file_get_contents(KINDLE_DIR . "/system/collections.json");
    echo $jsonFile;
    print_r(json_decode($jsonFile));
}
