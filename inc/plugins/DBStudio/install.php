<?php
/**
 * DB Studio - installation routines.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

if(!defined('PLUGINLIBRARY'))
{
    define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');
}

if(!defined('DBSTUDIO_ROOT'))
{
    define('DBSTUDIO_ROOT', MYBB_ROOT.'inc/plugins/DBStudio/');
}

function dbstudio_install_run()
{
    dbstudio_create_tables();
    dbstudio_add_settings();
    dbstudio_add_templates();
    dbstudio_add_stylesheet();
    dbstudio_install_admin_module();
    dbstudio_install_admin_stylesheet();
}

function dbstudio_activate_run()
{
    dbstudio_create_tables();
    dbstudio_add_settings();
    dbstudio_remove_templates();
    dbstudio_add_templates();
    dbstudio_add_stylesheet();
    dbstudio_install_admin_module();
    dbstudio_install_admin_stylesheet();

    if(function_exists('rebuild_settings'))
    {
        rebuild_settings();
    }
}

function dbstudio_deactivate_run()
{
    dbstudio_remove_stylesheet();
    dbstudio_remove_admin_module();
    dbstudio_remove_admin_stylesheet();
}

function dbstudio_uninstall_run()
{
    global $db;

    dbstudio_remove_stylesheet();
    dbstudio_remove_templates();
    dbstudio_remove_settings();
    dbstudio_remove_admin_module();
    dbstudio_remove_admin_stylesheet();

    $db->drop_table('dbstudio_sessions');
    $db->drop_table('dbstudio_log');
}

function dbstudio_create_tables()
{
    global $db;

    $schema_file = DBSTUDIO_ROOT.'database.sql';
    if(!file_exists($schema_file))
    {
        return;
    }

    $schema = file_get_contents($schema_file);
    if($schema === false)
    {
        return;
    }

    $schema = str_replace('{PREFIX}', TABLE_PREFIX, $schema);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema);

    foreach($statements as $statement)
    {
        $statement = trim($statement);
        if($statement !== '')
        {
            $db->write_query($statement);
        }
    }
}

function dbstudio_add_settings()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();

    $settings_file = DBSTUDIO_ROOT.'settings.json';
    if(!file_exists($settings_file))
    {
        return;
    }

    $settings_data = @json_decode(@file_get_contents($settings_file), true);
    if(!$settings_data || !isset($settings_data['group'], $settings_data['settings']))
    {
        return;
    }

    $named_settings = array();
    foreach($settings_data['settings'] as $setting)
    {
        if(!isset($setting['name']))
        {
            continue;
        }

        $name = str_replace($settings_data['group']['name'].'_', '', $setting['name']);
        $named_settings[$name] = $setting;
    }

    $PL->settings(
        $settings_data['group']['name'],
        $settings_data['group']['title'],
        $settings_data['group']['description'],
        $named_settings
    );
}

function dbstudio_remove_settings()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();
    $PL->settings_delete('dbstudio', true);
}

function dbstudio_add_templates()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();

    $template_files = glob(DBSTUDIO_ROOT.'Templates/dbstudio_*.html');
    if(!$template_files)
    {
        return;
    }

    $templates = array();
    foreach($template_files as $file)
    {
        $filename = basename($file, '.html');
        $content = @file_get_contents($file);
        if($content !== false)
        {
            $key = str_replace('dbstudio_', '', $filename);
            $templates[$key] = $content;
        }
    }

    if(!empty($templates))
    {
        $PL->templates('dbstudio', 'DB Studio', $templates);
    }
}

function dbstudio_remove_templates()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();
    $PL->templates_delete('dbstudio', true);
}

function dbstudio_add_stylesheet()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();

    $css_file = DBSTUDIO_ROOT.'Assets/dbstudio.css';
    $css = @file_get_contents($css_file);
    if($css === false)
    {
        return;
    }

    $PL->stylesheet('dbstudio.css', $css, '');
    $PL->cache_update('dbstudio_css', $css);
}

function dbstudio_remove_stylesheet()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;
    $PL = new PluginLibrary();
    $PL->stylesheet_delete('dbstudio.css', true);
    $PL->cache_delete('dbstudio_css');
}

function dbstudio_admin_modules_dir()
{
    global $config;

    $admin_dir = 'admin';
    if(isset($config['admin_dir']) && $config['admin_dir'] !== '')
    {
        $admin_dir = $config['admin_dir'];
    }

    return MYBB_ROOT.$admin_dir.'/modules/dbstudio/';
}

function dbstudio_install_admin_module()
{
    $source = DBSTUDIO_ROOT.'Admin/Module/';
    $destination = dbstudio_admin_modules_dir();

    if(!is_dir($destination))
    {
        @mkdir($destination, 0755, true);
    }

    $files = array(
        'module_meta.php' => 'module_meta.php',
        'module.php'      => 'dbstudio.php'
    );

    foreach($files as $source_file => $destination_file)
    {
        if(file_exists($source.$source_file))
        {
            @copy($source.$source_file, $destination.$destination_file);
        }
    }
}

function dbstudio_remove_admin_module()
{
    $destination = dbstudio_admin_modules_dir();

    foreach(array('module_meta.php', 'dbstudio.php') as $file)
    {
        if(file_exists($destination.$file))
        {
            @unlink($destination.$file);
        }
    }

    @rmdir($destination);
}

function dbstudio_admin_styles_dir()
{
    global $config;

    $admin_dir = 'admin';
    if(isset($config['admin_dir']) && $config['admin_dir'] !== '')
    {
        $admin_dir = $config['admin_dir'];
    }

    return MYBB_ROOT.$admin_dir.'/styles/';
}

function dbstudio_install_admin_stylesheet()
{
    $source = DBSTUDIO_ROOT.'Assets/dbstudio.css';
    if(!file_exists($source))
    {
        return;
    }

    $styles = glob(dbstudio_admin_styles_dir().'*/', GLOB_ONLYDIR);
    if(!$styles)
    {
        return;
    }

    foreach($styles as $style_dir)
    {
        @copy($source, $style_dir.'dbstudio.css');
    }
}

function dbstudio_remove_admin_stylesheet()
{
    $styles = glob(dbstudio_admin_styles_dir().'*/dbstudio.css');
    if(!$styles)
    {
        return;
    }

    foreach($styles as $file)
    {
        @unlink($file);
    }
}
