<?php
return [
    "usd_rate"=>4000,
    "currency"=>"USD",
    "currency_symbol"=>"$",
    "obd2logs"=>[
        "highest_volt_cell"=>"[BMS] Highest voltage cell value",
        "lowest_volt_cell"=>"[BMS] Lowest voltage cell value",
        "highest_temp_cell"=>"[BMS] Highest temperature cell value",
        "lowest_temp_cell"=>"[BMS] Lowest temperature cell value",
        "ac_power"=>"[BMS] Accumulated charge power [Ah]",
        "ad_power"=>"[BMS] Accumulated discharge power [Ah]",
        "ac"=>"[BMS] Accumulated charge energy",
        "ad"=>"[BMS] Accumulated discharge energy",
        "soc"=>"[BMS] State of charge actual",
        "odo"=>"[VCU] Odometer",
        "voltage"=>"[BMS] Battery total voltage",
    ]
];
