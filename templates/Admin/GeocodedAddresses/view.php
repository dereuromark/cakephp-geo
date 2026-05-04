<?php
/**
 * @var \App\View\AppView $this
 * @var \Geo\Model\Entity\GeocodedAddress $geocodedAddress
 */
$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __d('geo', 'Actions') ?></li>
        <li><?= $this->Html->link(__d('geo', 'Edit Geocoded Address'), ['action' => 'edit', $geocodedAddress->id]) ?> </li>
        <li><?= $this->Form->postButton(__d('geo', 'Delete Geocoded Address'), ['action' => 'delete', $geocodedAddress->id], [
            'class' => 'btn btn-link text-start w-100',
            'form' => [
                'class' => 'd-inline',
                'data-confirm-message' => __d('geo', 'Are you sure you want to delete # {0}?', $geocodedAddress->id),
            ],
        ]) ?> </li>
        <li><?= $this->Html->link(__d('geo', 'List Geocoded Addresses'), ['action' => 'index']) ?> </li>
    </ul>
</nav>
<div class="content action-view view large-9 medium-8 columns col-sm-8 col-xs-12">
    <h1><?= h($geocodedAddress->address) ?></h1>
    <table class="table vertical-table">
        <tr>
            <th><?= __d('geo', 'Formatted Address') ?></th>
            <td><?= h($geocodedAddress->formatted_address) ?></td>
        </tr>
        <tr>
            <th><?= __d('geo', 'Country') ?></th>
            <td><?= h($geocodedAddress->country) ?></td>
        </tr>
        <tr>
            <th><?= __d('geo', 'Coordinates') ?></th>
            <td><?= $geocodedAddress->lat && $geocodedAddress->lng ? $this->Number->format($geocodedAddress->lat) . ' / ' . $this->Number->format($geocodedAddress->lng) : '-' ?></td>
        </tr>
        <tr>
            <th><?= __d('geo', 'Data') ?></th>
            <td><?= $geocodedAddress->data ? '<pre>' . h(print_r($geocodedAddress->data->toArray(), true)) . '</pre>' : '-' ?></td>
        </tr>
		<tr>
			<th><?= __d('geo', 'Created') ?></th>
			<td><?= $this->Time->nice($geocodedAddress->created) ?></td>
		</tr>
    </table>

</div>
<script<?= $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '' ?>>
document.querySelectorAll('form[data-confirm-message]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!confirm(this.dataset.confirmMessage)) {
            e.preventDefault();
        }
    });
});
</script>
