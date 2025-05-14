<?php
return [
    "usd_rate"=>4000,
    "currency"=>"USD",
    "currency_symbol"=>"$",
    "obd2logs"=>[
        "[BMS] Highest voltage cell value"=>"highest_volt_cell",
        "[BMS] Lowest voltage cell value"=>"lowest_volt_cell",
        "[BMS] Highest temperature cell value"=>"highest_temp_cell",
        "[BMS] Lowest temperature cell value"=>"lowest_temp_cell",
        "[BMS] Accumulated charge power [Ah]"=>"ac_power",
        "[BMS] Accumulated discharge power [Ah]"=>"ad_power",
        "[BMS] Accumulated charge energy"=>"ac",
        "[BMS] Accumulated discharge energy"=>"ad",
        "[BMS] State of charge"=>"soc",
        "[BMS] State of charge actual"=>"soc_actual",
        "[VCU] Odometer"=>"odo",
        "[BMS] Battery total voltage"=>"voltage",
    ]
];
