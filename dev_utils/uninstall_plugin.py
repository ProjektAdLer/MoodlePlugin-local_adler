import fileinput
import pathlib
import os
import subprocess

script_path = pathlib.Path(__file__).parent.resolve()
moodle_path = str(script_path).split("local/adler/")[0]
plugin_path = moodle_path + 'availability/condition/adler'

dep_section = False
for line in fileinput.input(os.path.join(plugin_path, 'version.php'), inplace=True):
    if line.startswith("$plugin->dependencies"):
        dep_section = True

    if dep_section:
        line_before_comments = line.split("//")[0].split("#")[0]
        if ")" in line_before_comments or "]" in line_before_comments:
            dep_section = False
        print("# " + line, end='')
    else:
        print(line, end='')

result = subprocess.run(['php', os.path.join(moodle_path, 'admin/cli/purge_caches.php')])
result = subprocess.run(['php', os.path.join(moodle_path, 'admin/cli/uninstall_plugins.php'), '--plugins=local_adler', '--run'], capture_output=True, text=True)
print(result.stdout)
print(result.stderr)


"""
Maybe a starting point to implement this script as php script in local/adler/cli
Such a script would have to dynamically determine all plugins depending on this plugin.
<?php

define('CLI_SCRIPT', true);
#require(__DIR__ . '/../../../config.php');
# moodle dir from config file

$script_path = dirname(__FILE__);
$moodle_path = explode("local/adler/", $script_path)[0];
$plugin_path = $moodle_path . 'availability/condition/adler';

$dep_section = false;
foreach (file($plugin_path . '/version.php') as $line) {
    if (strpos($line, "$plugin->dependencies") === 0) {
        $dep_section = true;
    }

    if ($dep_section) {
        $line_before_comments = explode("//", $line)[0];
        $line_before_comments = explode("#", $line_before_comments)[0];
        if (strpos($line_before_comments, ")") !== false || strpos($line_before_comments, "]") !== false) {
            $dep_section = false;
        }
        echo "# " . $line;
    } else {
        echo $line;
    }
}

$result = shell_exec('php ' . $moodle_path . '/admin/cli/purge_caches.php');
echo $result;

$result = shell_exec('php ' . $moodle_path . '/admin/cli/uninstall_plugins.php --plugins=local_adler --run');
echo $result;
"""