<?php

include 'SettingKeksSeller.php';

$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller);
$KeksTransaction->bill_id = 20;
$KeksTransaction->amount = 500;
var_dump($KeksTransaction->buildDeepLinkAndQR());
