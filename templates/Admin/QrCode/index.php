<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $result
 * @var array $options
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills flex-column">
        <li class="nav-item heading"><?= __('Actions') ?></li>
        <li class="nav-item">
        </li>
    </ul>
</nav>
<div class="qr-code index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __('QR Codes') ?></h2>

	<p>By default, we render a simple SVG image that can be scaled up/down easily.</p>

	<div class="col-md-6" style="margin-bottom: 16px;">
		<?php
		if ($result) {
			echo '<h4>Result</h4>';
			echo $this->QrCode->image($result, $options);
		}
		?>
	</div>

	<h4>Generate QR Code</h4>

	<?php echo $this->Form->create();?>
	<p>Enter some text (URL, ...)</p>

	<?php
	echo $this->Form->control('content', ['autocomplete' => 'off', 'type' => 'textarea']);
	?>
	<div class="col-md-offset-2 col-md-6">
		<?php echo $this->Form->button(__('Go'), ['class' => 'btn btn-success']);?>
	</div>
	<?php echo $this->Form->end(); ?>


</div>
