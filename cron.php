<?php
// Run every 15–30 min:  */15 * * * * php /path/to/forex/cron.php
require 'includes.php';
collect(true);
echo "OK " . date('c') . PHP_EOL;
