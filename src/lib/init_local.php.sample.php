<?php

$envs = [
    "API_KEY" => "API_KEY"
    ,"API_URL" => "API_URL"
    ,"MSW_REQUEST_URL" => "MSW_REQUEST_URL"
    ,"MSW_BASIC_AUTH" => "MSW_BASIC_AUTH"
    ,"AINO_API_KEY" => "AINO_API_KEY"
];

foreach ($envs as $k => $v) {
    putenv("$k=$v");
};