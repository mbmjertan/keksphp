<?php

namespace KeksPHP;

class KeksRefund{
	public $KeksTransaction;
	private $KeksSeller;
	public $RefundAmount;
	public $RefundTime;
	public $RefundHash;
	public $RefundRequest;
	protected $RefundSuccessful = false;
	protected $KeksResponse;

	public function __construct($KeksSeller, $KeksTransaction){
		$this->KeksSeller = $KeksSeller;
		$this->KeksTransaction = $KeksTransaction;
	}
	private function calculateKeksHash(){
		$HashString = $this->RefundTime . $this->KeksSeller->tid . $this->RefundAmount . $this->KeksTransaction->bill_id;
		$HashString = strtoupper(md5($HashString));
		$Key = $this->KeksSeller->getHashKey();
		$Hash = @openssl_encrypt(hex2bin($HashString), 'des-ede3-cbc', $Key, OPENSSL_RAW_DATA);
		return strtoupper(bin2hex($Hash));

	}
	public function createRefund($amount){
		if($this->KeksTransaction->amount < $amount){
			throw new KeksIntegrationException('Amount of refund is higher than amount of KeksTransaction', $this->KeksSeller, $this->KeksTransaction, $this);
		}
		$this->RefundAmount = $amount;
		$this->RefundTime = time();
		$RefundRequest = new \stdClass();
		$RefundRequest->bill_id = $this->KeksTransaction->bill_id;
		$RefundRequest->keks_id = $this->KeksTransaction->keks_id;
		$RefundRequest->tid = $this->KeksSeller->tid;
		$RefundRequest->cid = $this->KeksSeller->cid;
		$RefundRequest->amount = $this->RefundAmount;
		$RefundRequest->currency = $this->KeksSeller->currency;
		$RefundRequest->epochtime = $this->RefundTime;
		$RefundRequest->hash = $this->calculateKeksHash();
		$this->RefundRequest = $RefundRequest;
	}

	public function validateKeksResponse($KeksResponse){
		if(isset($KeksResponse->exception)){
			throw new KeksIntegrationException($KeksResponse->exception, $this->KeksSeller, $this->KeksTransaction);
		}
		if(isset($KeksResponse->status)){
			if($KeksResponse->status == 0){
				$this->RefundSuccessful = true;
			}
			else{
				$this->RefundSuccessful = false;
			}

		}
		else{
			$this->RefundSuccessful = false;
		}
	}

	public function submitRefund(){
		$Endpoint = $this->KeksSeller->apiBase . '/keksrefund';
		$Guzzle = new \GuzzleHttp\Client();
		$Response = $Guzzle->request('POST', $Endpoint, [
			'body' => json_encode($this->RefundRequest)
		]);
		$KeksResponse = json_decode($Response->getBody());
		$this->validateKeksResponse($KeksResponse);
	}

	
}