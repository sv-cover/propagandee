<?php

if (!defined('COVER_API_URL'))
    define('COVER_API_URL', 'https://www.svcover.nl/api.php');

if (!defined('COVER_COOKIE_NAME'))
    define('COVER_COOKIE_NAME', 'cover_session_id');
    
if (!defined('COVER_LOGIN_URL'))
    define('COVER_LOGIN_URL', 'https://www.svcover.nl/sessions.php?view=login');

if (!defined('COVER_LOGOUT_URL'))
    define('COVER_LOGOUT_URL', 'https://www.svcover.nl/sessions.php?view=logout');


function http_signed_request($app, $secret, $url, array $post = null, $timeout=30)
{
    $body = $post !== null ? http_build_query($post) : '';

    $checksum = sha1($body . $secret);

    $headers = "X-App: ". $app. "\r\n".
              "X-Hash: ". $checksum . "\r\n";

    $options = array(
        'http' => $post !== null
            ? array(
                'header'  => $headers."Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => $timeout,
                'method'  => 'POST',
                'content' => $body
                )
            : array(
                'header'  => $headers,
                'timeout' => $timeout,
                'method'  => 'GET'
                )
        );

    $context = stream_context_create($options);

    return file_get_contents($url, false, $context);
}

function http_get_json($url, array $data = null)
{   
    if ($data !== null)
        $url = http_inject_url($url, $data);

    $response = http_signed_request(COVER_APP, COVER_SECRET, $url);

    if (!$response)
        throw new Exception('No response');

    $data = json_decode($response);

    if ($data === null)
        throw new Exception('Response could not be parsed as JSON: <pre>' . htmlentities($response) . '</pre>');

    return $data;
}

function get_cover_session()
{
    static $session = null;

    // Is there a cover website global session id available?
    if (!empty($_COOKIE[COVER_COOKIE_NAME]))
        $session_id = $_COOKIE[COVER_COOKIE_NAME];

    // If not, bail out. I have no place else to look :(
    else
        return null;

    if ($session !== null)
        return $session;

    $data = array(
        'method' => 'session_get_member',
        'session_id' => $session_id
        );

    $response = http_get_json(COVER_API_URL, $data);

    return $session = !empty($response->result)
        ? $response->result
        : false;
}

function cover_session_logged_in()
{
    return get_cover_session() !== false;
}

function cover_session_url($url, $next_url=null, $next_field='referrer'){
    if ($next_url === null)
        $next_url = SERVER_NAME.$_SERVER['REQUEST_URI'];
    return http_inject_url($url, array($next_field => $next_url));
}

function cover_login_url($next_url=null){
    return cover_session_url(COVER_LOGIN_URL, $next_url);
}


function cover_logout_url($next_url=null){
    return cover_session_url(COVER_LOGOUT_URL, $next_url);
}

function cover_session_status()
{
    $session = get_cover_session();

    if (!$session)
        $content = sprintf('<a class="button" href="%s">Log in</a>', cover_login_url());
    else
        $content = sprintf('Logged in as %s. <a class="button" href="%s">Log out</a>', 
            $session->voornaam, 
            cover_logout_url()
            );

    return sprintf('<div class="session">%s</div>', $content);
}

function cover_session_get_committees()
{
    static $committees;

    if ($committees !== null)
        return $committees;
    
    $session = get_cover_session();

    if (!$session)
        return array();

    $data = array(
        'method' => 'get_committees',
        'member_id' => $session->id
        );

    $response = http_get_json(COVER_API_URL, $data);

    return $committees = array_keys((array) $response->result);
}

function cover_session_in_committee($committee)
{
    return in_array(strtolower($committee), cover_session_get_committees());
}
