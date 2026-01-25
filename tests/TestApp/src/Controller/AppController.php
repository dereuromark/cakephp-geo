<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
	}

}
