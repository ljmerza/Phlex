<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . "/php/webApp.php";
require_once dirname(__FILE__) . '/php/util.php';
require_once dirname(__FILE__) . '/PHPTail.php';

error_reporting(E_ERROR);
if (!isset($_GET['apiToken']) || isWebApp()) {
	die("Unauthorize access detected.");
} else {
	$apiToken = $_GET['apiToken'];
        if (!verifyApiToken($apiToken)) {
            write_log("Invalid API Token used for logfile access.");
            die("Invalid API Token");
        }
}

$logs = array(
	"Main" => realpath(dirname(__FILE__)."/logs/Phlex.log.php"),
	"Updates" => realpath(dirname(__FILE__)."/logs/Phlex_update.log.php"),
	"Error Log" => realpath(dirname(__FILE__)."/logs/Phlex_error.log.php"),
);

$testPaths = [
    "Apache" => ["/var/log/apache2/error.log","/var/log/httpd/apache24-error_log","/var/log/httpd/apache23-error_log"],
    "NGINX" => ["/var/log/nginx/nginx_error.log", "/usr/local/var/log/nginx/error.log"],
    "IIS" => ['C:\Windows\Temp\PHP70_errors.log','C:\Windows\Temp\PHP71_errors.log','C:\Windows\Temp\PHP72_errors.log'],
    "Synology (PHP)" => ["/var/log/httpd/php_error.log"],
    "Cast Plugin" => [
        '%LOCALAPPDATA%\Plex Media Server\Logs\PMS Plugin Logs/com.plexapp.plugins.Cast.log',
        "~/Library/Logs/Plex Media Server/PMS Plugin Logs/com.plexapp.plugins.Cast.log",
        "/sdcard/Plex Media Server/Logs/PMS Plugin Logs/com.plexapp.plugins.Cast.log",
        '$PLEX_HOME/Library/Application Support/Plex Media Server/Logs/PMS Plugin Logs/com.plexapp.plugins.Cast.log'
    ]
];

$logPath = ini_get("error_log");
$pushDefault = true;
foreach ($testPaths as $name=>$testPath) {
    foreach($testPath as $path) {
        //$path = realpath($path);
        if (file_exists($path)) {
            if ($path == $logPath) {
                $pushDefault = false;
            }
            $logs[$name] = $path;
        }
    }
}

if ($pushDefault && trim($logPath)) $logs['PHP'] = $logPath;


$noHeader = $_GET['noHeader'] ?? false;
$tail = new PHPTail($logs,1000,2097152,$apiToken,$noHeader);

/**
 * We're getting an AJAX call
 */
if(isset($_GET['ajax']))  {
	echo $tail->getNewLines($_GET['file'], $_GET['lastsize'], $_GET['grep'], $_GET['invert'], intval($_GET['count']));
	die();
}

/**
 * Regular GET/POST call, print out the GUI
 */
$tail->generateGUI();