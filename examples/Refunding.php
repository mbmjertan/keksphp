<?php
include 'SettingKeksSeller.php';

$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller);
$KeksTransaction->bill_id = 20;
$KeksTransaction->amount = 500;

$SampleAdvice = '{"bill_id": 20, "amount": 500, "keks_id": 8822290, "currency": "HRK", "store": "Supremum", "message": "Processing", "tid": "P00952", "status": 0}';

$KeksTransaction->buildFromAdvice($SampleAdvice);
var_dump($KeksTransaction);

$KeksRefund = new \KeksPHP\KeksRefund($KeksSeller, $KeksTransaction);

$KeksRefund->createRefund(50);

var_dump($KeksRefund->RefundRequest);