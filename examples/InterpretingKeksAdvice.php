<?php

include 'SettingKeksSeller.php';

$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller);

$SampleAdvice = '{"bill_id": 20, "amount": 500, "keks_id": 8822290, "currency": "HRK", "store": "Supremum", "message": "Processing", "tid": "P00123", "status": 0}';

$Response = $KeksTransaction->buildFromAdvice($SampleAdvice);

var_dump($Response);

var_dump(json_encode($KeksTransaction));
