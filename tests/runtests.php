<?php

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
    echo $file . "\n";
}

process_all_files_with_condition(__DIR__, 'alltests_condition', 'call_test_method');
