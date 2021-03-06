#!/usr/bin/env php
<?php

$repl_stdin = fopen('php://stdin', 'r');
$repl_data = '';
$repl_lines = array();
$repl_include_dir = dirname(__FILE__) . '/include';
$repl_include_file_pattern = '/.*\.php$/';
 
register_print_code_func();

foreach (get_include_files() as $file) {
    print 'including ' . basename($file) . PHP_EOL;
    include $file;
}
while(true) {
    print '> ';
    $repl_data .= fgets($repl_stdin);
    if (preg_match('/\\\\$/', $repl_data)==1) {
        $repl_data = trim($repl_data, "\x00..\x1F \x5C");
    } else {
        if(eval($repl_data) !== FALSE) {
            $repl_lines[] = trim($repl_data);
        }
        $repl_data = '';
    }
}


function l($d) { print strlen($d) . PHP_EOL; }

function d() {
    $args = func_get_args();
    foreach ($args as $arg) {
        var_dump($arg);
    }
}

function p() {
    $args = func_get_args();
    foreach ($args as $arg) {
        print $arg . PHP_EOL;
    }
}

function h($data) {
    $colLimit = 15;
    $col = 0;
    $length = strlen($data);
    for ($i=0; $i < $length; $i++) {
        $d = unpack('C', $data[$i]);
        printf('%02X ', $d[1]);
        if ($col==$colLimit) {
            $col=0;
            print PHP_EOL;
        } else {
            $col++;
        }
    }
    print PHP_EOL;
}


function get_include_files() {
    global 
        $repl_include_dir, 
        $repl_include_file_pattern;

    $dir = dir($repl_include_dir);
    if (!$dir) {
        return;
    }

    $entries = array();
    while (false !== ($entry=$dir->read())) {
        if (!preg_match($repl_include_file_pattern, $entry)) {
            continue;
        }
        $entries[] = realpath($dir->path . '/' . $entry);
    }

    usort($entries, function($a, $b) {
        $aNum = (int)$a;
        $bNum = (int)$b;
        if ($aNum == $bNum) {
            return 0;
        }

        return ($aNum < $bNum) ? -1 : 1;
    });

    return $entries;
}

function register_print_code_func() {
    global $repl_lines;
    register_shutdown_function(function() use(&$repl_lines) {
     
        $source = join("\n", $repl_lines);
     
        $c_string = ini_get('highligth.string'); // get ini values
        $c_comment = ini_get('highlight.comment');
        $c_keyword = ini_get('highlight.keyword');
        $c_default = ini_get('highlight.default');
        $c_html = ini_get('highlight.html');
     
        $source = highlight_string("<?php\n". $source, true);
     
        // 30 = gray, 31 = red, 32 = green, 33 = yellow, 34 = blue, 35 = purple, 36 = cyan, 37 = white
     
        $source = str_replace('</span>', "\033[0m", $source);
     
        $source = str_replace('<span style="color: #DD0000">', "\033[0;32m", $source);
        $source = str_replace('<span style="color: '.$c_string.'">', "\033[0;32m", $source);
        $source = str_replace('<span style="color: '.$c_comment.'">', "\033[0;37m", $source);
        $source = str_replace('<span style="color: '.$c_keyword.'">', "\033[0;34m", $source);
        $source = str_replace('<span style="color: '.$c_default.'">', "\033[0;31m", $source);
        $source = str_replace('<span style="color: '.$c_html.'">', "\033[0;30m", $source);
     
        $source = str_replace('<code>', '', $source);
        $source = str_replace('</code>', '', $source);
        $source = str_replace('<br />', "\n", $source);
        $source = str_replace('&nbsp;', ' ', $source);
     
        $source = html_entity_decode($source);
     
        print "\n" . $source . "\n";
    });
}
