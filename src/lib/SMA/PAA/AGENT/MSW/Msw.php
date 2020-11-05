<?php
namespace SMA\PAA\AGENT\MSW;

use SMA\PAA\CURL\ICurlRequest;
use SMA\PAA\CURL\CurlRequest;
use SMA\PAA\RESULTPOSTER\IResultPoster;
use SMA\PAA\RESULTPOSTER\ResultPoster;
use SMA\PAA\AGENT\ApiConfig;
use SMA\PAA\AINO\AinoClient;

use Exception;
use DateTimeInterface;
use DateTime;
use DateInterval;

class Msw
{
    private $config;
    private $curlRequest;
    private $resultPoster;
    private $aino;

    public function __construct(
        ICurlRequest $curlRequest = null,
        IResultPoster $resultPoster = null,
        AinoClient $aino = null
    ) {
        $this->curlRequest = $curlRequest ?: new CurlRequest();
        $this->resultPoster = $resultPoster ?: new ResultPoster(new CurlRequest());
        $this->aino = $aino;

        $this->config = require("MswConfig.php");

        date_default_timezone_set("UTC");
    }

    public function execute(ApiConfig $apiConfig, array $validPorts)
    {
        $rawResults = $this->fetchResults();
        $parsedResults = $this->parseResults($rawResults, $validPorts);
        return $this->postResults($apiConfig, $parsedResults);
    }

    private function fetchResults(): array
    {
        $res = [];

        $dateIntervalHistory = new DateInterval("P2D");
        $dateIntervalFuture = new DateInterval("P1M");

        $startDate = new DateTime();
        $startDate->sub($dateIntervalHistory);
        $startDateStr = $startDate->format("Y-m-d\TH:i:s.v\Z");

        $endDate = new DateTime();
        $endDate->add($dateIntervalFuture);
        $endDateStr = $endDate->format("Y-m-d\TH:i:s.v\Z");

        $postPayload["startDate"] = $startDateStr;
        $postPayload["endDate"] = $endDateStr;

        $this->curlRequest->init(getenv("MSW_REQUEST_URL"));
        $this->curlRequest->setOption(CURLOPT_ENCODING, ""); // allow all encodings, gzip etc.
        $this->curlRequest->setOption(CURLOPT_RETURNTRANSFER, true);
        #$this->curlRequest->setOption(CURLOPT_VERBOSE, 1);
        $this->curlRequest->setOption(CURLOPT_USERPWD, getenv("MSW_BASIC_AUTH"));
        $this->curlRequest->setOption(CURLOPT_HEADER, 1);
        $header = [];
        $header[] = "Content-Type: application/json";
        $header[] = "Accept: application/json";
        $this->curlRequest->setOption(CURLOPT_HTTPHEADER, $header);
        $this->curlRequest->setOption(CURLOPT_POST, 1);
        $this->curlRequest->setOption(CURLOPT_POSTFIELDS, json_encode($postPayload));

        $response = $this->curlRequest->execute();
        $info = $this->curlRequest->getInfo();
        $headerSize = $this->curlRequest->getInfo(CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $this->curlRequest->close();

        if ($info["http_code"] !== 200) {
            throw new Exception(
                "Error occured during curl exec.\ncurl_getinfo returns:\n" . print_r($info, true) . "\n"
                . "Response body:\n". print_r(json_decode($body, true), true) . "\n"
            );
        }

        $res = json_decode($body, true);

        return $res;
    }

    private function parseResults(array $rawResults, array $validPorts): array
    {
        $res = [];

        foreach ($rawResults as $rawResult) {
            $timestamp = [];
            $payload = [];

            $timestamp["imo"] = 0;
            foreach ($this->config["parameter_mappings"] as $in => $out) {
                if (isset($rawResult[$in])) {
                    if ($out === "imo") {
                        $timestamp[$out] = (int)$rawResult[$in];
                    } else {
                        $timestamp[$out] = $rawResult[$in];
                    }
                }
            }

            $payload["original_message"] = $rawResult;
            foreach ($this->config["payload_mappings"] as $in => $out) {
                if (isset($rawResult[$in])) {
                    $payload[$out] = $rawResult[$in];
                }
            }

            foreach ($this->config["timestamp_mappings"] as $in => $out) {
                if (isset($rawResult[$in])) {
                    $timestamp["time_type"] = $out["time_type"];
                    $timestamp["state"] = $out["state"];
                    $dateTime = DateTime::createFromFormat("Y-m-d\TH:i:sO", $rawResult[$in]);
                    $timestamp["time"] = $dateTime->format("Y-m-d\TH:i:sO");
                    $timestamp["payload"] = $payload;
                    if (in_array($payload["to_port"], $validPorts)) {
                        $res[] = $timestamp;
                    }
                }
            }
        }

        return $res;
    }

    private function postResults(ApiConfig $apiConfig, array $results)
    {
        $countOk = 0;
        $countFailed = 0;

        $ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        foreach ($results as $result) {
            $ainoFlowId = $this->resultPoster->resultChecksum($apiConfig, $result);
            try {
                $this->resultPoster->postResult($apiConfig, $result);
                ++$countOk;
                if (isset($this->aino)) {
                    $this->aino->succeeded(
                        $ainoTimestamp,
                        "MSW agent succeeded",
                        "Post",
                        "timestamp",
                        ["imo" => $result["imo"]],
                        [],
                        $ainoFlowId
                    );
                }
            } catch (\Exception $e) {
                ++$countFailed;
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
                if (isset($this->aino)) {
                    $this->aino->failure(
                        $ainoTimestamp,
                        "MSW agent failed",
                        "Post",
                        "timestamp",
                        [],
                        [],
                        $ainoFlowId
                    );
                }
            }
        }

        return [
            "ok" => $countOk,
            "failed" => $countFailed
        ];
    }
}
