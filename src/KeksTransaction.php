<?php

namespace KeksPHP;

class KeksTransaction{
	private $KeksSeller;
	public $bill_id;
	public $keks_id;
	public $tid;
	public $amount;
	public $status;
	public $message;
	private $handledExceptions = [];

	public function __construct($KeksSeller){
		$this->KeksSeller = $KeksSeller;
	}

	public function buildFromAdvice($advice = ''){
		if(!isset($advice)){
			$KeksRequestBody = file_get_contents('php://input');
		}
		else{
			$KeksRequestBody = $advice;
		}
		$KeksAdvice = json_decode($KeksRequestBody);
		try{
			$this->validateAdvice($KeksAdvice);
		}
		catch(KeksIntegrationException $e){
			$this->handledExceptions[] = $e;
			$KeksResponse = $this->buildFailingResponse();
			return $KeksResponse;
		}
		$this->bill_id = $KeksAdvice->bill_id;
		$this->keks_id = $KeksAdvice->keks_id;
		$this->tid = $KeksAdvice->tid;
		$this->amount = $KeksAdvice->amount;
		$this->status = $KeksAdvice->status;
		$this->message = 'Accepted';
		$KeksResponse = $this->buildPassingResponse();
		return $KeksResponse;
	}	

	private function validateAdvice($KeksAdvice){
		if($this->KeksSeller->tid != $KeksAdvice->tid){
			throw new KeksIntegrationException('Mismatch between TID on KeksSeller and Advice received from KEKS', $this->KeksSeller, $this);
		}

		if($KeksAdvice->status != 0){
			throw new KeksIntegrationException('Failed KEKS Transaction', $this->KeksSeller, $this);
		}

		if(($this->KeksSeller->validateAdviceFunction)($KeksAdvice) === true){
			return true;
		}
		else{
			throw new KeksIntegrationException('Provided KEKS Advice validation function did not return true for recieved advice', $this->KeksSeller, $this);
		}
	}

	private function buildFailingResponse(){
		$Response = new \stdClass();
		$Response->status = -1;
		$Response->message = '';
		foreach($this->handledExceptions as $e){
			$Response->message .= (string)$e;
		}
		return $Response;
	}
	private function buildPassingResponse(){
		$Response = new \stdClass();
		$Response->status = $this->status;
		$Response->message = $this->message;
		return $Response;
	}
	public function getExceptions(){
		return $this->handledExceptions;
	}

	public function buildDeepLinkAndQR(){
		$KeksLinks = new \stdClass();
		$KeksSell = new \stdClass();
        $KeksSell->qr_type = "1";
        $KeksSell->cid = $this->KeksSeller->cid;
        $KeksSell->tid = $this->KeksSeller->tid;
        $KeksSell->bill_id = $this->bill_id;
        $KeksSell->amount = number_format($this->amount, 2, '.', '');
        $KeksQRContent = json_encode($KeksSell);

        if(isset($this->KeksSeller->successRedirect)){
        	$KeksSell->success_url = $this->KeksSeller->successRedirect;
        }
        else{
        	$KeksSell->success_url = $this->KeksSeller->successRedirectFunction($this);
        }

        if(isset($this->KeksSeller->failRedirect)){
        	$KeksSell->fail_url = $this->KeksSeller->failRedirect;
        }
        else{
        	$KeksSell->fail_url = $this->KeksSeller->failRedirectFunction($this);
        }

        $KeksDeepLink = $this->KeksSeller->deepLinkBase . '?' . http_build_query($KeksSell);
        $KeksLinks->qr = $KeksQRContent;
        $KeksLinks->deepLink = $KeksDeepLink;
        return $KeksLinks;
	}
}