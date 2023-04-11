<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Persons extends MX_Controller {
	
	var $check_permissions=true;

	public function index(){			
		$this->view_data['page_title']=$this->lang->line("smsender").$this->theme->page_title_delimiter.$this->lang->line("persons");
		$this->load->model('PersonsModel');
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
		$this->view_data['items']=$this->PersonsModel->getItems($params,$sorting,$page);
		$total_items=$this->PersonsModel->total_count;
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
			"filter_action"=>base_url()."smsender/persons/index",
			"submit_button"=>$this->lang->line("apply_filters"),
			"reset_button"=>$this->lang->line("reset_filters"),
			"filter_event"=>"SMSenderPersonsFilterFormRow",
			"elements"=>array(
				array(
					"type"=>"text",
					"name"=>"name",
					"placeholder"=>$this->lang->line("enter_name")
				),
				array(
					"type"=>"text",
					"name"=>"email",
					"placeholder"=>$this->lang->line("enter_email")
				),
				array(
					"type"=>"text",
					"name"=>"phone",
					"placeholder"=>$this->lang->line("enter_phone")
				),
				array(
					"type"=>"select",
					"name"=>"group_id",
					"options"=>$groups_options
				)
			)
		);		
		$this->sidebar->register($sidebar_params);
		$this->load->view('general/header',$this->view_data);
		$this->load->view('persons/index',$this->view_data);
		$this->load->view('general/footer',$this->view_data);
	}	
	
	public function create(){
		$this->load->model('PersonsModel');
		$this->load->model('GroupsModel');
		if ($data=$this->input->post(NULL, TRUE)) {
			if ($this->PersonsModel->create($data)) {
				$this->notifications->setMessage($this->lang->line("person_created_successfully"));
			}
			redirect($_SERVER['HTTP_REFERER']);
		}	
		$this->view_data['all_groups']=$this->GroupsModel->getItems(array(),array("sort-column"=>"smsender_groups.name","sort-direction"=>"asc"));
		$this->load->view('persons/create',$this->view_data);
	}	
	
	public function update(){
		$this->load->model('PersonsModel');
		$this->load->model('GroupsModel');	
		if ($this->uri->segment(4)!==FALSE) {
			if ($data=$this->input->post(NULL, TRUE)) {
				if ($this->PersonsModel->update($data,$this->uri->segment(4))) {
					$this->notifications->setMessage($this->lang->line("person_updated_successfully"));
				}
				redirect($_SERVER['HTTP_REFERER']);
			}		
			$this->view_data['item']=$this->PersonsModel->getItem($this->uri->segment(4));
			$this->view_data['all_groups']=$this->GroupsModel->getItems(array(),array("sort-column"=>"smsender_groups.name","sort-direction"=>"asc"));
			if ($this->view_data['item']===false) {
				$this->load->view('errors/notfound',$this->view_data);
			} else {
				$this->load->view('persons/update',$this->view_data);
			}
		} else {
			$this->load->view('errors/wrongparameters',$this->view_data);
		}
	}	
	
	public function delete(){
		if ($this->uri->segment(4)!==FALSE) {
			$this->load->model('PersonsModel');
			if ($this->PersonsModel->delete($this->uri->segment(4))) {
				$this->notifications->setMessage($this->lang->line("person_deleted_successfully"));
			}			
		} else {
			$this->notifications->setError($this->lang->line("wrong_parameters"));
		}
		redirect($_SERVER['HTTP_REFERER']);
	}	
	
	public function import(){
		$this->load->model('PersonsModel');
		if ($data=$this->input->post(NULL, TRUE)) {
			if (isset($data['task'])) {
				if ($data['task']=="upload") {
					$result=$this->PersonsModel->uploadTemporaryImportFile();
					if ($result->result=="error") {
						echo json_encode($result);
						die();
					} else {
						$file=$result->file;
						$result=$this->PersonsModel->parseImportFile($result->file);
						if (count($result)==0) {
							$result=new stdClass;
							$result->result="error";
							$result->message=$this->lang->line("import_file_is_empty");
							echo json_encode($result);
							die();							
						} else {
							$this->view_data['file']=$file;
							$this->view_data['rows']=$result;
							$this->load->view('persons/import/definecolumns',$this->view_data);
						}
					}
				}
				if ($data['task']=="import") {
					if ($this->PersonsModel->processImportFile($data)) {
						if ($this->PersonsModel->created_items>0) {
							$this->notifications->setMessage($this->PersonsModel->created_items." ".$this->lang->line("n_persons_created_successfully"));
						}
						if ($this->PersonsModel->updated_items>0) {
							$this->notifications->setMessage($this->PersonsModel->updated_items." ".$this->lang->line("n_persons_updated_successfully"));
						}
					}
					redirect($_SERVER['HTTP_REFERER']);
				}
			} else {
				$this->load->view('errors/wrongparameters',$this->view_data);
			}
		} else { 
			$this->load->view('persons/import',$this->view_data);
		}
	}	
	
	public function export(){
		$this->load->model('PersonsModel');
		$this->PersonsModel->exportItems();
	}
	
	public function send(){
		$this->load->model('PersonsModel');
		$this->load->model('GroupsModel');	
		if ($data=$this->input->post(NULL, TRUE)) {
			$result="";
			if ($data['task']=="send_to_recipient") {
				$result=$this->PersonsModel->sendSMS($data);
				if ($result!==false) {
					$result=json_encode($result);
				} else $result="";
			}
			echo $result;
			die();
		}
		$this->view_data['settings']=$this->PersonsModel->getSettings();
		$this->view_data['all_groups']=$this->GroupsModel->getItems(array(),array("sort-column"=>"smsender_groups.name","sort-direction"=>"asc"));
		$this->view_data['all_persons']=$this->PersonsModel->getItems(array(),array("sort-column"=>"smsender_persons.name","sort-direction"=>"asc"));
		$this->load->view('persons/send',$this->view_data);
	}
	
}