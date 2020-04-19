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
        if(in_array($prefix, ['!', '@', '/'])) {
            $command['pref']  = $prefix;
            if(strlen($msg) > 1) {
                $verb = strtolower(substr(rtrim($msg), 1, strpos($msg.' ', ' ') - 1));
                if(ctype_alnum($verb)) {
                    $tokens = explode(' ', trim($msg));
                    $command['verb']  = $verb;
                    $command['count'] = count($tokens) - 1;
                    for($i = 1; $i < count($tokens); $i++) {
                        $command['params'][$i - 1] = trim($tokens[$i]);
                    }
                }
            }
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