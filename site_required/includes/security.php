<?php
// Helpers de sécurité : CSRF et injection automatique dans les formulaires

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

function csrf_init()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        try {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // fallback
            $_SESSION['_csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
}

function csrf_get_token()
{
    csrf_init();
    return (string) ($_SESSION['_csrf_token'] ?? '');
}

function csrf_meta_tag()
{
    $t = htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8');
    echo '<meta name="clinik-csrf-token" content="' . $t . '">';
}

function csrf_print_js()
{
    echo "<script>(function(){var meta=document.querySelector('meta[name=\'clinik-csrf-token\']');if(!meta)return;var token=meta.getAttribute('content');function ensure(form){if(!form.querySelector('input[name=\'_csrf\']')){var i=document.createElement('input');i.type='hidden';i.name='_csrf';i.value=token;form.appendChild(i);}}document.addEventListener('readystatechange',function(){if(document.readyState==='interactive'||document.readyState==='complete'){document.querySelectorAll('form').forEach(ensure);}});document.addEventListener('submit',function(e){ensure(e.target);});})();</script>";
}

function csrf_print_meta_and_js()
{
    csrf_meta_tag();
    csrf_print_js();
}

function csrf_validate_request()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return true;
    }
    if (!isset($_POST['_csrf'])) {
        return false;
    }
    $token = $_POST['_csrf'];
    if (!is_string($token) || $token === '') {
        return false;
    }
    csrf_init();
    return hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) $token);
}

// HTML escape helper: prefer catalog_escape() if present, fallback to htmlspecialchars
if (!function_exists('e')) {
    function e($value)
    {
        if (function_exists('catalog_escape')) {
            return catalog_escape($value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

?>
