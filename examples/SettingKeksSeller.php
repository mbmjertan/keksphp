<?php
include __DIR__.'/../vendor/autoload.php';
$KeksSeller = new \KeksPHP\KeksSeller;
$KeksSeller->cid = "C00925";
$KeksSeller->tid = "P00952";
$KeksSeller->setHashKey("6D55AD0F8A7CA30F1787C518");
$KeksSeller->successRedirect = 'https://google.com';
$KeksSeller->failRedirect = 'https://bing.com';
$KeksSeller->provideValidateAdviceFunction(function($KeksTransaction){
	if($KeksTransaction->amount != 500) return false;
	return true;
});