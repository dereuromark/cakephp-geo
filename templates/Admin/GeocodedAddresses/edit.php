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
        <li><?= $this->Form->postButton(
                __d('geo', 'Delete'),
                ['action' => 'delete', $geocodedAddress->id],
                [
                    'class' => 'btn btn-link text-start w-100',
                    'form' => [
                        'class' => 'd-inline',
                        'data-confirm-message' => __d('geo', 'Are you sure you want to delete # {0}?', $geocodedAddress->id),
                    ],
                ]
            )
        ?></li>
        <li><?= $this->Html->link(__d('geo', 'List Geocoded Addresses'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="content action-form form large-9 medium-8 columns col-sm-8 col-xs-12">
	<h1><?= __d('geo', 'Edit Geocoded Address') ?></h1>
    <?= $this->Form->create($geocodedAddress) ?>
    <fieldset>
        <legend><?= __d('geo', 'Edit Geocoded Address') ?></legend>
        <?php
            echo $this->Form->control('address');
            echo $this->Form->control('formatted_address');
            echo $this->Form->control('country');
            echo $this->Form->control('lat');
            echo $this->Form->control('lng');
        ?>
    </fieldset>
    <?= $this->Form->button(__d('geo', 'Submit')) ?>
    <?= $this->Form->end() ?>
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
