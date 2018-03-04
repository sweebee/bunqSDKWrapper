<?php

namespace wiebenieuwenhuis\bunqApi;

use bunq\Context\ApiContext;
use bunq\Model\Generated\Endpoint\User;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Endpoint\Payment as bunqPayment;
use bunq\Model\Generated\Object\Pointer;
use bunq\Model\Generated\Object\NotificationFilter;
use bunq\Model\Generated\Endpoint\UserPerson;

Class bunqApi {

	public $apiContext, $user, $accounts, $payments, $callbacks;

	/**
	 * bunqApi constructor.
	 */
	public function __construct($config_file = 'bunq.conf') {
		// Set the api
		$this->apiContext = ApiContext::restore($config_file);
		// Get the userid
		$users = User::listing($this->apiContext)->getValue();
		$this->user = $users[0]->getUserPerson();

		$this->accounts = new Accounts($this);
		$this->payments = new Payments($this);
		$this->callbacks = new Callbacks($this);
	}

}

Class Accounts {

	private $bunqApi;

	/**
	 * Accounts constructor.
	 *
	 * @param $bunqApi
	 */
	public function __construct($bunqApi)
	{
		$this->bunqApi = $bunqApi;
	}

	/**
	 * return all the accounts
	 *
	 * @return array
	 */
	public function all()
	{
		$list = [];
		$items = MonetaryAccount::listing($this->bunqApi->apiContext, $this->bunqApi->user->getId())->getValue();
		foreach($items as $item){
			$list[] = $item->getMonetaryAccountBank();
		}
		return $list;
	}

	/**
	 * Return a single account by ID
	 *
	 * @param $account_id
	 *
	 * @return \bunq\Model\Generated\Endpoint\MonetaryAccountBank
	 */
	public function get($account_id)
	{
		return MonetaryAccount::get($this->bunqApi->apiContext, $this->bunqApi->user->getId(), $account_id)->getValue()->getMonetaryAccountBank();
	}
}

Class Payments {

	private $bunqApi, $data;

	/**
	 * payment constructor.
	 *
	 * @param $bunqApi
	 */
	public function __construct($bunqApi)
	{
		$this->bunqApi = $bunqApi;
	}

	/**
	 * Get all payments from an account
	 *
	 * @param $account_id
	 */
	public function all($account_id, $params = [], $customHeaders = [])
	{
		return bunqPayment::listing($this->bunqApi->apiContext, $this->bunqApi->user->getId(), $account_id, $params, $customHeaders)->getValue();
	}

	/**
	 * Get a specific payment from an account
	 *
	 * @param $account_id
	 * @param $payment_id
	 *
	 * @return \bunq\Model\Generated\Endpoint\BunqResponsePayment
	 */
	public function get($account_id, $payment_id)
	{
		return bunqPayment::get($this->bunqApi->apiContext, $this->bunqApi->user->getId(), $account_id, $payment_id, $customHeaders = [])->getValue();
	}

	/**
	 * Create a payment
	 *
	 * @return int
	 */
	public function create($from_account, $data, $customHeaders = [])
	{
		$this->map($data);
		$this->validate();

		// Generate the payment
		$paymentMap = [
			bunqPayment::FIELD_AMOUNT               => new Amount($this->data['amount'], $this->data['currency']),
			bunqPayment::FIELD_COUNTERPARTY_ALIAS   => $this->getRecipient(),
			bunqPayment::FIELD_DESCRIPTION          => $this->data['description'],
		];

		// Execute the payment
		return bunqPayment::create($this->bunqApi->apiContext, $paymentMap, $this->bunqApi->user->getId(), $this->getAccount($from_account), $customHeaders)->getValue();
	}

	/**
	 *  Validate the input
	 */
	private function validate()
	{
		if(!is_array($this->data)){
			die('Invalid data, must be an array');
		}
		if(!$this->data['amount']){
			die('No amount provided');
		}
		if(!$this->data['recipient']){
			die('No recipient provided, must MonetaryAccountBank object, account_id or array [type, value, name]');
		}
	}

	/**
	 * @param $data
	 */
	private function map($data)
	{
		$this->data = $data;
		$this->data['amount'] = (string)$this->data['amount'];
		if(!isset($this->data['currency'])){
			$this->data['currency'] = 'EUR';
		}
		if(!isset($this->data['description'])){
			$this->data['description'] = '';
		}
	}

	/**
	 * @return int
	 */
	private function getAccount($account)
	{
		if(is_object($account) && get_class($account) == 'bunq\Model\Generated\Endpoint\MonetaryAccountBank'){
			return $account->getId();
		}
		return $account;
	}

	/**
	 * @return Pointer
	 */
	private function getRecipient()
	{
		$data = $this->data;
		if(is_array($data['recipient'])) {
			$pointer = new Pointer( $data['recipient']['type'], $data['recipient']['value']);
			if($data['recipient']['type'] == 'IBAN'){
				$pointer->setName($data['recipient']['name']);
			}
			return $pointer;
		}

		if(is_object($data['recipient']) && get_class($data['recipient']) == 'bunq\Model\Generated\Endpoint\MonetaryAccountBank'){
			return $data['recipient']->getAlias()[0];
		}
		return $this->bunqApi->account($data['recipient'])->getAlias()[0];
	}
}

