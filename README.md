# debug
Simple class for debug & logger on PHP

##Installation

To add this package as a local, per-project dependency to your project, simply add a dependency on your project's composer.json file. Here is a minimal example of a composer.json file that just defines a dependency on Debug:

```json
{
    "require": {
        "sincco/debug": "dev-master"
    }
}
```


##Use

Log any message / variable / data.
```php
<?php
Debug::log( 'message', $var1, $var2, ... ); // log as much data as you want
Debug::dump( 'message', $var1, $var2, ... ); // log and exit
```

Use and display chrono.
```php
<?php
Debug::chrono(); // set the timer
Debug::chrono('your message'); // display your message & time elapsed since last chrono call
Debug::chrono(true); // display a table with all messages & times
```

#### NOTICE OF LICENSE
This source file is subject to the Open Software License (OSL 3.0) that is available through the world-wide-web at this URL:
http://opensource.org/licenses/osl-3.0.php

**Happy coding!**
- [ivan miranda](http://ivanmiranda.me)
