<?php
/**
 * DB Studio - session, IP and audit helpers.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

function dbstudio_client_ip()
{
    $ip = get_ip();
    if(!is_string($ip) || $ip === '')
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    return substr($ip, 0, 45);
}

function dbstudio_ip_allowed()
{
    $setting = trim((string)dbstudio_setting('allowed_ips', ''));
    if($setting === '')
    {
        return true;
    }

    $ip = dbstudio_client_ip();
    $lines = preg_split('/\r\n|\r|\n/', $setting);

    foreach($lines as $line)
    {
        $line = trim($line);
        if($line === '' || $line[0] === '#')
        {
            continue;
        }

        if(dbstudio_ip_in_range($ip, $line))
        {
            return true;
        }
    }

    return false;
}

function dbstudio_ip_in_range($ip, $range)
{
    if(strpos($range, '/') === false)
    {
        return $ip === $range;
    }

    list($subnet, $bits) = explode('/', $range, 2);
    $bits = (int)$bits;

    $ip_binary = @inet_pton($ip);
    $subnet_binary = @inet_pton($subnet);

    if($ip_binary === false || $subnet_binary === false || strlen($ip_binary) !== strlen($subnet_binary))
    {
        return false;
    }

    $max_bits = strlen($ip_binary) * 8;
    if($bits < 0 || $bits > $max_bits)
    {
        return false;
    }

    $whole_bytes = intdiv($bits, 8);
    $remaining_bits = $bits % 8;

    if($whole_bytes > 0 && substr($ip_binary, 0, $whole_bytes) !== substr($subnet_binary, 0, $whole_bytes))
    {
        return false;
    }

    if($remaining_bits > 0)
    {
        $mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        if((ord($ip_binary[$whole_bytes]) & $mask) !== (ord($subnet_binary[$whole_bytes]) & $mask))
        {
            return false;
        }
    }

    return true;
}

/**
 * Checks the database manager session cookie.
 *
 * @return array|string|false Session row, "expired", or false when no valid session exists.
 */
function dbstudio_session_check()
{
    global $db, $mybb;

    $token = isset($mybb->cookies['dbstudio_sid']) ? (string)$mybb->cookies['dbstudio_sid'] : '';
    if(!preg_match('/^[a-f0-9]{64}$/', $token))
    {
        return false;
    }

    $sid = hash('sha256', $token);
    $query = $db->simple_select('dbstudio_sessions', '*', "sid='".$db->escape_string($sid)."'", array('limit' => 1));
    $session = $db->fetch_array($query);

    if(!$session)
    {
        return false;
    }

    if((int)$session['uid'] !== (int)$mybb->user['uid'])
    {
        dbstudio_session_destroy();
        return false;
    }

    if((int)dbstudio_setting('bind_ip', 1) === 1 && $session['ip'] !== dbstudio_client_ip())
    {
        dbstudio_log('denied_ip');
        dbstudio_session_destroy();
        return false;
    }

    $timeout = (int)dbstudio_setting('idle_timeout', 15);
    if($timeout > 0 && (int)$session['lastactive'] < TIME_NOW - ($timeout * 60))
    {
        dbstudio_log('expired');
        dbstudio_session_destroy();
        return 'expired';
    }

    $db->update_query('dbstudio_sessions', array('lastactive' => TIME_NOW), "sid='".$db->escape_string($sid)."'");
    $session['lastactive'] = TIME_NOW;

    if(mt_rand(1, 50) === 1)
    {
        dbstudio_prune();
    }

    return $session;
}

function dbstudio_session_create()
{
    global $db, $mybb;

    $token = bin2hex(random_bytes(32));
    $sid = hash('sha256', $token);
    $uid = (int)$mybb->user['uid'];

    if((int)dbstudio_setting('single_session', 1) === 1)
    {
        $db->delete_query('dbstudio_sessions', "uid='{$uid}'");
    }

    $db->insert_query('dbstudio_sessions', array(
        'sid'        => $db->escape_string($sid),
        'uid'        => $uid,
        'ip'         => $db->escape_string(dbstudio_client_ip()),
        'autologin'  => 0,
        'created'    => TIME_NOW,
        'lastactive' => TIME_NOW
    ));

    my_setcookie('dbstudio_sid', $token, -1, true, 'lax');
    dbstudio_log('start');

    return $sid;
}

function dbstudio_session_destroy()
{
    global $db, $mybb;

    $token = isset($mybb->cookies['dbstudio_sid']) ? (string)$mybb->cookies['dbstudio_sid'] : '';
    if(preg_match('/^[a-f0-9]{64}$/', $token))
    {
        $sid = hash('sha256', $token);
        $db->delete_query('dbstudio_sessions', "sid='".$db->escape_string($sid)."'");
    }

    my_setcookie('dbstudio_sid', '', -3600, true, 'lax');
}

function dbstudio_log($action)
{
    global $db, $mybb;

    if((int)dbstudio_setting('audit_log', 1) !== 1)
    {
        return;
    }

    $db->insert_query('dbstudio_log', array(
        'uid'      => (int)$mybb->user['uid'],
        'action'   => $db->escape_string(substr((string)$action, 0, 32)),
        'ip'       => $db->escape_string(dbstudio_client_ip()),
        'dateline' => TIME_NOW
    ));
}

function dbstudio_prune()
{
    global $db;

    $timeout = (int)dbstudio_setting('idle_timeout', 15);
    if($timeout > 0)
    {
        $stale_before = TIME_NOW - max($timeout * 120, 3600);
        $db->delete_query('dbstudio_sessions', "lastactive < '{$stale_before}'");
    }
    else
    {
        $stale_before = TIME_NOW - 86400;
        $db->delete_query('dbstudio_sessions', "lastactive < '{$stale_before}'");
    }

    $retention = (int)dbstudio_setting('log_retention', 90);
    if($retention > 0)
    {
        $log_before = TIME_NOW - ($retention * 86400);
        $db->delete_query('dbstudio_log', "dateline < '{$log_before}'");
    }
}
