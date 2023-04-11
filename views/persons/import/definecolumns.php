<form method="post" action="<?=base_url()?>smsender/persons/import" validate-form="true" validation-error="<?=$this->lang->line("at_least_one_column_should_be_attached")?>">
	<input type="hidden" name="task" value="import" />
	<input type="hidden" name="file" value="<?=$file?>" />
	<div class="modal-header">
		<?=$this->lang->line("set_import_relations")?>
	</div>	
	<div class="modal-content">
		<div class="inline-form-row define-column-row">
			<div class="column-4 smsender-define-column-cell">
				<h5><?=$this->lang->line("excel_column")?></h5>
			</div>
			<div class="column-4 smsender-define-column-cell">
				<h5><?=$this->lang->line("smsender_data")?></h5>
			</div>
			<div class="column-4 smsender-define-column-cell">
				<h5><?=$this->lang->line("extra_options")?></h5>
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
		foreach($rows[0] as $c=>$cell) {
		?>
		<div class="inline-form-row define-column-row">
			<div class="column-4 smsender-define-column-cell">
				<?=$cell?>
			</div>
			<div class="column-4 smsender-define-column-cell">
				<select class="full-width" name="columns[<?=$c?>]" required-field="true" validation="[at-least-one-column-attached]" import-column-selector="true">
					<option value="">- <?=$this->lang->line("do_not_import_column")?> -</option>
					<option value="name"><?=$this->lang->line("name")?></option>
					<option value="email"><?=$this->lang->line("email")?></option>
					<option value="phone"><?=$this->lang->line("phone")?></option>
					<option value="groups_names"><?=$this->lang->line("groups_names")?></option>
				</select>
			</div>
			<div class="column-4 smsender-define-column-cell">
				<div class="smsender-column-extra-option" related-to="" style="display:block;">
					- <?=$this->lang->line("no_extra_options")?> -
				</div>
				<div class="smsender-column-extra-option" related-to="name">
					- <?=$this->lang->line("no_extra_options")?> -
				</div>
				<div class="smsender-column-extra-option" related-to="email">
					- <?=$this->lang->line("no_extra_options")?> -
				</div>
				<div class="smsender-column-extra-option" related-to="phone">
					- <?=$this->lang->line("no_extra_options")?> -
				</div>
				<div class="smsender-column-extra-option" related-to="groups_names">
					<div class="inline-form-row">
						<label><?=$this->lang->line("groups_names_delimiter")?></label>
						<input type="text" class="full-width" name="extra_options[groups_delimiter][<?=$c?>]" value=";" />
					</div>
					<div class="inline-form-row">
						<label><?=$this->lang->line("create_new_group_if_not_found")?></label>
						<select class="full-width" name="extra_options[create_new_group][<?=$c?>]">
							<option value="1"><?=$this->lang->line("yes")?></option>
							<option value="0"><?=$this->lang->line("no")?></option>							
						</select>
					</div>					
				</div>
			</div>
			<div class="clearfix"></div>			
		</div>
		<?php
		}
		?>
		<div class="inline-form-row">
			<div class="smsender-horizontal-spacer"></div>
		</div>
		<div class="column-8">
			<div class="inline-form-row">
				<div class="column-6">
					<label for="rows_from"><?=$this->lang->line("import_from_row")?></label>
				</div>
				<div class="column-6">
					<input type="text" id="rows_from" name="rows_from" class="full-width" required-field="true" validation="[limited-number:1:<?=count($rows)?>]" value="2" />
				</div>
				<div class="clearfix"></div>
			</div>		
			<div class="inline-form-row">
				<div class="column-6">
					<label for="rows_to"><?=$this->lang->line("import_to_row")?></label>
				</div>
				<div class="column-6">
					<input type="text" id="rows_to" name="rows_to" class="full-width" required-field="true" validation="[limited-number:1:<?=count($rows)?>]" value="<?=count($rows)?>" />
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="clearfix"></div>	
		<?php
		$this->event->register("SMSenderImportPersonsStep2FormRow");
		?>
		<div class="form-error-handler" error-handler="true"></div>
	</div>	
	<div class="modal-footer">
		<input type="submit" name="import" value="<?=$this->lang->line("import")?>" class="button medium-button primary-button" />
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>