Class Callbacks {

	private $bunqApi;

	public function __construct($bunqApi) {
		$this->bunqApi = $bunqApi;
	}

	/**
	 * Get all callbacks
	 *
	 * @param bool $all
	 *
	 * @return array
	 */
	public function all()
	{
		$notifications = $this->bunqApi->user->getNotificationFilters();

		$data = [];
		for ($i = 0; $i < count($notifications); $i++) {
			$filter = $notifications[$i];
			// Remove any URL notification callbacks for the MUTATION category from the array
			if ($filter->getNotificationDeliveryMethod() == 'URL') {
				$data = $notifications[$i];
			}
		}
		return $data;
	}

	/**
	 * Add a callback url
	 *
	 * @param $url
	 * @param $category
	 *
	 * @return bool
	 */
	public function create($url, $category)
	{
		if($this->exists($url)){
			return true;
		}

		$notifications = $this->bunqApi->user->getNotificationFilters();

		$notifications[] = new NotificationFilter('URL', $url, $category);

		UserPerson::update($this->bunqApi->apiContext, [
			UserPerson::FIELD_NOTIFICATION_FILTERS => $notifications],
			$this->bunqApi->user->getId()
		);

		return true;
	}

	/**
	 * Remove a callback url
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	public function delete($url)
	{
		if(!$this->exists($url)){
			return true;
		}

		$notifications = $this->bunqApi->user->getNotificationFilters();

		for ($i = 0; $i < count($notifications); $i++) {
			$filter = $notifications[$i];
			// Remove any URL notification callbacks for the MUTATION category from the array
			if ($filter->getNotificationDeliveryMethod() == 'URL' && $notifications[$i]->getNotificationTarget() == $url) {
				unset($notifications[$i]);
			}
		}

		UserPerson::update($this->bunqApi->apiContext, [
			UserPerson::FIELD_NOTIFICATION_FILTERS => $notifications],
			$this->bunqApi->user->getId()
		);

		return true;
	}

	/**
	 * Check if a callback url exists
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	private function exists($url)
	{
		$notifications = $this->bunqApi->user->getNotificationFilters();
		for ($i = 0; $i < count($notifications); $i++) {
			$filter = $notifications[$i];
			// Remove any URL notification callbacks for the MUTATION category from the array
			if ($filter->getNotificationDeliveryMethod() == 'URL' && $notifications[$i]->getNotificationTarget() == $url) {
				return true;
			}
		}
		return false;
	}
}