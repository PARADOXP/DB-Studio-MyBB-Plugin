<?php
/**
 * DB Studio - Admin CP integration.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

if(!defined('DBSTUDIO_ROOT'))
{
    define('DBSTUDIO_ROOT', MYBB_ROOT.'inc/plugins/DBStudio/');
}

function dbstudio_admin_tools_action_handler(&$actions)
{
    $actions['dbstudio'] = array(
        'active' => 'dbstudio',
        'file'   => 'dbstudio.php'
    );
}

function dbstudio_admin_load()
{
    global $page;

    if(!is_object($page))
    {
        return;
    }

    $is_top_level = isset($page->active_module, $page->active_action) && $page->active_module === 'dbstudio' && $page->active_action === 'dbstudio';
    $is_legacy_tools = isset($page->active_module, $page->active_action) && $page->active_module === 'tools' && $page->active_action === 'dbstudio';

    if($is_top_level || $is_legacy_tools)
    {
        require_once DBSTUDIO_ROOT.'Admin/dbstudio.php';
        exit;
    }
}

function dbstudio_is_frame_request()
{
    return isset($_GET['dbstudio_frame']) && (string)$_GET['dbstudio_frame'] === '1';
}

function dbstudio_current_theme()
{
    global $mybb;

    $cookie = isset($mybb->cookies['dbstudio_theme']) ? (string)$mybb->cookies['dbstudio_theme'] : '';
    if(in_array($cookie, array('auto', 'light', 'dark'), true))
    {
        return $cookie;
    }

    $setting = (string)dbstudio_setting('theme', 'auto');
    if(in_array($setting, array('auto', 'light', 'dark'), true))
    {
        return $setting;
    }

    return 'auto';
}

function dbstudio_setting($name, $default = '')
{
    global $mybb;

    $key = 'dbstudio_'.$name;
    if(isset($mybb->settings[$key]) && $mybb->settings[$key] !== '')
    {
        return $mybb->settings[$key];
    }

    return $default;
}

function dbstudio_is_enabled()
{
    return (int)dbstudio_setting('enabled', 1) === 1;
}

function dbstudio_admin_uid_allowed()
{
    global $mybb;

    $uid = (int)$mybb->user['uid'];
    if(is_super_admin($uid))
    {
        return true;
    }

    $allowed = trim((string)dbstudio_setting('allowed_admins', ''));
    if($allowed === '')
    {
        return true;
    }

    $uids = preg_split('/[\s,]+/', $allowed);
    foreach($uids as $allowed_uid)
    {
        if((int)$allowed_uid === $uid)
        {
            return true;
        }
    }

    return false;
}

/**
 * Builds the DB Studio login array from the MyBB database configuration.
 *
 * @return array|null
 */
function dbstudio_config_credentials()
{
    global $config;

    if(empty($config['database']) || !is_array($config['database']))
    {
        return null;
    }

    $database = $config['database'];
    $type = isset($database['type']) ? strtolower($database['type']) : '';

    if($type === 'mysql' || $type === 'mysqli')
    {
        $driver = 'server';
    }
    elseif($type === 'pgsql')
    {
        $driver = 'pgsql';
    }
    elseif($type === 'sqlite')
    {
        $driver = 'sqlite';
    }
    else
    {
        return null;
    }

    if($driver === 'sqlite')
    {
        return array(
            'driver'   => $driver,
            'server'   => (string)$database['database'],
            'username' => '',
            'password' => '',
            'db'       => '',
            'permanent' => 0
        );
    }

    return array(
        'driver'    => $driver,
        'server'    => (string)$database['hostname'],
        'username'  => (string)$database['username'],
        'password'  => (string)$database['password'],
        'db'        => (string)$database['database'],
        'permanent' => 0
    );
}
