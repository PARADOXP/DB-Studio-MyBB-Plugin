<?php
/**
 * DB Studio - top-level Admin CP module metadata.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

function dbstudio_meta()
{
    global $page, $plugins;

    $sub_menu = array();
    $sub_menu = $plugins->run_hooks('admin_dbstudio_menu', $sub_menu);

    $page->add_menu_item('DB Studio', 'dbstudio', 'index.php?module=dbstudio', 60, $sub_menu);

    return true;
}

function dbstudio_action_handler($action)
{
    global $page;

    $page->active_module = 'dbstudio';
    $page->active_action = 'dbstudio';

    return 'dbstudio.php';
}

function dbstudio_admin_permissions()
{
    return array(
        'name' => 'DB Studio',
        'permissions' => array(
            'dbstudio' => 'Can manage the database with DB Studio'
        ),
        'disporder' => 60
    );
}
