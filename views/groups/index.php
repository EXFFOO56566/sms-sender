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
				<li class="active">
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
				<li>
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
			if ($this->acl->checkPermissions("smsender","groups","create")) {
			?>
			<a href="<?=base_url()?>smsender/groups/create" class="button big-button primary-button modal-window">
				<i class="typcn typcn-plus"></i>
				<?=$this->lang->line("create_new_group")?>
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
								?>
								<th sort-column="smsender_groups.name"><?=$this->lang->line("name")?></th>
								<th sort-column="count_of_persons"><?=$this->lang->line("attached_persons")?></th>
								<th sort-column="smsender_groups.created" class="s-static-hide" <?=@$_GET['sort-column']==""?"sort-direction=\"desc\"":""?>><?=$this->lang->line("created")?></th>
								<th sort-column="smsender_groups.updated" class="s-static-hide"><?=$this->lang->line("updated")?></th>
								<?php
								$this->event->register("SMSenderGroupsTableHeading",$columns);
								?>
								<?php
								if ($this->acl->checkPermissions("smsender","groups","update") || $this->acl->checkPermissions("smsender","groups","delete")){
								$columns++
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
								<td class="align-center"><?=$items[$i]->name?></td>
								<td class="align-center">
									<?php
									if ($items[$i]->count_of_persons>0 && $this->acl->checkPermissions("smsender","persons","index")) {
									?>
									<a href="<?=base_url()?>smsender/persons/index?filter[group_id]=<?=$items[$i]->id?>&apply_filters=" class="work-table-link"><?=$items[$i]->count_of_persons?></a>
									<?php
									} else echo $items[$i]->count_of_persons;
									?>
								</td>
								<td class="align-center s-static-hide"><?=$items[$i]->created!="0000-00-00 00:00:00"?date("d/m/Y H:i",strtotime($items[$i]->created)):$this->lang->line("never")?></td>
								<td class="align-center s-static-hide"><?=$items[$i]->updated!="0000-00-00 00:00:00"?date("d/m/Y H:i",strtotime($items[$i]->updated)):$this->lang->line("never")?></td>
								<?php
								$this->event->register("SMSenderGroupsTableRow",$items[$i],$i);
								?>	
								<?php
								if ($this->acl->checkPermissions("smsender","groups","update") || $this->acl->checkPermissions("smsender","groups","delete")){
								?>							
								<td class="align-center">
									<?php
									if ($this->acl->checkPermissions("smsender","groups","update")){
									?>
									<a href="<?=base_url()?>smsender/groups/update/<?=$items[$i]->id?>" class="table-action-button modal-window" tooltip-text="<?=$this->lang->line("edit_group")?>" ><i class="typcn typcn-pencil"></i></a>
									<?php
									}
									?>
									<?php
									if ($this->acl->checkPermissions("smsender","groups","delete")) {
									?>
									<a href="<?=base_url()?>smsender/groups/delete/<?=$items[$i]->id?>" class="table-action-button popup-action" popup-type="confirmation" popup-message="<?=$this->lang->line("you_really_want_to_delete_group")?>" popup-buttons="confirm:<?=$this->lang->line("yes")?>,close:<?=$this->lang->line("cancel")?>" tooltip-text="<?=$this->lang->line("delete_group")?>"><i class="typcn typcn-trash"></i></a>
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