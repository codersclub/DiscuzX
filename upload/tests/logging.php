<?php
namespace logging;

if (!defined('IN_UNITTESTING')) exit();

define("LOG_LEVEL_SILENCE", 0);
define("LOG_LEVEL_ERROR", 1);
define("LOG_LEVEL_WARNING", 2);
define("LOG_LEVEL_INFO", 3);
define("LOG_LEVEL_DEBUG", 4);

$LEVEL_MAPPING = [
    1 => 'ERROR  ',
    2 => 'WARNING',
    3 => 'INFO   ',
    4 => 'DEBUG  ',
];

$LEVEL = LOG_LEVEL_INFO;

$LOGGING_OUTPUT_FUNC = NULL;

function _log_array($level, $format, $array)
{
    global $LEVEL, $LEVEL_MAPPING, $LOGGING_OUTPUT_FUNC;
    if ($LEVEL < $level) {
        return;
    }
 
    $str = vsprintf($format, $array);
    if ($level < LOG_LEVEL_SILENCE) {
        $level = LOG_LEVEL_SILENCE;
    }
    if ($level > LOG_LEVEL_DEBUG) {
        $level = LOG_LEVEL_DEBUG;
    }
    $level_str = $LEVEL_MAPPING[$level];
    if ($LOGGING_OUTPUT_FUNC) {
        $LOGGING_OUTPUT_FUNC(date('Y-m-d H:i:s') . '  ' . $level_str . '   ' . $str);
    } else {
        echo date('Y-m-d H:i:s') . '  ' . $level_str . '   ' . $str;
    }
}

function log($level, $format, ...$args)
{
    _log_array($level, $format, $args);
}

function error($format, ...$args)
{
    _log_array(LOG_LEVEL_ERROR, $format . "\n", $args);
}

function warning($format, ...$args)
{
    _log_array(LOG_LEVEL_WARNING, $format . "\n", $args);
}

function info($format, ...$args)
{
    _log_array(LOG_LEVEL_INFO, $format . "\n", $args);
}

function debug($format, ...$args)
{
    _log_array(LOG_LEVEL_DEBUG, $format . "\n", $args);
}
