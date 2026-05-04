<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\Geo\Model\Entity\GeocodedAddress> $geocodedAddresses
 */
use Cake\Core\Plugin;

$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>

<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills nav-stacked">
        <li class="heading"><?= __d('geo', 'Actions') ?></li>
		<li><?= $this->Html->link(__d('geo', 'Overview'), ['controller' => 'Geo', 'action' => 'index']) ?></li>
        <li><?= $this->Form->postButton(__d('geo', 'Clear empty Geocoded Addresses'), ['action' => 'clearEmpty'], [
            'class' => 'btn btn-link text-start w-100',
            'form' => [
                'class' => 'd-inline',
                'data-confirm-message' => 'Sure?',
            ],
        ]) ?></li>
		<li><?= $this->Form->postButton(__d('geo', 'Clear all Geocoded Addresses'), ['action' => 'clearAll'], [
            'class' => 'btn btn-link text-start w-100',
            'form' => [
                'class' => 'd-inline',
                'data-confirm-message' => 'Sure?',
            ],
        ]) ?></li>
    </ul>
</nav>
<div class="content action-index index large-9 medium-8 columns col-sm-8 col-xs-12">
    <h1><?= __d('geo', 'Geocoded Addresses') ?></h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('address') ?></th>
                <th><?= $this->Paginator->sort('formatted_address') ?></th>
                <th><?= $this->Paginator->sort('country') ?></th>
                <th><?= __d('geo', 'Coordinates') ?></th>
                <th class="actions"><?= __d('geo', 'Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($geocodedAddresses as $geocodedAddress): ?>
            <tr>
                <td><?= h($geocodedAddress->address) ?></td>
                <td><?= h($geocodedAddress->formatted_address) ?></td>
                <td><?= h($geocodedAddress->country) ?></td>
                <td><?= $geocodedAddress->lat && $geocodedAddress->lng ? $this->Number->format($geocodedAddress->lat) . ' / ' . $this->Number->format($geocodedAddress->lng) : '-' ?></td>
                <td class="actions">
                <?= $this->Html->link(Plugin::isLoaded('Tools') ? $this->Icon->render('view') : __d('geo', 'View'), ['action' => 'view', $geocodedAddress->id], ['escapeTitle' => false]); ?>
                <?= $this->Html->link(Plugin::isLoaded('Tools') ? $this->Icon->render('edit') : __d('geo', 'Edit'), ['action' => 'edit', $geocodedAddress->id], ['escapeTitle' => false]); ?>
                <?= $this->Form->postButton(Plugin::isLoaded('Tools') ? $this->Icon->render('delete') : __d('geo', 'Delete'), ['action' => 'delete', $geocodedAddress->id], [
                    'escapeTitle' => false,
                    'class' => 'btn btn-link p-0 align-baseline',
                    'form' => [
                        'class' => 'd-inline',
                        'data-confirm-message' => __d('geo', 'Are you sure you want to delete # {0}?', $geocodedAddress->id),
                    ],
                ]); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php echo Plugin::isLoaded('Tools') ? $this->element('Tools.pagination') : $this->element('pagination'); ?>
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
