<?php

function fsencode_path($path, $root = ARCHIVE_ROOT){
    $parts = array_filter(explode('/', $path));

    array_unshift($parts, $root);

    return implode(DIRECTORY_SEPARATOR, $parts);
}

function urlencode_path($path){
    $path = preg_replace('{^' . preg_quote( ARCHIVE_ROOT ) . '}', '', $path);

    $parts = explode(DIRECTORY_SEPARATOR, $path);

    $parts = array_map('rawurlencode', $parts);

    return implode('/', $parts);
}
