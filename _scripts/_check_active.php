<?php
define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
$active = (array) get_option('active_plugins', []);
echo "Active plugins (" . count($active) . "):\n";
foreach ($active as $p) echo "  $p\n";
