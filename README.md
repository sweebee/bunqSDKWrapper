# bunqSDKWrapper
simple wrapper around the bunq SDK

## Examples

Always create a new instance
```php
use wiebenieuwenhuis\bunqApi\bunqApi;
$bunq = new bunqApi();

```

### Accounts

Get all the accounts

```php
$accounts = $bunq->accounts->all();
```

Get a single account and get the balance value
```php
$account = $bunq->accounts->get($account_id);
$value = $account->getBalance()->getValue();
```

### Payments

Get all payments

```php
$payments = $bunq->payments->all($account_id);
```

Get a single payment
```php
$payment = $bunq->payments->get($account_id, $payment_id);
```

Create a payment to a own bunq account by ID

```php
$payment = $bunq->payments->create($account_id, [
    'recipient' => $recipient_id,
    'amount' => 10
]);
```

Create a payment to a IBAN account

```php
$payment = $bunq->payments->create($account_id, [
    'recipient' => [
        'type' => 'IBAN',
        'name' => 'John Doe',
        'value' => 'NL00BUNQ000000000'
    ],
    'amount' => 10
]);
```

### Callbacks

Get all user callbacks

```php
$callbacks = $bunq->callbacks->all();
```

Create a callback
```php
$bunq->callbacks->create('https://mysite.nl/callback', 'MUTATION');
```

Delete a callback
```php
$bunq->callbacks->delete('https://mysite.nl/callback');
```