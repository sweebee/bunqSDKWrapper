<?php

namespace wiebenieuwenhuis\bunqApi;

use bunq\Context\ApiContext;
use bunq\Model\Generated\Endpoint\User;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Endpoint\Payment as bunqPayment;
use bunq\Model\Generated\Object\Pointer;

Class bunqApi {

	public $apiContext, $user;

	/**
	 * bunqApi constructor.
	 */
	public function __construct() {
		// Set the api
		$this->apiContext = ApiContext::restore(ApiContext::FILENAME_CONFIG_DEFAULT);
		// Get the userid
		$users = User::listing($this->apiContext)->getValue();
		$user = $users[0]->getUserPerson();
		$this->user = $user;
	}

	/**
	 * @return \bunq\Model\Generated\Endpoint\UserPerson
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * return all the accounts
	 *
	 * @return array
	 */
	public function accounts()
	{
		$list = [];
		$items = MonetaryAccount::listing($this->apiContext, $this->user->getId())->getValue();
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
	public function account($account_id)
	{
		return MonetaryAccount::get($this->apiContext, $this->user->getId(), $account_id)->getValue()->getMonetaryAccountBank();
	}

}

Class payment {

	private $bunqApi;
	public $description, $amount = 0, $from, $to;

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
	 * Create a payment
	 * @return int
	 */
	public function create()
	{
		// Check if the amount is higher than 0
		if(!$this->amount){
			return false;
		}

		// Generate the payment
		$paymentMap = [
			bunqPayment::FIELD_AMOUNT => new Amount((string)$this->amount, 'EUR'),
			bunqPayment::FIELD_COUNTERPARTY_ALIAS => $this->getTo(),
			bunqPayment::FIELD_DESCRIPTION => $this->description,
		];

		// Execute the payment
		return bunqPayment::create($this->bunqApi->apiContext, $paymentMap, $this->bunqApi->user->getId(), $this->getFrom())->getValue();
	}

	/**
	 * @return int
	 */
	private function getFrom()
	{
		if(is_object($this->from) && get_class($this->from) == 'bunq\Model\Generated\Endpoint\MonetaryAccountBank'){
			return $this->from->getId();
		}
		return $this->from;
	}

	/**
	 * @return Pointer
	 */
	private function getTo()
	{
		if(is_array($this->to)) {
			$pointer = new Pointer( $this->to['type'], $this->to['value']);
			if($this->to['type'] == 'IBAN'){
				$pointer->setName($this->to['name']);
			}
			return $pointer;
		}
		if(is_object($this->to) && get_class($this->to == 'bunq\Model\Generated\Endpoint\MonetaryAccountBank')){
			return $this->to->getAlias()[0];
		}
		return $this->bunqApi->account($this->to)->getAlias()[0];
	}
}
