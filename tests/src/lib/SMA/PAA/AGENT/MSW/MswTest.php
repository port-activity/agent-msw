<?php

namespace SMA\PAA\AGENT\MSW;

use PHPUnit\Framework\TestCase;

use TESTS\DATA\DataProviders;

final class MswTest extends TestCase
{
    /**
     * @dataProvider \TESTS\DATA\DataProviders::executeProvider
     */
    public function testExecute($serverData, $posterData): void
    {
        $fakeCurl = DataProviders::getFakeCurl();
        $fakeResultPoster = DataProviders::getFakeResultPoster();
        $apiConfig = DataProviders::getApiConfig();
        $msw = new Msw($fakeCurl, $fakeResultPoster);
        $fakeCurl->getInfoReturn = ["http_code" => 200];
        $fakeCurl->executeReturn = $serverData;
        $msw->execute($apiConfig, array("SEGVX", "SEKAS"));

        /* file_put_contents(
            "tests/data/ValidParsedData.json",
            json_encode($fakeResultPoster->results, JSON_PRETTY_PRINT)
        ); */

        $this->assertEquals($fakeResultPoster->results, json_decode($posterData, true));
    }
}
