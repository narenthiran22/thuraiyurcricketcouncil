<?php
ob_start();
ini_set('date.timezone', 'Asia/Manila');
date_default_timezone_set('Asia/Manila');
session_start();
require_once 'initialize.php';
require_once 'classes/DBConnection.php';
require_once 'classes/SystemSettings.php';

// Ensure base_url is defined
if (!defined('base_url')) {
    define('base_url', 'http://localhost/tcc/'); // Adjust the URL as needed
}

// Initialize AppConfig
class AppConfig
{
    private $db;
    private $conn;
    private $tables = [];

    // Constructor: Initializes the DB connection and loads all table data
    public function __construct()
    {
        $this->db = new DBConnection();  // Initialize the DBConnection class
        $this->conn = $this->db->conn;   // Get the connection
        $this->loadTables();             // Load tables dynamically
    }

    // Load all tables from the database and store their data
    private function loadTables()
    {
        try {
            // Fetch all table names from the database
            $query = $this->conn->query("SHOW TABLES");
            while ($row = $query->fetch_array()) {
                $tableName = $row[0];
                $this->tables[$tableName] = $this->fetchData("SELECT * FROM `$tableName`");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Fetch data from the database for a specific table
    private function fetchData($sql)
    {
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Magic method to access table data dynamically (e.g., $appconfig->users)
    public function __get($name)
    {
        return $this->tables[$name] ?? null;
    }
}

// Initialize AppConfig object
$appconfig = new AppConfig();

/**
 * Redirect to a specific URL
 */
function redirect($url = '')
{
    if (!empty($url)) {
        header('Location: ' . base_url . $url);
        exit;
    }
}

/**
 * Validate image existence and return the correct URL
 */
function validate_image($file)
{
    if (!empty($file)) {
        $ex = explode("?", $file);
        $ts = isset($ex[1]) ? "?{$ex[1]}" : '';

        if (is_file(base_app . $file)) {
            return base_url . $file . $ts;
        } else {
            return base_url . 'dist/img/no-image-available.png';
        }
    } else {
        return base_url . 'dist/img/no-image-available.png';
    }
}

/**
 * Format numbers with decimal places
 */
function format_num($number = '', $decimal = '')
{
    if (is_numeric($number)) {
        $ex = explode(".", $number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        return is_numeric($decimal) ? number_format($number, $decimal) : number_format($number, $decLen);
    } else {
        return "Invalid Input";
    }
}

/**
 * Detect if the user is on a mobile device
 */
function isMobileDevice()
{
    $aMobileUA = array(
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile'
    );

    foreach ($aMobileUA as $sMobileKey => $_) {
        if (preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }
    }
    return false;
}

ob_end_flush();
