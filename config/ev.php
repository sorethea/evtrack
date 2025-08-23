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
    ],
    'socVoltage'=>[
        3.40 => 100,   // 100% Rest
        3.35 => 90,
        3.32 => 80,
        3.30 => 70,
        3.27 => 60,
        3.26 => 50,
        3.25 => 40,
        3.22 => 30,
        3.20 => 20,
        3.00 => 10,
        2.50 => 0
    ],

];
