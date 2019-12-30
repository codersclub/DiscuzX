<?php

if (!defined('IN_UNITTESTING')) exit();

require 'logging.php';

$FAILED = 0;
$SUCCEEDED = 0;

function starts_with($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function join_paths()
{
    return implode('/', array_map(function ($s) {
        return rtrim($s, '/');
    }, func_get_args()));
}

function all_classes_in($file) {
    $content = file_get_contents($file);
    $tokens = token_get_all($content);
    $namespace = '';
    $fqcns = array();
    for ($index = 0; isset($tokens[$index]); $index++) {
        if (!isset($tokens[$index][0])) {
            continue;
        }
        if (T_NAMESPACE === $tokens[$index][0]) {
            $index += 2; // Skip namespace keyword and whitespace
            while (isset($tokens[$index]) && is_array($tokens[$index])) {
                $namespace .= $tokens[$index++][1];
            }
        }
        if (T_CLASS === $tokens[$index][0] && T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0]) {
            $index += 2; // Skip class keyword and whitespace
            $fqcns[] = $namespace.'\\'.$tokens[$index][1];
        }
    }    
    return $fqcns;
}

function all_test_methods_in($class) {
    $methods = array();
    foreach (get_class_methods($class) as $method) {
        if (starts_with($method, "test")) {
            $methods[] = $method;
        }
    } 
    return $methods;
}

function try_call_function($instance, $method, $count = true) {
    global $FAILED, $SUCCEEDED;
    try {
        if (in_array($method, get_class_methods($instance))) {
            call_user_func(array($instance, $method));
            if ($count) {
                $SUCCEEDED ++;
                logging\info(str_pad(sprintf("    %s->%s", get_class($instance), $method), 50, ".") . "PASSED");    
            }
            return TRUE;
        } else {
            logging\debug("skipping %s->%s", get_class($instance), $method);
            return FALSE;
        }
    } catch (Exception $e) {
        logging\info(str_pad(sprintf("    %s->%s", get_class($instance), $method), 50, ".") . "FAILED");
        if ($count) {
            $FAILED ++;
        }
        return FALSE;
    }
}

function process_all_files_with_condition($path, $condition, $process_func)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fi) {
        if (!$fi->isDot()) {
            $filename = $fi->getFilename();
            if ($fi->isDir()) {
                process_all_files_with_condition(join_paths($path, $filename), $condition, $process_func);
            } else {
                if ($condition($fi)) {
                    $process_func(join_paths($path, $filename));
                }
            }
        }
    }
}

function alltests_condition($fi)
{
    if (starts_with($fi->getFilename(), "test")) {
        return true;
    }

    if (ends_with($fi->getFilename(), "test.php")) {
        return true;
    }

    return false;
}

function call_test_method($file)
{
    logging\info("start running tests in %s", $file);
    @require_once($file);
    $classes = all_classes_in($file);
    if (empty($classes)) {
        logging\info("    cannot find classes in %s", $file);
        return;
    }
    foreach ($classes as $class) {
        $methods = all_test_methods_in($class);
        if (empty($classes)) {
            logging\info("no test methods in %s", $class);
            continue;
        }
        $instance = new $class();
        try_call_function($instance, 'setUp', false);
        foreach ($methods as $method) {
            try_call_function($instance, $method);
        }
        try_call_function($instance, 'tearDown', false);
    }
}

function assertTrue($cond) {
    if (!$cond) throw new Exception();
}

function assertFalse($cond) {
    if ($cond) throw new Exception();
}

function assertEqual($a, $b) {
    if ($a !== $b) throw new Exception();
}

function assertNotEqual($a, $b) {
    if ($a === $b) throw new Exception();
}

function runtests_main() {
    global $FAILED, $SUCCEEDED;
    process_all_files_with_condition(__DIR__, 'alltests_condition', 'call_test_method');
    logging\info("");    
    logging\info("totally %d failed, %d succeeded.", $FAILED, $SUCCEEDED);    
}