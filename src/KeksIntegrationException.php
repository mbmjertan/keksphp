<?php

namespace KeksPHP;

class KeksIntegrationException extends \Exception
{
	public $KeksSeller;
	public $KeksTransaction;
	public $KeksRefund;

    public function __construct($message, $KeksSeller, $KeksTransaction, $KeksRefund = null, $code = 0, Throwable $previous = null) {


    	$this->KeksSeller = $KeksSeller;
    	$this->KeksTransaction = $KeksTransaction;
    	$this->KeksRefund = $KeksRefund;

        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}