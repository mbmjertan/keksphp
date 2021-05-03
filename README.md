# KeksPHP

KeksPHP is an implementation of the KEKS Pay API webshop backend integrations in PHP. You can use it as a reference when building your own custom implementation and it's available as a library on Composer/Packagist as well. 

It's free and open source, and published under the MIT license. However, it's not built or supported by the KEKS Pay team and I can't provide any warranties for it. Use it as you wish, but your usage is your resposibility entirely. 

KeksPHP only handles *the communication* with KEKS Pay. You'll still need to integrate it with your own order management, you'll still need to implement some kind of transaction logging (which KeksPHP does make easy-ish) and you'll still need to build a frontend for KEKS Pay payments.

To start integrating with KEKS Pay, you'll need to contact the KEKS Pay team first. You can find their contact info, as well as their official documentation on [the KEKS Pay website](https://kekspay.hr).


## Usage

### Installing and autoloading

Install KeksPHP with Composer (or another tool of your choice). If you're using Composer, all KeksPHP classes will be available under the `KeksPHP` namespace after including autoload.php. 

If you're new to Composer, please look into how to use it and come back here. You can also just download this code and integrate it into your project (but making sure you handle _this project's_ dependencies as well), but Composer will make all of this easier.

If you'd really like to avoid using Composer, make sure that you autoload the classes in the `src\` folder using PSR-4 under the namespace `KeksPHP`.

### Requirements

KeksPHP is tested under PHP 8. While it may work under previous PHP versions, I can't support older versions.

You'll need the PHP JSON and OpenSSL extensions. If you're using the `KeksRefund::sumbitRefund` method, you'll also need to make sure you're able to use Guzzle. If you're using Composer, Guzzle will be automatically installed as well.

### Setting KeksSeller

All other KeksPHP classes require `KeksSeller` as a member. 

