<?php

return array(
    "parameter_mappings" => [
        "imo" => "imo",
        "name" => "vessel_name"],
    "payload_mappings" => [
        "portVisitID" => "external_id",
        "unLocodePreviousPort" => "from_port",
        "unLocode" => "to_port",
        "unLocodeNextPort" => "next_port",
        "flagState" => "nationality",
        "callSign" => "call_sign",
        "mmsi" => "mmsi",
        "grossWeight" => "gross_weight",
        "netWeight" => "net_weight"],
    "timestamp_mappings" => [
        "eta" => ["time_type" => "Estimated", "state" => "Arrival_Vessel_PortArea"],
        "etd" => ["time_type" => "Estimated", "state" => "Departure_Vessel_Berth"]
    ]
);
