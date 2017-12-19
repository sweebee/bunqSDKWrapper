<pre><?php

require 'vendor/autoload.php';
require 'bunq.php';

use wiebenieuwenhuis\bunqApi\bunqApi;
use wiebenieuwenhuis\bunqApi\Payment;


$bunq = new bunqApi();

// Get all accounts
$accounts = $bunq->accounts();
var_dump($accounts);

echo '<hr>';

// Get a single account by ID
$account = $bunq->account($accounts[0]->getId());
var_dump($account);

// Create a payment
$payment = new Payment($bunq);
$payment->amount = 1;
$payment->from = $account;
$payment->to = [
  'type' => 'IBAN',
  'value' => 'NL00BUNQ000000',
  'name' => 'name'
];
$result = $payment->create();
var_dump($result);


