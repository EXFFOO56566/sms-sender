<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class HistoryModel extends CI_Model {

	function __construct() {
		parent::__construct();
		$this->load->database();
	}
    
	function getItems($params=array(),$sorting=array(),$page=-1) {
		$return=array();
		$this->db->select("smsender_history_records.*");
		$this->db->select("GROUP_CONCAT(DISTINCT smsender_history_groups.name SEPARATOR \";\") as groups,GROUP_CONCAT(DISTINCT smsender_history_groups.group_id SEPARATOR \";\") as groups_ids",false);
		$this->db->select("COUNT(DISTINCT(smsender_history_detailed_records.person_id)) as number_of_recipients");
		$this->db->from("smsender_history_records as smsender_history_records");
		$this->db->join("smsender_history_groups as smsender_history_groups","smsender_history_groups.record_id=smsender_history_records.id","left");
		$this->db->join("smsender_history_detailed_records as smsender_history_detailed_records","smsender_history_detailed_records.record_id=smsender_history_records.id","left");
		$this->db->join("smsender_history_groups as smsender_history_groups_filter","smsender_history_groups_filter.record_id=smsender_history_records.id","left");
		$this->db->join("smsender_history_detailed_records as smsender_history_detailed_records_filter","smsender_history_detailed_records_filter.record_id=smsender_history_records.id","left");
		if (isset($params['message'])) {
			if (str_replace(" ","",$params['message'])!="") {
				$this->db->where("smsender_history_records.`message` LIKE '%".$this->db->escape_like_str($params['message'])."%'",NULL,false);	
			}
		}				
		if (isset($params['name'])) {
			if (str_replace(" ","",$params['name'])!="") {
				$this->db->where("smsender_history_detailed_records_filter.`name` LIKE '%".$this->db->escape_like_str($params['name'])."%'",NULL,false);	
			}
		}
		if (isset($params['email'])) {
			if (str_replace(" ","",$params['email'])!="") {
				$this->db->where("smsender_history_detailed_records_filter.`email` LIKE '%".$this->db->escape_like_str($params['email'])."%'",NULL,false);	
			}
		}
		if (isset($params['phone'])) {
			if (str_replace(" ","",$params['phone'])!="") {
				$this->db->where("smsender_history_detailed_records_filter.`phone` LIKE '%".$this->db->escape_like_str($params['phone'])."%'",NULL,false);	
			}
		}
		if (isset($params['group_id'])) {
			if (str_replace(" ","",$params['group_id'])!="") {
				$this->db->where("smsender_history_groups_filter.`group_id`",$params['group_id']);
			}
		}
		if (isset($params['period_from'])) {
			$temp=explode("/",$params['period_from']);
			if (count($temp)==3) {
				$date=$temp[2]."-".$temp[1]."-".$temp[0]." 00:00:00";
				$this->db->where("smsender_history_records.`sent`>='".$date."'",NULL,false);
			}
		}
		if (isset($params['period_to'])) {
			$temp=explode("/",$params['period_to']);
			if (count($temp)==3) {
				$date=$temp[2]."-".$temp[1]."-".$temp[0]." 23:59:59";
				$this->db->where("smsender_history_records.`sent`<='".$date."'",NULL,false);
			}
		}	
		$this->db->group_by("smsender_history_records.id");
		if (isset($sorting['sort-column']) && isset($sorting['sort-direction'])) {
			$this->db->order_by($sorting['sort-column'],$sorting['sort-direction']);
		} else {
			$this->db->order_by("smsender_history_records.sent","desc");
		}
		$this->event->register("BuildSMSenderHistoryQuery");
		$this->total_count=$this->db->get_total_count();
		if ($page!=-1) {
			$this->db->limit($this->pagination->count_per_page,$page*$this->pagination->count_per_page);
		}
		$query=$this->db->get();
		$return=$query->result();
		return $return;
    }
	
	function getItem($item_id){
		$return=false;
		$this->db->select("smsender_history_records.*");
		$this->db->select("GROUP_CONCAT(smsender_history_groups.name SEPARATOR \";\") as groups,GROUP_CONCAT(smsender_history_groups.group_id SEPARATOR \";\") as groups_ids",false);
		$this->db->select("COUNT(DISTINCT(smsender_history_detailed_records.person_id)) as number_of_recipients");
		$this->db->from("smsender_history_records as smsender_history_records");
		$this->db->join("smsender_history_groups as smsender_history_groups","smsender_history_groups.record_id=smsender_history_records.id","left");
		$this->db->join("smsender_history_detailed_records as smsender_history_detailed_records","smsender_history_detailed_records.record_id=smsender_history_records.id","left");
		$this->db->where("smsender_history_records.id",$item_id);		
		$this->event->register("BuildSMSenderHistoryQuery",$item_id);
		$query=$this->db->get();
		$results=$query->result();
		if (count($results)>0) {
			$return=$results[0];
			$this->db->select("*");
			$this->db->from("smsender_history_groups");
			$this->db->where("record_id",$item_id);
			$query=$this->db->get();
			$return->detailed_groups=$query->result();			
			$this->db->select("*");
			$this->db->from("smsender_history_detailed_records");
			$this->db->where("record_id",$item_id);
			$query=$this->db->get();
			$return->detailed_records=$query->result();					
		}
		return $return;
	}
	
	function delete($item_id){
		$this->event->register("BeforeDeleteSMSenderHistory",$item_id);
    	$item=$this->getItem($item_id);		
		$this->db->where("record_id",$item_id);
		$this->db->delete("smsender_history_groups");
		$this->db->where("record_id",$item_id);
		$this->db->delete("smsender_history_detailed_records");
		$this->db->where("id",$item_id);
		$this->db->delete("smsender_history_records");
		$this->event->register("AfterDeleteSMSenderHistory",$item_id);
		$this->SystemLog->write("smsender","history","delete",3,"History record about sending message \"".$item->message."\" has been deleted from SMSender");
		return true;	
	}
	
    function clear(){
    	$this->event->register("BeforeClearSMSenderHistory");
    	$table_prefix=$this->db->dbprefix;
    	$query="TRUNCATE ".$table_prefix."smsender_history_groups";
    	$this->db->query($query);
    	$query="TRUNCATE ".$table_prefix."smsender_history_detailed_records";
    	$this->db->query($query);    	
    	$query="TRUNCATE ".$table_prefix."smsender_history_records";
    	$this->db->query($query);     	
    	$this->event->register("AfterClearSMSenderHistory");
    	$this->SystemLog->write("smsender","history","clear",3,"History of SMS sendings has been cleared in SMSender");
    	return true;
    }
    
    function batchDelete($ids){
    	$this->event->register("BeforeDeleteSMSenderHistoryRecords",$ids);
    	$this->deleted_records=0;
    	for($i=0;$i<count($ids);$i++){
			$this->db->where("record_id",$ids[$i]);
			$this->db->delete("smsender_history_groups");
			$this->db->where("record_id",$ids[$i]);
			$this->db->delete("smsender_history_detailed_records");
			$this->db->where("id",$ids[$i]);
			$this->db->delete("smsender_history_records");    		
    		$this->deleted_records++;
    	}
    	$this->event->register("AfterDeleteSMSenderHistoryRecords",$ids);
    	$this->SystemLog->write("smsender","history","batchdelete",3,$this->deleted_records." history records have been deleted from SMSender");
    	return true;
    }	

}
?>