# infection mutation testing

All paths relative are relative to the moodle root, if not stated otherwise

in moodle root:
wget https://github.com/infection/infection/releases/download/0.29.0/infection.phar
chmod +x infection.phar

create file vendor/autoloadmoodle.php
```
<?php
define("PHPUNIT_UTIL", true);
require(__DIR__ . '/../lib/phpunit/bootstrap.php');
require('autoload.php');
```

create file local/logging/infection.json5
```
{
  "$schema": "https://raw.githubusercontent.com/infection/infection/0.29.0/resources/schema.json",
  "source": {
    "directories": [
      "."
    ]
  },
  "phpUnit": {
    "configDir": ".",
    "customPath": "./vendor/phpunit/phpunit/phpunit"
  },
  "initialTestsPhpOptions": "-dxdebug.mode=off -dpcov.enabled=1 -dpcov.directory=.",
  "bootstrap": "vendor/autoloadmoodle.php",
  "mutators": {
    "@default": true
  }
}
```

./infection.phar -s --only-covered --configuration=local/logging/infection.json5