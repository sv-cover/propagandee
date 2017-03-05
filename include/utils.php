<?php

function get_committee_email($login){
    return sprintf('%s@svcover.nl', $login);
}

function escape($data){
    return nl2br(htmlentities($data, ENT_COMPAT, 'utf-8'));
}

/** Borrowed from Documents & Templates */
function fsencode_path($path, $root = ARCHIVE_ROOT){
    $parts = array_filter(explode('/', $path));

    array_unshift($parts, $root);

    return implode(DIRECTORY_SEPARATOR, $parts);
}


/** Borrowed from Documents & Templates */
function urlencode_path($path) {
    $path = preg_replace('{^' . preg_quote( ARCHIVE_ROOT ) . '}', '', $path);

    $parts = explode(DIRECTORY_SEPARATOR, $path);

    $parts = array_map('rawurlencode', $parts);

    return implode('/', $parts);
}

function http_build_url($parts)
{ 
    return implode("", array(
        isset($parts['scheme']) ? $parts['scheme'] . '://' : '',
        isset($parts['user']) ? $parts['user'] : '',
        isset($parts['pass']) ? ':' . $parts['pass']  : '',
        (isset($parts['user']) || isset($parts['pass'])) ? "@" : '',
        isset($parts['host']) ? $parts['host'] : '',
        isset($parts['port']) ? ':' . intval($parts['port']) : '',
        isset($parts['path']) ? $parts['path'] : '',
        isset($parts['query']) ? '?' . $parts['query'] : '',
        isset($parts['fragment']) ? '#' . $parts['fragment'] : ''
    ));
}

function http_inject_url($url, array $data){
    // Parse the url
    $url_parts = parse_url($url);

    // Explicitly parse the query part as well
    if (isset($url_parts['query']))
        parse_str($url_parts['query'], $url_query);
    else
        $url_query = array();

    // Splice in the token authentication
    $url_query = array_merge($data, $url_query);

    // Rebuild the url
    $url_parts['query'] = http_build_query($url_query);
    return http_build_url($url_parts);
}


/** Borrowed from Documents & Templates */
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


/** Partially borrowed from Documents & Templates */
function serve_file($file){
    $content_type = get_mime_type($file) or null;
    if ($content_type === null){
        $name = pathinfo($file, PATHINFO_FILENAME);
        header('Content-Disposition: attachment; filename="' . $name . '"');
    }

    $fout = fopen($file, 'rb');
    fseek($fout, 0, SEEK_END);
    $file_size = ftell($fout);
    rewind($fout);
    serve_stream($fout, $content_type, $file_size);
    fclose($fout);
}


/** Borrowed from cover website */
function serve_stream($fout, $type = null, $length = null) {
    // Send proper headers: cache control & mime type
    header('Pragma: public');
    header('Cache-Control: max-age=86400');
    header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

    if ($length > 0)
        header(sprintf('Content-Length: %d', $length));

    if ($type !== null)
        header(sprintf('Content-Type: %s', $type));
    
    fpassthru($fout);
}

