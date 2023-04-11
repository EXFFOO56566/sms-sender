<?php
$total_people_to_send=count($all_persons);
?>
<form method="post" action="<?=base_url()?>smsender/persons/send" class="modal-wrapper column-8" id="send-sms-form" validate-form="true" validation-error="<?=$this->lang->line("please_check_recipients_and_message")?>">
	<div class="modal-header">
		<?=$this->lang->line("send_sms")?>
	</div>	
	<div class="modal-content">
		<?php
		$allow_send=false;
		if ($settings->gateway_url=="" || $settings->request_params=="") {
			if ($this->acl->checkPermissions("settings","listing","update")){
				echo $this->lang->line("message_please_update_smsender_settings");
			} else {
				echo $this->lang->line("message_please_contact_admin_to_update_smsender_settings");
			}
		} elseif (count($all_persons)==0) {
			echo $this->lang->line("message_you_have_no_persons");
		} else {
		$allow_send=true;
		?>
		<div class="ajax-form-wrapper" id="smsender-presend-wrapper">
			<div class="inline-form-row">
				<div class="column-6">
					<div class="inline-form-row">
						<div class="column-6">
							<label><?=$this->lang->line("send_to")?></label>
						</div>
						<div class="column-6">
							<div class="permission-box">
								<input type="radio" name="send_to" value="all" id="send_to_all" checked="checked" destination-selector="true" calculate-total-to-send="<?=$total_people_to_send?>" />
								<label for="send_to_all"><?=$this->lang->line("all")?></label>
							</div>
							<?php
							if (count($all_groups)>0) {
							?>
							<div class="permission-box">
								<input type="radio" name="send_to" value="selected_groups" id="send_to_selected_groups" destination-selector="true" calculate-total-to-send="0" />
								<label for="send_to_selected_groups"><?=$this->lang->line("selected_groups")?></label>
							</div>
							<?php
							}
							?>
							<div class="permission-box">
								<input type="radio" name="send_to" value="selected_persons" id="send_to_selected_persons" destination-selector="true" calculate-total-to-send="0" />
								<label for="send_to_selected_persons"><?=$this->lang->line("selected_persons")?></label>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
				<div class="column-6">
					<div class="smsender-send-to-related" related-to="selected_groups">
						<div class="column-6">
							<label><?=$this->lang->line("select_groups")?></label>
						</div>
						<div class="column-6">
							<div class="smsender-recipients-list">
								<?php
								foreach($all_groups as $group) {
								?>
								<div class="permission-box smsender-search-row">
									<input type="checkbox" name="selected_groups[]" value="<?=$group->id?>" id="group_selector_<?=$group->id?>" smsender-select-item="true" required-field="true" validation="[at-least-one-selected-recipient]" calculate-total-to-send="<?=$group->count_of_persons?>" />
									<label for="group_selector_<?=$group->id?>"><?=$group->name?></label>
								</div>						
								<?php
								}
								?>
								<div class="smsender-no-repecients" <?=count($all_groups)>0?"style=\"display:none;\"":""?>><?=$this->lang->line("no_records_found")?></div>
							</div>
							<div class="inline-form-row">
								<input type="text" class="full-width" placeholder="<?=$this->lang->line("search")?>" smsender-search-input="true" />
							</div>
							<div class="inline-form-row">
								<input type="checkbox" id="select_unselect_groups" smsender-select-all="true" />
								<label for="select_unselect_groups"><?=$this->lang->line("select_unselect_all")?></label>
							</div>					
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="smsender-send-to-related" related-to="selected_persons">
					<div class="column-6">
							<label><?=$this->lang->line("select_persons")?></label>
						</div>
						<div class="column-6">
							<div class="smsender-recipients-list">
								<?php
								foreach($all_persons as $person) {
								?>
								<div class="permission-box smsender-search-row">
									<input type="checkbox" name="selected_persons[]" value="<?=$person->id?>" id="person_selector_<?=$person->id?>" smsender-select-item="true" required-field="true" validation="[at-least-one-selected-recipient]" calculate-total-to-send="1" />
									<label for="person_selector_<?=$person->id?>"><?=$person->name?></label>
								</div>						
								<?php
								}
								?>
								<div class="smsender-no-repecients" <?=count($all_persons)>0?"style=\"display:none;\"":""?>><?=$this->lang->line("no_records_found")?></div>
							</div>
							<div class="inline-form-row">
								<input type="text" class="full-width" placeholder="<?=$this->lang->line("search")?>" smsender-search-input="true" />
							</div>
							<div class="inline-form-row">
								<input type="checkbox" id="select_unselect_persons" smsender-select-all="true" />
								<label for="select_unselect_persons"><?=$this->lang->line("select_unselect_all")?></label>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>			
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="inline-form-row">
				<div class="column-3">
					<label><?=$this->lang->line("total_people_to_send")?></label>
				</div>
				<div class="column-3">
					<strong id="smsender-total-people-to-send"><?=$total_people_to_send?></strong>
				</div>	
				<div class="clearfix"></div>			
			</div>
			<div class="inline-form-row">
				<label><?=$this->lang->line("message")?></label>
			</div>
			<div class="inline-form-row">
				<textarea name="message" required-field="true" validation="[not-empty]" class="full-width" smsender-message-input="true" id="smsedner-message" smsender-characters-counting="true"></textarea>
				<div class="smsender-message-characters-counter">
					<?=$this->lang->line("characters_count")?>:
					<strong smsender-characters-counter="smsedner-message">0</strong>
				</div>
				<div class="smsedner-shortcodes-info"><?=$this->lang->line("message_allowed_message_shortcodes")?></div>
			</div>		
			<?php
			$this->event->register("SMSenderSendSMSFormRow");
			?>			
		</div>
		<div id="smsender-send-result">
			<h5><?=$this->lang->line("sending_result")?></h5>
			<br/>
			<div class="smsender-sending-info-box"><?=$this->lang->line("message_dont_close_window")?></div>
			<br/>
			<table class="work-table" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th><?=$this->lang->line("recipient")?></th>
						<th><?=$this->lang->line("message")?></th>
						<th><?=$this->lang->line("sent")?></th>
						<th class="s-static-hide"><?=$this->lang->line("remote_response")?></th>
					</tr>
				</thead>
				<tbody id="smsender-send-result-table">
					
				</tbody>
			</table>
			<div class="clearfix"></div>
		</div>
		<?php
		}
		?>			
		<div class="form-error-handler" error-handler="true"></div>
	</div>	
	<div class="modal-footer">
		<?php
		if ($allow_send) {
		?>
		<input type="button" value="<?=$this->lang->line("send")?>" class="button medium-button primary-button" smsender-send-button="true" />
		<input type="button" value="<?=$this->lang->line("stop")?>" class="button medium-button delete-button" smsender-stop-button="true" />
		<?php
		}
		?>
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>
<script>
$("[smsender-stop-button]").hide();
var _group_persons={};
var _all_persons=new Array();
<?php
foreach($all_persons as $person) {
?>
_all_persons.push(<?=$person->id?>);
<?php
	$person_groups=array();
	if ($person->person_groups_ids!="") $person_groups=explode(";",$person->person_groups_ids);
	foreach($person_groups as $group) {
		if ($group>0) {
		?>
		if (typeof _group_persons[<?=$group?>]=="undefined") _group_persons[<?=$group?>]=new Array();
		if (_group_persons[<?=$group?>].indexOf(<?=$person->id?>)==-1) _group_persons[<?=$group?>].push(<?=$person->id?>);
		<?php	
		}
	}
}
?>
</script>