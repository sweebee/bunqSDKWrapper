<pre><?php

require 'vendor/autoload.php';

use wiebenieuwenhuis\bunqApi\bunqApi;


// Init the bunq API
$bunq = new bunqApi();

// Get all the accounts
$account = $bunq->accounts->get(246548);
print_r($account->getBalance()->getValue());

