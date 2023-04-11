<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class GroupsModel extends CI_Model {

	function __construct() {
		parent::__construct();
		$this->load->database();
	}
    
	function getItems($params=array(),$sorting=array(),$page=-1) {
		$return=array();	
		$this->db->select("smsender_groups.*,COUNT(DISTINCT(smsender_persons2groups.person_id)) as count_of_persons");
		$this->db->from("smsender_groups as smsender_groups");
		$this->db->join("smsender_persons2groups as smsender_persons2groups","smsender_persons2groups.group_id=smsender_groups.id","left");		
		if (isset($params['name'])) {
			if (str_replace(" ","",$params['name'])!="") {
				$this->db->where("smsender_groups.`name` LIKE '%".$this->db->escape_like_str($params['name'])."%'",NULL,false);	
			}
		}				
		$this->db->group_by("smsender_groups.id");
		if (isset($sorting['sort-column']) && isset($sorting['sort-direction'])) {
			$this->db->order_by($sorting['sort-column'],$sorting['sort-direction']);
		} else {
			$this->db->order_by("smsender_groups.created","desc");
		}
		$this->event->register("BuildSMSenderGroupsQuery");
		$this->total_count=$this->db->get_total_count();
		if ($page!=-1) {
			$this->db->limit($this->pagination->count_per_page,$page*$this->pagination->count_per_page);
		}
		$query=$this->db->get();
		$return=$query->result();
		return $return;
    }
	
	function create($data,$unsafe=false){
		$this->event->register("BeforeCreateSMSenderGroup",$data);
    	$return=true;
    	if (!$unsafe) {
			$this->db->select("id");
			$this->db->from("smsender_groups");
			$this->db->where("name",$data['name']); 
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['name']."\" ".$this->lang->line("group_already_exists"));
			}	
		}
		if ($return) {
			$insert=array(
				"name"=>$data['name'],
				"created"=>date("Y-m-d H:i:s")
			);
			$this->db->insert("smsender_groups",$insert);	
			$item_id=$this->db->insert_id();
			$this->created_id=$item_id;
			$this->event->register("AfterCreateSMSenderGroup",$data,$item_id);
			$this->SystemLog->write("smsender","groups","create",1,"SMSender group \"".$data['name']."\" has been created in SMSender");
		}
		return $return;		
	}
	
	function getItem($item_id){
		$return=false;
		$this->db->select("smsender_groups.*,COUNT(DISTINCT(smsender_persons2groups.person_id)) as count_of_persons");
		$this->db->from("smsender_groups as smsender_groups");
		$this->db->join("smsender_persons2groups as smsender_persons2groups","smsender_persons2groups.group_id=smsender_groups.id","left");		
		$this->db->where("smsender_groups.id",$item_id);		
		$this->event->register("BuildSMSenderGroupQuery",$item_id);
		$query=$this->db->get();
		$results=$query->result();
		if (count($results)>0) {
			$return=$results[0];
		}
		return $return;
	}
	
	function update($data,$item_id,$unsafe=false){
		$this->event->register("BeforeUpdateSMSenderGroup",$data,$item_id);
    	$return=true;
    	if (!$unsafe) {
			$this->db->select("id");
			$this->db->from("smsender_groups");
			$this->db->where("name",$data['name']); 
			$this->db->where("id !=",$item_id);
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['name']."\" ".$this->lang->line("group_already_exists"));
			}
		}
		if ($return) {		
			$item=$this->getItem($item_id);
			$return=true;	
			$update=array(
				"name"=>$data['name'],
				"updated"=>date("Y-m-d H:i:s")
			);
			$this->db->where("id",$item_id);
			$this->db->update("smsender_groups",$update);
			$this->event->register("AfterUpdateSMSenderGroup",$data,$item_id);
			$this->SystemLog->write("smsender","groups","update",2,"SMSender group \"".$item->name."\" has been updated in SMSender");
		}
		return $return;
	}
	
	function delete($item_id){
		$this->event->register("BeforeDeleteSMSenderGroup",$item_id);
    	$item=$this->getItem($item_id);		
		$this->db->where("id",$item_id);
		$this->db->delete("smsender_groups");
		$this->db->where("group_id",$item_id);
		$this->db->delete("smsender_persons2groups");		
		$this->event->register("AfterDeleteSMSenderGroup",$item_id);
		$this->SystemLog->write("smsender","groups","delete",3,"SMSender group \"".$item->name."\" has been deleted from SMSender");
		return true;	
	}

}
?>