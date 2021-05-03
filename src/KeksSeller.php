<?php

namespace KeksPHP;

class KeksSeller{
	public $cid;
	public $tid;
	public $apiBase = 'https://kekspayuat.erstebank.hr/eretailer';
	public $deepLinkBase = 'https://kekspay.hr/galebpay';
	public $successRedirect = '';
	public $failRedirect = '';
	private $hashKey = '';
	public $currency = 'HRK';

	public $successRedirectFunction;
	public $failRedirectFunction;
	public $validateAdviceFunction;

	public function __construct($cid = '', $tid = '', $apiBase = '', $deepLinkBase = '', $successRedirect = '', $failRedirect = '', $hashKey = ''){
		$this->cid = $cid;
		$this->tid = $tid;
		if($apiBase != ''){
			$this->apiBase = $apiBase;
		}		
		if($deepLinkBase != ''){
			$this->deepLinkBase = $deepLinkBase;
		}
		$this->successRedirect = $successRedirect;
		$this->failRedirect = $failRedirect;
		$this->hashKey = $hashKey;
	}

	public function provideSuccessRedirectFunction($f){
		$this->successRedirect = '';
		$this->successRedirectFunction = $f;
	}

	public function provideFailRedirectFunction($f){
		$this->failRedirect = '';
		$this->failRedirectFunction = $f;
	}

	public function provideValidateAdviceFunction($f){
		$this->validateAdviceFunction = $f;
	}
	public function setHashKey($hashKey){
		$this->hashKey = $hashKey;
	}
	public function getHashKey(){
		return $this->hashKey;
	}
}