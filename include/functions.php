<?php

/** Converts committee login into emailaddress */
function get_committee_email($login){
    return sprintf('%s@svcover.nl', $login);
}

/** Escapes string for HTML */
function escape($data, $nl2br=true){
    if ($nl2br)
        return nl2br(htmlentities($data, ENT_COMPAT, 'utf-8'));
    return htmlentities($data, ENT_COMPAT, 'utf-8');
}

/** 
 * Encodes relative path to absolute location on disk 
 * (borrowed from Documents & Templates) 
 */
function fsencode_path($path, $root=ARCHIVE_ROOT){
    $parts = array_filter(explode('/', $path));

    array_unshift($parts, $root);

    return implode(DIRECTORY_SEPARATOR, $parts);
}


/** 
 * Convert absolute path to urlencoded relative path string
 * (borrowed from Documents & Templates) 
 */
function urlencode_path($path) {
    $path = preg_replace('{^' . preg_quote( ARCHIVE_ROOT ) . '}', '', $path);

    $parts = explode(DIRECTORY_SEPARATOR, $path);

    $parts = array_map('rawurlencode', $parts);

    return implode('/', $parts);
}


/** 
 * Returns mimietype of file
 * (borrowed from Documents & Templates) 
 */
function get_mime_type($file) {
    if (function_exists("finfo_file")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }
    
    else if (function_exists("mime_content_type"))
        return mime_content_type($file);
    
    else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
        // http://stackoverflow.com/a/134930/1593459
        $file = escapeshellarg($file);
        $mime = shell_exec('file -bi ' . escapeshellarg($file));
        return $mime;
    }
    
    else
        return null;
}

