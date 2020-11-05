<?php

namespace TESTS\DATA;

use SMA\PAA\AGENT\ApiConfig;
use SMA\PAA\FAKECURL\FakeCurlRequest;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;

class DataProviders
{
    public static function jsonFileContents(string $name)
    {
        return file_get_contents(__DIR__ . "/" . $name . ".json");
    }

    public static function getFakeCurl(): FakeCurlRequest
    {
        return new FakeCurlRequest();
    }

    public static function getFakeResultPoster(): FakeResultPoster
    {
        return new FakeResultPoster();
    }

    public static function getApiConfig(): ApiConfig
    {
        return new ApiConfig("key", "http://url/foo", ["foo"]);
    }

    public static function executeProvider(): array
    {

        $res["execute valid data"][0] = DataProviders::jsonFileContents("ValidServerData");
        $res["execute valid data"][1] = DataProviders::jsonFileContents("ValidParsedData");

        return $res;
    }
}
