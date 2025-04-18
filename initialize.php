<?php
$dev_data = array(
    'id' => '-1',
    'firstname' => 'Developer',
    'lastname' => '',
    'username' => 'narenthiran',
    'password' => password_hash('nari', PASSWORD_DEFAULT), // Use password hashing for security
    'last_login' => '',
    'date_updated' => '',
    'date_added' => ''
);

if (!defined('base_url')) define('base_url', 'http://localhost/tcc/');
if (!defined('base_app')) define('base_app', str_replace('\\', '/', __DIR__) . '/');
if (!defined('DEV_DATA')) define('DEV_DATA', $dev_data);
if (!defined('DB_SERVER')) define('DB_SERVER', "localhost");
if (!defined('DB_USERNAME')) define('DB_USERNAME', "root");
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', "");
if (!defined('DB_NAME')) define('DB_NAME', "tcc");
