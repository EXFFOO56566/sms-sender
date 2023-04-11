<form method="post" action="<?=base_url()?>smsender/persons/import" id="smsender-import-persons-form" validate-form="true" validation-error="<?=$this->lang->line("please_check_marked_fields")?>" enctype="multipart/form-data" ajax-form="true" callback="smsender.processUploadRequest(response);">
	<input type="hidden" name="task" value="upload" />
	<div class="modal-header">
		<?=$this->lang->line("import_persons_from_excel")?>
	</div>	
	<div class="modal-content">
		<div class="inline-form-row">
			<div class="column-6">
				<label for="file"><?=$this->lang->line("file_to_import")?></label>
			</div>
			<div class="column-6">
				<input class="full-width" type="file" id="file" name="file" required-field="true" validation="[not-empty][extension:xls,xlsx,csv]" />
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
		$this->event->register("SMSenderImportPersonsStep1FormRow");
		?>
		<div class="form-error-handler" error-handler="true"></div>
	</div>	
	<div class="modal-footer">
		<input type="submit" name="upload" value="<?=$this->lang->line("upload")?>" class="button medium-button primary-button" />
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>