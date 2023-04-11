<form method="post" action="<?=base_url()?>smsender/persons/update/<?=$item->id?>" class="modal-wrapper column-4" validate-form="true" validation-error="<?=$this->lang->line("please_check_marked_fields")?>">
	<div class="modal-header">
		<?=$this->lang->line("edit_person")?>
	</div>	
	<div class="modal-content">
		<div class="inline-form-row">
			<div class="column-6">
				<label for="name"><?=$this->lang->line("name")?></label>
			</div>
			<div class="column-6">
				<input type="text" id="name" name="name" value="<?=$item->name?>" class="full-width" required-field="true" validation="[not-empty]" />
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="inline-form-row">
			<div class="column-6">
				<label for="email"><?=$this->lang->line("email")?></label>
			</div>
			<div class="column-6">
				<input type="text" id="email" name="email" value="<?=$item->email?>" class="full-width" />
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="inline-form-row">
			<div class="column-6">
				<label for="phone"><?=$this->lang->line("phone")?></label>
			</div>
			<div class="column-6">
				<input type="text" id="phone" name="phone" value="<?=$item->phone?>" class="full-width" required-field="true" validation="[not-empty]" />
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="inline-form-row">
			<div class="column-6">
				<label><?=$this->lang->line("attach_groups")?></label>
			</div>
			<div class="column-6">
				<?php
				$person_groups=array();
				if (trim($item->person_groups_ids)!="") {
					$person_groups=explode(";",$item->person_groups_ids);
				}
				foreach($all_groups as $group){
				?>
				<div class="permission-box">
					<input type="checkbox" name="groups[]" value="<?=$group->id?>" id="group_selector_<?=$group->id?>" <?=in_array($group->id,$person_groups)?"checked=\"checked\"":""?> />
					<label for="group_selector_<?=$group->id?>"><?=$group->name?></lebale>
				</div>
				<?php
				}
				if (count($all_groups)==0) {
				?>
				<div class="permission-box">- <?=$this->lang->line("groups_not_found")?> -</div>
				<?php
				}
				?>
			</div>
			<div class="clearfix"></div>
		</div>		
		<?php
		$this->event->register("SMSenderPersonUpdateFormRow",$item);
		?>		
		<div class="form-error-handler" error-handler="true"></div>
	</div>	
	<div class="modal-footer">
		<input type="submit" value="<?=$this->lang->line("update")?>" class="button medium-button primary-button" />
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>