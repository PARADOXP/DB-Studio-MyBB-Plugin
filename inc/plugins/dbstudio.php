<?php
/**
 * DB Studio
 *
 * Adds a permission-protected DB Studio interface to the MyBB Admin CP.
 *
 * @package DB Studio
 * @version 1.0.0
 * @author PARAD0X
 * @website https://mybb.epizy.com
 * @codename dbstudio
 */

if(!defined('IN_MYBB'))
{
    die('This file cannot be accessed directly.');
}

if(!defined('PLUGINLIBRARY'))
{
    define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');
}

if(!defined('DBSTUDIO_ROOT'))
{
    define('DBSTUDIO_ROOT', MYBB_ROOT.'inc/plugins/DBStudio/');
}

require_once DBSTUDIO_ROOT.'install.php';

if(defined('IN_ADMINCP'))
{
    require_once DBSTUDIO_ROOT.'Functions/admin_functions.php';
    require_once DBSTUDIO_ROOT.'Functions/session_functions.php';
    require_once DBSTUDIO_ROOT.'Functions/display_functions.php';

    $plugins->add_hook('admin_tools_action_handler', 'dbstudio_admin_tools_action_handler');
    $plugins->add_hook('admin_load', 'dbstudio_admin_load');
}

function dbstudio_info()
{
    return array(
        'name'          => 'DB Studio',
        'description'   => 'Adds a permission-protected database studio to the Admin CP. Access is controlled from the administrator permissions screen.',
        'website'       => 'https://mybb.epizy.com',
        'author'        => 'PARAD0X',
        'authorsite'    => 'https://community.mybb.com/user-134311.html',
        'version'       => '1.0.0',
        'guid'          => '',
        'compatibility' => '18*',
        'codename'      => 'dbstudio'
    );
}

function dbstudio_is_installed()
{
    global $db;

    return $db->table_exists('dbstudio_sessions') && $db->table_exists('dbstudio_log');
}

function dbstudio_install()
{
    dbstudio_install_run();
}

function dbstudio_activate()
{
    dbstudio_activate_run();
}

function dbstudio_deactivate()
{
    dbstudio_deactivate_run();
}

function dbstudio_uninstall()
{
    dbstudio_uninstall_run();
}