KeksSeller contains your KEKS Pay configuration (CID, TID, API Base and Deep Link Base, which you'll get from the KEKS Pay team) and lets you provide functions that define how to validate the `advice` you recieve from KEKS Pay and how to build success/failure URLs for customers.

To set the CID and TID, set the cid and tid members of the class instance respectively. To set the hashKey, use the setHashKey method.

```php

$KeksSeller = new \KeksPHP\KeksSeller;
$KeksSeller->cid = "C00111";
$KeksSeller->tid = "P00222";
$KeksSeller->setHashKey("SOMEHASHKEYPROVIDEDBYKEKS");

```

If your success and failure URLs are fixed for *every* transaction, you can set the successRedirect and failRedirect members of the class instance respectively, like so:

```php

$KeksSeller->successRedirect = 'https://google.com';
$KeksSeller->failRedirect = 'https://bing.com';
```

If they can change depending on transaction data, provide a closure for them like so:
```php
$KeksSeller->provideSuccessRedirectFunction(function($KeksTransaction){
	// your code here
	return $successURL;
});

$KeksSeller->provideFailRedirectFunction(function($KeksTransaction){
	// your code here
	return $failURL;
});
```
The closures will be called with the corresponding KeksTransaction class instance.

You'll also need to validate the recieved advice with a function. At the very least, you need to check if the cart amount on your store matches with the amount paid via KEKS Pay. To let KeksPHP know what function to call, set a closure like so, which will be called with an instance of `\stdClass` (a generic object) built from the JSON recieved from KEKS Pay:

```php
$KeksSeller->provideValidateAdviceFunction(function($KeksAdvice){
	// your code here, with a very very simple example as well
	// if the JSON has a someField object, you can access it using $KeksAdvice->someField
	if($KeksAdvice->amount != $YourShopOrder->amount) return false;
	return true;
});

```

If a transaction should be accepted, return `true`. Otherwise, return `false`.



### KeksTransaction: Initiating a transaction

To initiate a transaction, you'll need to serve a QR code to customers on the desktop and a deep link to customers on phones.

To make generating these QR codes and deep links easier, KeksPHP provides a method to build the QR codes and deep links for you.

To do so, create an instance of the `KeksTransaction` class by calling the constructor with your `KeksSeller` instance as its argument.

Set the `bill_id` member of the `KeksTransaction` instance to a unique order identifier in your stores backend (order ID, UUID, whatever - KeksPHP doesn't care, but you'll need to be able to identify an _order_ based on its `bill_id`).

Set the `amount` member of the `KeksTransaction` instance to the amount you want to charge using KEKS Pay. `amount` is an int or a float, and will be appropriately formatted by KeksPHP. For instance, to charge 20 HRK, set `amount` to equal `20`, and to charge 20.05 HRK, set `amount` to equal `20.05` -- do not set `amount` to a formatted string. 

Then, call the `buildDeepLinkAndQR` method of the `KeksTransaction` instance. This will return a `\stdClass` instance with two members: `qr` and `deeplink`. 



```php

$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller); // create a new KeksTransaction
$KeksTransaction->bill_id = 20;
$KeksTransaction->amount = 500;
var_dump($KeksTransaction->buildDeepLinkAndQR()); // use $KeksTransaction->buildDeepLinkAndQR()->deeplink to get the deeplink

```

You can then generate a QR code containing **the value** of the `qr` member of the class or a link to **the value** of the `deeplink` member of the class and display it to the user. How you'll handle that: that's up to you. 

I'd advise you to find a library for your language (or a JavaScript library or an API) to generate the QR code and to toggle QR code/deep link visibility with media queries. 

### KeksTransaction: Interpreting recieved advice

#### Requirements

You'll need to build an endpoint where KEKS Pay will send POST requests to send the `advice`. You'll also need to implement some authentification there - KeksPHP **will not** do this for you.

There are two ways you can do this: using token autentification and [PHP Basic Auth](https://www.php.net/manual/en/features.http-auth.php).

If you're going to be catching the KEKS Pay request yourself, store it in a variable and pass it as a parameter to the `buildFromAdvice` method of the `KeksTransaction` class. If you don't to this, KeksPHP will try to catch the request itself.

#### Interpreting advice

If you've caught the advice content from php://input yourself:

```php
$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller);

$Response = $KeksTransaction->buildFromAdvice($YourAdvice);

echo json_encode($Response);
```

If you want KeksPHP to catch the advice for you:
```php
$KeksTransaction = new \KeksPHP\KeksTransaction($KeksSeller);

$Response = $KeksTransaction->buildFromAdvice();

echo json_encode($Response);
```

KeksPHP will now:
	* check if your TID in KeksSeller matches the TID in the advice recieved
	* check if the transaction has cleared (status == 0)
	* call your validation closure with the advice recieved

If any of these checks fail, KeksPHP will throw a `KeksIntegrationException`, handle it and log it into the `handledExceptions` member array of the KeksTransactions instance, which you can access with the `getExceptions` method to check what went wrong. 

Furthermore, the `buildFromAdvice` method will return a response with status of -1 and message equalling the exception messages.

If these checks pass, KeksPHP will return a response with status of 0 and message = 'Accepted'.

#### Responding to KEKS Pay

To respond to KEKS Pay after handling the transaction, encode the `buildFromAdvice` output at JSON (using `json_encode`) and return it.

#### Logging transactions

How you'll do this is up to you, but you can simply serialize or json_encode the KeksTransaction instance.


### Refunding

To refund a transaction, create a `KeksRefund` by calling its constructor with the corresponding KeksSeller and KeksTransaction instances.

```php
$KeksRefund = new \KeksPHP\KeksRefund($KeksSeller, $KeksTransaction);
```

Then, you can choose a path.

#### Just generate the refund request, but don't send it


If you'd like to send the refund request yourself, you can just ask KeksPHP to create the Refund, but not to send it - using the `createRefund` method, setting its only parameter to **the amount to be refunded**. You can then access it using the `RefundRequest` member of the KeksRefund instance.


```php

$KeksRefund->createRefund($Amount);

your_function_to_send_refund_request($KeksRefund->RefundRequest)

```

This will not validate the KEKS Pay response, so if you need to do that, call the `validateKeksResponse` method with the KEKS Pay API reponse body as its only parameter

```php

$KeksRefund->validateKeksResponse($KeksReponse);

```

This will change the `RefundSuccessful` member of the class to either true or false. (It defaults to false)

#### Do everything for me

If you'd like KeksPHP to send the refund request for you, first generate it using the `createRefund` method, setting its only parameter to **the amount to be refunded**.

Then call the `submitRefund` method to send the refund to KEKS Pay and check the `RefundSuccessful` member to see if the refund was successful.
```php

$KeksRefund->createRefund($Amount);

$KeksRefund->submitRefund();

if($KeksRefund->RefundSuccessful){
	echo 'yay, successfull refund!';
}
else{
	echo 'oh no, I could not refund!';
}
```

#### Possible pitfalls

If the amount **to be refunded** is higher than the **original transaction amount**, you'll get a `KeksIntegrationException`.

### Exceptions

If an exception occurs in KeksPHP's `KeksTransaction` methods, it'll try to handle it and store it in the `handledExceptions` member of itself, which you can access using the `getExceptions` method of the class.

If an exception occurs in `KeksRefund`, you'll get it thrown your way as there's no chance to recover.

All exceptions thrown by KeksPHP are of the type `KeksIntegrationException` which extends PHP's generic exceptions to provide you with the applicable `KeksSeller`, `KeksTransaction` and `KeksRefund` transactions whereever possible to make debugging easier. 

You can access these by accessing the `KeksSeller`, `KeksTransaction` and `KeksRefund` members of the exception respectively.

## Support and bugs

For questions and issues regarding this project, open an issue here. (Croatian is fine here, as well!)

For questions regarding KEKS Pay, contact the KEKS Pay team.
 