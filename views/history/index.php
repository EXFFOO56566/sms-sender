<script src="<?=base_url()?>modules/smsender/assets/class.SMSender.js" type="text/javascript"></script>
<link href="<?=base_url()?>modules/smsender/assets/SMSender.css" rel="stylesheet" type="text/css" />
<?php
if ($this->theme->direction=="rtl") {
?>
<link href="<?=base_url()?>modules/smsender/assets/SMSender.rtl.css" rel="stylesheet" type="text/css" />
<?php
}
?>
<section class="content">
	<div class="content-inner">
		<div class="tabs-wrapper">
			<ul class="tabs-list" id="tabs-list">
				<?php
				if ($this->acl->checkPermissions("smsender","persons","index")) {
				?>
				<li>
					<a href="<?=base_url()?>smsender/persons/index">
						<?=$this->lang->line("persons")?>
						<?php
						$_section_description=$this->language->getSectionDescription("smsender","persons");
						if ($_section_description!="") {
						?>
						<i class="typcn typcn-info-large" tooltip-text="<?=$_section_description?>"></i>
						<?php
						}
						?>
					</a>
				</li>
				<?php
				}
				?>
				<?php
				if ($this->acl->checkPermissions("smsender","groups","index")) {
				?>
				<li>
					<a href="<?=base_url()?>smsender/groups/index">
						<?=$this->lang->line("groups")?>
						<?php
						$_section_description=$this->language->getSectionDescription("smsender","groups");
						if ($_section_description!="") {
						?>
						<i class="typcn typcn-info-large" tooltip-text="<?=$_section_description?>"></i>
						<?php
						}
						?>
					</a>
				</li>
				<?php
				}
				?>				
				<?php
				if ($this->acl->checkPermissions("smsender","history","index")) {
				?>
				<li class="active">
					<a href="<?=base_url()?>smsender/history/index">
						<?=$this->lang->line("history")?>
						<?php
						$_section_description=$this->language->getSectionDescription("smsender","history");
						if ($_section_description!="") {
						?>
						<i class="typcn typcn-info-large" tooltip-text="<?=$_section_description?>"></i>
						<?php
						}
						?>
					</a>
				</li>
				<?php
				}
				?>
			</ul>
			<script>
			var _tabs=new Tabs("#tabs-list").bindEvents();
			</script>
			<div class="clearfix"></div>
		</div>
		<div class="content-header">
			<?php
			if ($this->acl->checkPermissions("smsender","history","clear")) {
			?>
			<a href="<?=base_url()?>smsender/history/clear" class="button big-button primary-button popup-action" popup-type="confirmation" popup-message="<?=$this->lang->line("you_really_want_to_clear_history")?>" popup-buttons="confirm:<?=$this->lang->line("yes")?>,close:<?=$this->lang->line("cancel")?>">
				<i class="typcn typcn-backspace-outline"></i>
				<?=$this->lang->line("clear_history")?>
			</a>
			<?php
			}
			?>
			<?php
			if ($this->acl->checkPermissions("smsender","history","batchdelete")) {
			?>
			<a href="javascript:smsender.deleteSelected();" class="button big-button primary-button popup-action" popup-type="confirmation" popup-message="<?=$this->lang->line("you_really_want_to_delete_selected_records")?>" popup-buttons="confirm:<?=$this->lang->line("yes")?>,close:<?=$this->lang->line("cancel")?>" id="batch-delete" style="display:none;">
				<i class="typcn typcn-trash"></i>
				<?=$this->lang->line("delete_selected")?>
			</a>
			<?php
			}
			?>			
		</div>	
		<div class="content-body">
			<?php
			$this->sidebar->renderSidebar("left-sidebar");
			?>	
			<div class="content-action bordered-left-sidebar">
				<div class="content-action-inner">
					<div class="content-action-header xs-static-hide"></div>
					<div class="content-action-subheader">

					</div>
					<table class="work-table" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<?php
								$columns=4;
								if ($this->acl->checkPermissions("smsender","history","batchdelete")) {
								$columns++;
								?>
								<th>
									<?php
									if (count($items)>0) {
									?>
									<input type="checkbox" select-all="history-row" tooltip-text="<?=$this->lang->line("select_unselect_all")?>" />
									<?php
									}
									?>
								</th>
								<?php
								}
								?>
								<th sort-column="smsender_history_records.message"><?=$this->lang->line("message")?></th>
								<th sort-column="smsender_history_records.sent" <?=@$_GET['sort-column']==""?"sort-direction=\"desc\"":""?>><?=$this->lang->line("sent")?></th>
								<th sort-column="groups"><?=$this->lang->line("groups")?></th>
								<th sort-column="number_of_recipients"><?=$this->lang->line("number_of_recipients")?></th>
								<?php
								$this->event->register("SMSenderHistoryTableHeading",$columns);
								?>									
								<?php
								if ($this->acl->checkPermissions("smsender","history","delete") || $this->acl->checkPermissions("smsender","history","view")) {
								$columns++;
								?>									
								<th></th>								
								<?php
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							for($i=0;$i<count($items);$i++) {
							?>
							<tr>
								<?php
								if ($this->acl->checkPermissions("smsender","history","batchdelete")) {
								?>
								<td class="align-center">
									<input type="checkbox" select-all-child="history-row" batch-handler="history-row" batch-related="batch-delete" name="ids[]" value="<?=$items[$i]->id?>" />
								</td>
								<?php
								}
								?>							
								<td><?=$items[$i]->message?></td>
								<td class="align-center"><?=date("d/m/Y H:i",strtotime($items[$i]->sent))?></td>
								<td class="align-center">
									<?php
									$groups=array();
									$groups_ids=array();
									if ($items[$i]->groups!="") $groups=explode(";",$items[$i]->groups);
									if ($items[$i]->groups_ids!="") $groups_ids=explode(";",$items[$i]->groups_ids);
									foreach($groups as $n=>$group){
										if ($n>0) echo ", ";
										if ($this->acl->checkPermissions("smsender","groups","index") && $this->GroupsModel->getItem($groups_ids[$n])!==false) {
										?><a href="<?=base_url()?>smsender/groups/index?filter[name]=<?=$group?>&apply_filters=" class="work-table-link"><?=$group?></a><?php
										} else echo $group;										
									}
									if (count($groups)==0) echo "-";
									?>
								</td>
								<td class="align-center"><?=$items[$i]->number_of_recipients?></td>
								<?php
								$this->event->register("SMSenderHistoryTableRow",$items[$i],$i);
								?>									
								<?php
								if ($this->acl->checkPermissions("smsender","history","delete") || $this->acl->checkPermissions("smsender","history","view")) {
								?>								
								<td class="align-center">		
									<?php
									if ($this->acl->checkPermissions("smsender","history","view")) {
									?>
									<a href="<?=base_url()?>smsender/history/view/<?=$items[$i]->id?>" class="table-action-button modal-window" tooltip-text="<?=$this->lang->line("view_detailed_info")?>" ><i class="typcn typcn-eye"></i></a>
									<?php
									}
									?>													
									<?php
									if ($this->acl->checkPermissions("smsender","history","delete")) {
									?>
									<a href="<?=base_url()?>smsender/history/delete/<?=$items[$i]->id?>" class="popup-action table-action-button" popup-type="confirmation" popup-message="<?=$this->lang->line("you_really_want_to_delete_record")?>" popup-buttons="confirm:<?=$this->lang->line("yes")?>,close:<?=$this->lang->line("cancel")?>" tooltip-text="<?=$this->lang->line("delete_record")?>"><i class="typcn typcn-trash"></i></a>							
									<?php
									}
									?>
								</td>								
								<?php
								}
								?>									
							</tr>
							<?php
							}
							if (count($items)==0) {
							?>
							<tr>
								<td class="no-records-found-row" colspan="<?=$columns?>"><?=$this->lang->line("no_records_found")?></td>
							</tr>
							<?php
							}
							?>							
						</tbody>
					</table>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="clearfix"></div>	
			<div class="content-footer">
				<?php
				$this->pagination->drawPagination();
				?>
			</div>						
		</div>		
	</div>
</section>