<?php

function toJSON($var, bool $oneLine = false): string {
    $opts = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES;
    $json = \json_encode($var, $opts | (!$oneLine? JSON_PRETTY_PRINT : 0));
    $json = ($json !== '')? $json : var_export($var, true);
    return $json;
}

function parseMsg($msg) {
    $command = ['verb' => '', 'pref' => '', 'count' => 0, 'params' => []];
    if($msg) {
        $msg    = ltrim($msg);
        $prefix = substr($msg, 0, 1);
        if(strlen($msg) > 1 && in_array($prefix, ['!', '@', '/'])) {
            $space = strpos($msg, ' ')?? 0;
            $verb = strtolower(substr(rtrim($msg), 1, ($space === 0? strlen($msg) : $space) - 1));
            $verb = strtolower($verb);
            if(ctype_alnum($verb)) {
                $command['pref']  = $prefix;
                $command['verb']  = $verb;
                $tokens = explode(' ', trim($msg));
                $command['count'] = count($tokens) - 1;
                for($i = 1; $i < count($tokens); $i++) {
                    $command['params'][$i - 1] = trim($tokens[$i]);
                }
            }
            return $command;
        }
    }
    return $command;
}

function parse($msg) {
    echo("'$msg'".PHP_EOL);
    $command = parseMsg($msg);
    echo(toJSON($command).PHP_EOL.PHP_EOL);
}

parse('/');
parse('/ping');
parse('/ping xxx');
parse('/ping xxx yyy');
parse('/ping xxx yyy');

parse('/ ');
parse('/ping ');
parse('/ping xxx ');
parse('/ping xxx yyy ');
parse('/ping xxx yyy ');

parse(' / ');
parse(' /ping ');
parse(' /ping xxx ');
parse(' /ping xxx yyy ');
parse(' /ping xxx yyy ');

parse(' / ');
parse(' /p.ing ');
parse(' /pin-g xxx ');
parse(' /pi*ng xxx yyy ');
parse(' /pi2ng xxx yyy ');