<form method="post" action="<?=base_url()?>smsender/groups/create" class="modal-wrapper column-4" validate-form="true" validation-error="<?=$this->lang->line("please_check_marked_fields")?>">
	<div class="modal-header">
		<?=$this->lang->line("create_group")?>
	</div>	
	<div class="modal-content">
		<div class="inline-form-row">
			<div class="column-6">
				<label for="name"><?=$this->lang->line("name")?></label>
			</div>
			<div class="column-6">
				<input type="text" id="name" name="name" class="full-width" required-field="true" validation="[not-empty]" />
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
		$this->event->register("SMSenderGroupCreateFormRow");
		?>
		<div class="form-error-handler" error-handler="true"></div>
	</div>	
	<div class="modal-footer">
		<input type="submit" value="<?=$this->lang->line("create")?>" class="button medium-button primary-button" />
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>