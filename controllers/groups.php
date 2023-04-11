<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Groups extends MX_Controller {
	
	var $check_permissions=true;

	public function index(){			
		$this->view_data['page_title']=$this->lang->line("smsender").$this->theme->page_title_delimiter.$this->lang->line("groups");
		$this->load->model('GroupsModel');
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
		$this->view_data['items']=$this->GroupsModel->getItems($params,$sorting,$page);
		$total_items=$this->GroupsModel->total_count;
		$this->pagination->setNumbers(count($this->view_data['items']),$total_items);
		$sidebar_params=array(
			"name"=>"left-sidebar",
			"title"=>$this->lang->line("filters"),
			"position"=>"left",
			"is_filter"=>true,
			"filter_action"=>base_url()."smsender/groups/index",
			"submit_button"=>$this->lang->line("apply_filters"),
			"reset_button"=>$this->lang->line("reset_filters"),
			"filter_event"=>"SMSenderGroupsFilterFormRow",
			"elements"=>array(
				array(
					"type"=>"text",
					"name"=>"name",
					"placeholder"=>$this->lang->line("enter_group_name")
				)
			)
		);		
		$this->sidebar->register($sidebar_params);		
		$this->load->view('general/header',$this->view_data);
		$this->load->view('groups/index',$this->view_data);
		$this->load->view('general/footer',$this->view_data);
	}	
	
	public function create(){
		$this->load->model('GroupsModel');	
		if ($data=$this->input->post(NULL, TRUE)) {
			if ($this->GroupsModel->create($data)) {
				$this->notifications->setMessage($this->lang->line("group_created_successfully"));
			}
			redirect($_SERVER['HTTP_REFERER']);
		}	
		$this->load->view('groups/create',$this->view_data);
	}	
	
	public function update(){
		$this->load->model('GroupsModel');
		if ($this->uri->segment(4)!==FALSE) {
			if ($data=$this->input->post(NULL, TRUE)) {
				if ($this->GroupsModel->update($data,$this->uri->segment(4))) {
					$this->notifications->setMessage($this->lang->line("group_updated_successfully"));
				}
				redirect($_SERVER['HTTP_REFERER']);
			}		
			$this->view_data['item']=$this->GroupsModel->getItem($this->uri->segment(4));
			if ($this->view_data['item']===false) {
				$this->load->view('errors/notfound',$this->view_data);
			} else {
				$this->load->view('groups/update',$this->view_data);
			}
		} else {
			$this->load->view('errors/wrongparameters',$this->view_data);
		}
	}	
	
	public function delete(){
		if ($this->uri->segment(4)!==FALSE) {
			$this->load->model('GroupsModel');
			if ($this->GroupsModel->delete($this->uri->segment(4))) {
				$this->notifications->setMessage($this->lang->line("group_deleted_successfully"));
			}			
		} else {
			$this->notifications->setError($this->lang->line("record_not_found"));
		}
		redirect($_SERVER['HTTP_REFERER']);
	}
	
}