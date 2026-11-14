<?php
/**
 * DB Studio - Admin CP controller.
 *
 * This file is loaded from the admin_load hook. It validates the Admin CP
 * permission, the plugin settings and the database manager session before
 * DB Studio is allowed to run.
 */

if(!defined('IN_MYBB') || !defined('IN_ADMINCP'))
{
    die('Direct initialization of this file is not allowed.');
}

if(!defined('DBSTUDIO_ROOT'))
{
    define('DBSTUDIO_ROOT', MYBB_ROOT.'inc/plugins/DBStudio/');
}

require_once DBSTUDIO_ROOT.'Functions/admin_functions.php';
require_once DBSTUDIO_ROOT.'Functions/session_functions.php';
require_once DBSTUDIO_ROOT.'Functions/display_functions.php';

global $mybb, $db, $page;

check_admin_permissions(array('module' => 'dbstudio', 'action' => 'dbstudio'));

if(!dbstudio_is_enabled())
{
    dbstudio_log('denied_disabled');
    dbstudio_render_gate('The database manager is disabled by the administrator.', false);
}

if(!dbstudio_admin_uid_allowed())
{
    dbstudio_log('denied_user');
    dbstudio_render_gate('Your administrator account is not allowed to use the database manager.', false);
}

if(!dbstudio_ip_allowed())
{
    dbstudio_log('denied_ip');
    dbstudio_render_gate('Your IP address is not allowed to use the database manager.', false);
}

$session = dbstudio_session_check();

if($session === 'expired')
{
    dbstudio_render_gate('Your database manager session expired. Open the database manager again to start a new session.');
}

if($session === false)
{
    if($mybb->request_method === 'post' && $mybb->get_input('dbstudio_start'))
    {
        if(verify_post_check($mybb->get_input('my_post_key'), true))
        {
            dbstudio_session_create();
            admin_redirect('index.php?module=dbstudio');
        }

        dbstudio_render_gate('The security token expired. Please try again.');
    }

    dbstudio_render_gate('The database manager is protected by your Admin CP account and permission. Open a new session to continue.');
}

if(isset($_GET['dbstudio_theme']))
{
    $theme = strtolower((string)$_GET['dbstudio_theme']);
    if(in_array($theme, array('auto', 'light', 'dark'), true))
    {
        my_setcookie('dbstudio_theme', $theme, 31536000, true, 'lax');
    }
}

if(isset($_GET['dbstudio_theme_css']))
{
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: public, max-age=31536000, immutable');

    $theme = isset($_GET['theme']) ? strtolower((string)$_GET['theme']) : dbstudio_current_theme();
    if(!in_array($theme, array('auto', 'light', 'dark'), true))
    {
        $theme = 'auto';
    }

    $css = (string)@file_get_contents(DBSTUDIO_ROOT.'Assets/dbstudio_theme.css');
    $dark = (string)@file_get_contents(DBSTUDIO_ROOT.'Assets/dbstudio_theme_dark.css');
    $light = (string)@file_get_contents(DBSTUDIO_ROOT.'Assets/dbstudio_theme_light.css');

    if($theme === 'dark')
    {
        $css .= $dark;
    }
    elseif($theme === 'light')
    {
        $css .= $light;
    }
    elseif($theme === 'auto')
    {
        $css .= "@media (prefers-color-scheme: dark) {\n".$dark."\n}";
        $css .= "@media (prefers-color-scheme: light) {\n".$light."\n}";
    }

    echo $css;
    exit;
}

if(!dbstudio_is_frame_request())
{
    dbstudio_render_frame();
}

if((int)dbstudio_setting('auto_login', 1) === 1 && empty($_POST['auth']) && empty($_POST['logout']) && !isset($_GET['username']) && !isset($_GET['file']) && !isset($_GET['manifest']))
{
    $auth = dbstudio_config_credentials();
    if($auth !== null)
    {
        $_POST['auth'] = $auth;
        $GLOBALS['dbstudio_autologin'] = true;
    }
}

if(isset($_POST['logout']))
{
    dbstudio_log('logout');
}

if(!function_exists('dbstudio_object'))
{
    function dbstudio_object()
    {
        return new class extends \Adminer\Adminer
        {
            public function name()
            {
                $url = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : 'index.php?module=dbstudio&dbstudio_frame=1';

                return "<a href='".htmlspecialchars($url, ENT_QUOTES, 'UTF-8')."' id='h1'><span class='dbstudio-brand-mark' aria-hidden='true'></span>DB Studio</a>";
            }

            public function verifyLoginToken()
            {
                if(!empty($GLOBALS['dbstudio_autologin']))
                {
                    return false;
                }

                return parent::verifyLoginToken();
            }

            public function headers()
            {
                header('X-Frame-Options: SAMEORIGIN');
                parent::headers();
            }

            public function csp(array $csp)
            {
                foreach($csp as &$policy)
                {
                    $policy['frame-ancestors'] = "'self'";
                }

                return $csp;
            }

            public function css()
            {
                $theme = dbstudio_current_theme();
                $scheme = ($theme === 'light' || $theme === 'dark') ? $theme : '';

                return array('index.php?module=dbstudio&dbstudio_frame=1&dbstudio_theme_css=1&theme='.$theme.'&v=8' => $scheme);
            }

        };
    }
}

define('MYBB_DBSTUDIO_SERVE', 1);
require_once DBSTUDIO_ROOT.'lib/dbstudio.php';
exit;
