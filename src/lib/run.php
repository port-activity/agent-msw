<?php
namespace SMA\PAA\AGENT;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once "init.php";

use SMA\PAA\AGENT\MSW\Msw;
use SMA\PAA\SERVICE\StateService;
use SMA\PAA\AINO\AinoClient;
use Exception;

$apiKey = getenv("API_KEY");
$apiUrl = getenv("API_URL");
$validPorts = explode(",", getenv("VALID_TO_PORT_UNLOCODES") ?: "");
$ainoKey = getenv("AINO_API_KEY");

$apiParameters = ["imo", "vessel_name", "time_type", "state", "time", "payload"];

$apiConfig = new ApiConfig($apiKey, $apiUrl, $apiParameters);

$aino = null;
if ($ainoKey) {
    $toApplication = parse_url($apiUrl, PHP_URL_HOST);
    $aino = new AinoClient($ainoKey, "MSW", $toApplication);
}
$agent = new Msw(null, null, $aino);

$aino = null;
if ($ainoKey) {
    $aino = new AinoClient($ainoKey, "MSW service", "MSW");
}
$ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

try {
    $counts = $agent->execute($apiConfig, $validPorts);
    if (isset($aino)) {
        $aino->succeeded($ainoTimestamp, "MSW agent succeeded", "Batch run", "timestamp", [], $counts);
    }
} catch (\Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    if (isset($aino)) {
        $aino->failure($ainoTimestamp, "MSW agent failed", "Batch run", "timestamp", [], []);
    }
}
