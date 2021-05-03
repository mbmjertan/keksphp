<?php
include __DIR__.'/../vendor/autoload.php';
$KeksSeller = new \KeksPHP\KeksSeller;
$KeksSeller->cid = "A12345";
$KeksSeller->tid = "P12345";
$KeksSeller->setHashKey("KFKFAKAKLOA12");
$KeksSeller->successRedirect = 'https://google.com';
$KeksSeller->failRedirect = 'https://bing.com';
$KeksSeller->provideValidateAdviceFunction(function($KeksTransaction){
	if($KeksTransaction->amount != 500) return false;
	return true;
});
