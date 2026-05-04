<?php

namespace Geo\Controller\Admin;

use App\Controller\AppController;

/**
 * GeocodedAddresses Controller
 *
 * @property \Geo\Model\Table\GeocodedAddressesTable $GeocodedAddresses
 *
 * @method \Cake\Datasource\ResultSetInterface<\Geo\Model\Entity\GeocodedAddress> paginate(\Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object = null, array $settings = [])
 */
class GeocodedAddressesController extends AppController {

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$geocodedAddresses = $this->paginate($this->GeocodedAddresses);

		$this->set(compact('geocodedAddresses'));
	}

	/**
	 * View method
	 *
	 * @param string|null $id Geocoded Address id.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @return \Cake\Http\Response|null|void
	 */
	public function view($id = null) {
		$geocodedAddress = $this->GeocodedAddresses->get($id);

		$this->set('geocodedAddress', $geocodedAddress);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function clearEmpty() {
		$this->request->allowMethod('post');

		$this->GeocodedAddresses->clearEmpty();

		$this->Flash->success(__d('geo', 'The empty geocoded addresses have been removed from cache.'));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function clearAll() {
		$this->request->allowMethod('post');

		$this->GeocodedAddresses->clearAll();

		$this->Flash->success(__d('geo', 'All geocoded addresses have been removed from cache'));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Geocoded Address id.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null) {
		$geocodedAddress = $this->GeocodedAddresses->get($id);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$geocodedAddress = $this->GeocodedAddresses->patchEntity($geocodedAddress, $this->request->getData());
			if ($this->GeocodedAddresses->save($geocodedAddress)) {
				$this->Flash->success(__d('geo', 'The geocoded address has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__d('geo', 'The geocoded address could not be saved. Please, try again.'));
		}
		$this->set(compact('geocodedAddress'));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Geocoded Address id.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$geocodedAddress = $this->GeocodedAddresses->get($id);
		if ($this->GeocodedAddresses->delete($geocodedAddress)) {
			$this->Flash->success(__d('geo', 'The geocoded address has been deleted.'));
		} else {
			$this->Flash->error(__d('geo', 'The geocoded address could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
