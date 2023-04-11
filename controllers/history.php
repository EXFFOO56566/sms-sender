<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class History extends MX_Controller {
	
	var $check_permissions=true;

	public function index(){							
		$this->view_data['page_title']=$this->lang->line("smsender").$this->theme->page_title_delimiter.$this->lang->line("history");
		$params=array();
		$get=$this->input->get(NULL, TRUE, TRUE);
		if (isset($get['apply_filters']) && isset($get['filter'])) {
			$params=$get['filter'];
		}			
		$sorting=array();
		if (isset($get['sort-column']) && @$get['sort-column']!="") {
			$sorting['sort-column']=$get['sort-column'];
			$sorting['sort-direction']="asc";
			if (isset($get['sort-direction'])) {
				if (strtolower($get['sort-direction'])=="asc" || strtolower($get['sort-direction'])=="desc") {
					$sorting['sort-direction']=$get['sort-direction'];
				}
			}
		}	
		$page=0;
		if (isset($get['page'])) {
			if (is_numeric($get['page']) && $get['page']>=0) {
				$page=$get['page'];
			}
		}				
		$this->load->model('HistoryModel');
		$this->load->model('GroupsModel');
		$this->view_data['items']=$this->HistoryModel->getItems($params,$sorting,$page);
		$total_items=$this->HistoryModel->total_count;
		$this->pagination->setNumbers(count($this->view_data['items']),$total_items);
		$groups_options=array(
			array("value"=>"","label"=>$this->lang->line("any_group"))
		);
		$all_groups=$this->GroupsModel->getItems(array(),array("sort-column"=>"smsender_groups.name","sort-direction"=>"asc"));
		foreach($all_groups as $group){
			$groups_options[]=array("value"=>$group->id,"label"=>$group->name);
		}		
		$sidebar_params=array(
			"name"=>"left-sidebar",
			"title"=>$this->lang->line("filters"),
			"position"=>"left",
			"is_filter"=>true,
			"filter_action"=>base_url()."smsender/history/index",
			"submit_button"=>$this->lang->line("apply_filters"),
			"reset_button"=>$this->lang->line("reset_filters"),
			"filter_event"=>"SMSenderHistoryFilterFormRow",
			"elements"=>array(
				array(
					"type"=>"text",
					"name"=>"message",
					"placeholder"=>$this->lang->line("search_message")
				),
				array(
					"type"=>"text",
					"name"=>"name",
					"placeholder"=>$this->lang->line("enter_person_name")
				),
				array(
					"type"=>"text",
					"name"=>"phone",
					"placeholder"=>$this->lang->line("enter_person_phone")
				),
				array(
					"type"=>"text",
					"name"=>"email",
					"placeholder"=>$this->lang->line("enter_person_email")
				),
				array(
					"type"=>"select",
					"name"=>"group_id",
					"options"=>$groups_options
				),
				array(
					"type"=>"datepicker",
					"name"=>"period_from",
					"placeholder"=>$this->lang->line("period_from")
				),
				array(
					"type"=>"datepicker",
					"name"=>"period_to",
					"placeholder"=>$this->lang->line("period_to")
				)
			)
		);
		$this->sidebar->register($sidebar_params);	
		$this->load->view('general/header',$this->view_data);
		$this->load->view('history/index',$this->view_data);
		$this->load->view('general/footer',$this->view_data);
	}
	
	public function delete(){
		$this->load->model('HistoryModel');
		if ($this->uri->segment(4)!==FALSE) {
			if ($this->HistoryModel->delete($this->uri->segment(4))) {
				$this->notifications->setMessage($this->lang->line("history_record_deleted_successfully"));
			}			
		} else {
			$this->notifications->setError($this->lang->line("wrong_parameters"));
		}
		redirect($_SERVER['HTTP_REFERER']);
	}	
	
	public function clear(){
		$this->load->model('HistoryModel');
		$this->HistoryModel->clear();
		$this->notifications->setMessage($this->lang->line("history_has_been_cleared"));
		redirect($_SERVER['HTTP_REFERER']);
	}
	
	public function batchdelete(){
		$this->load->model('HistoryModel');
		if ($data=$this->input->post(NULL, TRUE)) {
			if (isset($data['ids'])) {
				if (count($data['ids'])>0) {
					if ($this->HistoryModel->batchDelete($data['ids'])) {
						$this->notifications->setMessage($this->HistoryModel->deleted_records." ".$this->lang->line("history_records_deleted_successfully"));
					}
					redirect($_SERVER['HTTP_REFERER']);
				} else {
					$this->load->view('errors/wrongparameters',$this->view_data);
				}
			} else {
				$this->load->view('errors/wrongparameters',$this->view_data);
			}
		} else {
			$this->load->view('errors/wrongparameters',$this->view_data);
		}
	}
	
	public function view(){
		$this->load->model('HistoryModel');
		$this->load->model('PersonsModel');
		$this->load->model('GroupsModel');
		$this->view_data['item']=$this->HistoryModel->getItem($this->uri->segment(4));
		if ($this->view_data['item']===false) {
			$this->load->view('errors/notfound',$this->view_data);
		} else {
			$this->load->view('history/view',$this->view_data);
		}
	}
	
}