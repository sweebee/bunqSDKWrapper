<pre><?php

require 'vendor/autoload.php';

use wiebenieuwenhuis\bunqApi\bunqApi;

// Init the bunq API
$bunq = new bunqApi();

// Get all accounts
$accounts = $bunq->accounts->all();
print_r($accounts);
