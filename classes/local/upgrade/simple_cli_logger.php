<?php

namespace local_adler\local\upgrade;

class simple_cli_logger {
    public function __call($name, $arguments) {
        $logLevels = ['info', 'warning', 'error'];
        if (in_array($name, $logLevels) && isset($arguments[0])) {
            cli_writeln(strtoupper($name) . ': ' . $arguments[0]);
        }
    }
}