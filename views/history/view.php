<div class="modal-wrapper column-8">
	<div class="modal-header">
		<?=$this->lang->line("view_detailed_info")?>
	</div>	
	<div class="modal-content">
		<div class="inline-form-row">
			<div class="column-6">
				<div class="inline-form-row">
					<div class="column-6">
						<label><?=$this->lang->line("sent")?></label>
					</div>
					<div class="column-6">
						<strong><?=date("d/m/Y H:i:s",strtotime($item->sent))?></strong>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="inline-form-row">
					<div class="column-6">
						<label><?=$this->lang->line("selected_groups")?></label>
					</div>
					<div class="column-6">
						<?php
						foreach($item->detailed_groups as $n=>$group){
							if ($n>0) echo "<br/>";
							if ($this->acl->checkPermissions("smsender","groups","index") && $this->GroupsModel->getItem($group->group_id)!==false) {
							?><a href="<?=base_url()?>smsender/groups/index?filter[name]=<?=$group->name?>&apply_filters=" class="work-table-link"><strong><?=$group->name?></strong></a><?php
							} else echo "<strong>".$group."</strong>";
						}
						if (count($item->detailed_groups)==0) echo "-";
						?>
					</div>
					<div class="clearfix"></div>
				</div>
			</div>
			<div class="clearfix"></div>	
			<div class="inline-form-row">
				<div class="column-3">
					<label><?=$this->lang->line("message")?></label>
				</div>
				<div class="column-9">
					<strong><?=$item->message?></strong>
				</div>
				<div class="clearfix"></div>
			</div>			
			<?php
			$this->event->register("SMSenderHistoryVewFormRow",$item);
			?>			
		</div>
		<h5><?=$this->lang->line("sending_result")?></h5>
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
			<tbody>
				<?php
				foreach($item->detailed_records as $detailed_record) {
				?>
				<tr>
					<td>
						<?php
						if ($this->acl->checkPermissions("smsender","persons","index") && $this->PersonsModel->getItem($detailed_record->person_id)!==false) {
						?>
							<a href="<?=base_url()?>smsender/persons/index?filter[person_id]=<?=$detailed_record->person_id?>&apply_filters=" class="work-table-link"><?=$detailed_record->name?></a>
						<?php
						} else echo $detailed_record->name;
						?>
						<br/>
						<?=$detailed_record->phone?>
						<br/>
						<a href="mailto:<?=$detailed_record->email?>" class="work-table-link"><?=$detailed_record->email?></a>				
					</td>
					<td><?=$detailed_record->message?></td>
					<td><?=date("d/m/Y H:i:s",strtotime($detailed_record->sent))?></td>
					<td><?=$detailed_record->response?></td>
				</tr>
				<?php
				}
				if (count($item->detailed_records)==0) {
				?>
				<tr>
					<td class="no-records-found-row" colspan="4"><?=$this->lang->line("no_records_found")?></td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		<div class="clearfix"></div>
	</div>
	<div class="modal-footer">
		<?php
		if ($this->acl->checkPermissions("smsender","history","delete")) {
		?>
		<a href="<?=base_url()?>smsender/history/delete/<?=$item->id?>" class="button medium-button delete-button popup-action" popup-type="confirmation" popup-message="<?=$this->lang->line("you_really_want_to_delete_record")?>" popup-buttons="confirm:<?=$this->lang->line("yes")?>,close:<?=$this->lang->line("cancel")?>">
			<i class="typcn typcn-trash"></i>
			<?=$this->lang->line("delete_record")?>
		</a>
		<?php
		}
		?>
		<a href="#" class="button medium-button secondary-button close-modal-window">
			<?=$this->lang->line("close")?>
		</a>		
	</div>
</form>