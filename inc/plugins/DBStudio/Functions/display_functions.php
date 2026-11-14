<?php
/**
 * DB Studio - Admin CP page rendering.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

function dbstudio_render_gate($message, $allow_start = true)
{
    global $templates, $page, $mybb;

    if($allow_start)
    {
        $template_name = 'dbstudio_gate';
    }
    else
    {
        $template_name = 'dbstudio_denied';
    }

    $template = $templates->get($template_name);
    $gate_message = htmlspecialchars_uni($message);
    $gate_url = 'index.php?module=dbstudio';
    $gate_post_code = $mybb->post_code;

    $page->add_breadcrumb_item('DB Studio', 'index.php?module=dbstudio');
    $page->output_header('DB Studio');

    if($template)
    {
        eval('$gate = "'.$template.'";');
        echo $gate;
    }
    else
    {
        $page->output_error($gate_message);
    }

    $page->output_footer();
    exit;
}

function dbstudio_render_frame()
{
    global $templates, $page;

    $template = $templates->get('dbstudio_frame');
    $frame_url = 'index.php?module=dbstudio&amp;dbstudio_frame=1';

    $current_theme = dbstudio_current_theme();
    $theme_urls = array(
        'auto'  => 'index.php?module=dbstudio&amp;dbstudio_theme=auto',
        'light' => 'index.php?module=dbstudio&amp;dbstudio_theme=light',
        'dark'  => 'index.php?module=dbstudio&amp;dbstudio_theme=dark'
    );

    $dbstudio_theme_auto_url = $theme_urls['auto'];
    $dbstudio_theme_light_url = $theme_urls['light'];
    $dbstudio_theme_dark_url = $theme_urls['dark'];
    $dbstudio_theme_auto_state = $current_theme === 'auto' ? ' class="active" aria-current="true"' : '';
    $dbstudio_theme_light_state = $current_theme === 'light' ? ' class="active" aria-current="true"' : '';
    $dbstudio_theme_dark_state = $current_theme === 'dark' ? ' class="active" aria-current="true"' : '';

    $page->add_breadcrumb_item('DB Studio', 'index.php?module=dbstudio');
    $page->output_header('DB Studio');

    if($template)
    {
        eval('$frame = "'.$template.'";');
        echo $frame;
    }
    else
    {
        $page->output_error('The DB Studio frame template is missing.');
    }

    $page->output_footer();
    exit;
}
