<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PersonsModel extends CI_Model {

	var $created_items=0;
	var $updated_items=0;

	function __construct() {
		parent::__construct();
		$this->load->database();
	}
    
	function getItems($params=array(),$sorting=array(),$page=-1) {  		
		$return=array();		
		$this->db->select("smsender_persons.*");
		$this->db->select("GROUP_CONCAT(DISTINCT smsender_groups.name SEPARATOR \";\") as person_groups,GROUP_CONCAT(DISTINCT smsender_groups.id SEPARATOR \";\") as person_groups_ids",false);
		$this->db->from("smsender_persons as smsender_persons");
		$this->db->join("smsender_persons2groups as smsender_persons2groups","smsender_persons2groups.person_id=smsender_persons.id","left");
		$this->db->join("smsender_groups as smsender_groups","smsender_groups.id=smsender_persons2groups.group_id","left");
		$this->db->join("smsender_persons2groups as smsender_persons2groups_filter","smsender_persons2groups_filter.person_id=smsender_persons.id","left");
		if (isset($params['name'])) {
			if (str_replace(" ","",$params['name'])!="") {
				$this->db->where("smsender_persons.`name` LIKE '%".$this->db->escape_like_str($params['name'])."%'",NULL,false);
			}
		}
		if (isset($params['email'])) {
			if (str_replace(" ","",$params['email'])!="") {
				$this->db->where("smsender_persons.`email` LIKE '%".$this->db->escape_like_str($params['email'])."%'",NULL,false);
			}
		}
		if (isset($params['phone'])) {
			if (str_replace(" ","",$params['phone'])!="") {
				$this->db->where("smsender_persons.`phone` LIKE '%".$this->db->escape_like_str($params['phone'])."%'",NULL,false);
			}
		}		
		if (isset($params['group_id'])) {
			if (str_replace(" ","",$params['group_id'])!="") {
				$this->db->where("smsender_persons2groups_filter.group_id",$params['group_id']);
			}
		}	
		if (isset($params['person_id'])) {
			if (str_replace(" ","",$params['person_id'])!="") {
				$this->db->where("smsender_persons.id",$params['person_id']);
			}
		}
		$this->db->group_by("smsender_persons.id");						
		if (isset($sorting['sort-column']) && isset($sorting['sort-direction'])) {
			$this->db->order_by($sorting['sort-column'],$sorting['sort-direction']);
		} else {
			$this->db->order_by("smsender_persons.created","desc");
		}
		$this->event->register("BuildSMSenderPersonsQuery");
		$this->total_count=$this->db->get_total_count();
		if ($page!=-1) {
			$this->db->limit($this->pagination->count_per_page,$page*$this->pagination->count_per_page);
		}
		$query=$this->db->get();
		$return=$query->result();
		return $return;
    }
	
	function create($data,$unsafe=false){
		$this->event->register("BeforeCreateSMSenderPerson",$data);
    	$return=true;
    	if (!$unsafe) {
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("name",$data['name']); 
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['name']."\" ".$this->lang->line("name_already_used"));
			}
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("email",$data['email']); 
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['email']."\" ".$this->lang->line("email_already_used"));
			}	
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("phone",$data['phone']); 
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['phone']."\" ".$this->lang->line("phone_already_used"));
			}
		}
		if ($return) {
			$insert=array(
				"name"=>$data['name'],
				"email"=>$data['email'],
				"phone"=>$data['phone'],
				"created"=>date("Y-m-d H:i:s")
			);
			$this->db->insert("smsender_persons",$insert);	
			$item_id=$this->db->insert_id();
			$this->created_id=$item_id;
			$groups=isset($data['groups'])?$data['groups']:array();
			$this->_update_person_groups($groups,$item_id);
			$this->event->register("AfterCreateSMSenderPerson",$data,$item_id);
			$this->SystemLog->write("smsender","persons","create",1,"Person \"".$data['name']."\" has been created in SMSender");	
		}
		return $return;
	}
	
	protected function _update_person_groups($groups,$item_id){
		if (count($groups)==0) {
			$this->db->where("person_id",$item_id);
			$this->db->delete("smsender_persons2groups");				
		} else {
			$touched_ids=array();
			foreach($groups as $group){
				if ($group>0) {
					$this->db->select("id");
					$this->db->from("smsender_persons2groups");
					$this->db->where("person_id",$item_id);
					$this->db->where("group_id",$group);
					$query=$this->db->get();
					$check_result=$query->result();
					if (count($check_result)==0) {
						$insert=array(
							"person_id"=>$item_id,
							"group_id"=>$group
						);
						$this->db->insert("smsender_persons2groups",$insert);
						$touched_ids[]=$this->db->insert_id();
					} else {
						$touched_ids[]=$check_result[0]->id;
					}
				}
			}
			$this->db->where("person_id",$item_id);
			if (count($touched_ids)>0) $this->db->where("id NOT IN (".implode(",",$touched_ids).")",NULL,false);
			$this->db->delete("smsender_persons2groups");
		}
	}
	
	function getItem($item_id){
		$return=false;
		$this->db->select("smsender_persons.*");
		$this->db->select("GROUP_CONCAT(DISTINCT smsender_groups.name SEPARATOR \";\") as person_groups,GROUP_CONCAT(DISTINCT smsender_groups.id SEPARATOR \";\") as person_groups_ids",false);
		$this->db->from("smsender_persons as smsender_persons");
		$this->db->join("smsender_persons2groups as smsender_persons2groups","smsender_persons2groups.person_id=smsender_persons.id","left");
		$this->db->join("smsender_groups as smsender_groups","smsender_groups.id=smsender_persons2groups.group_id","left");
		$this->db->where("smsender_persons.id",$item_id);
		$this->event->register("BuildSMSenderPersonQuery",$item_id);
    	$query=$this->db->get();
		$result=$query->result();
		if (count($result)>0) {
			$return=$result[0];
		}
		return $return;
	}
	
	function update($data,$item_id,$unsafe=false){
		$this->event->register("BeforeUpdateSMSenderPerson",$data,$item_id);
		$item=$this->getItem($item_id);	
    	$return=true;
    	if (!$unsafe) {
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("name",$data['name']); 
			$this->db->where("id !=",$item_id);
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['name']."\" ".$this->lang->line("name_already_used"));
			}
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("email",$data['email']);
			$this->db->where("id !=",$item_id);
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['email']."\" ".$this->lang->line("email_already_used"));
			}	
			$this->db->select("id");
			$this->db->from("smsender_persons");
			$this->db->where("phone",$data['phone']); 
			$this->db->where("id !=",$item_id);
			$query=$this->db->get();
			$result=$query->result();
			if (count($result)>0) {
				$return=false;
				$this->notifications->setError("\"".$data['phone']."\" ".$this->lang->line("phone_already_used"));
			}
		}
		if ($return) {
			$update=array(
				"name"=>$data['name'],
				"email"=>$data['email'],
				"phone"=>$data['phone'],
				"updated"=>date("Y-m-d H:i:s")
			);	
			$this->db->where("id",$item_id);
			$this->db->update("smsender_persons",$update);	
			$groups=isset($data['groups'])?$data['groups']:array();
			$this->_update_person_groups($groups,$item_id);			
			$this->event->register("AfterUpdateSMSenderPerson",$data,$item_id);	
			$this->SystemLog->write("smsender","persons","update",2,"Person \"".$item->name."\" has been updated in SMSender");
		}
		return $return;
	}

	function delete($item_id){
		$this->event->register("BeforeDeleteSMSenderPerson",$item_id);
		$user=$this->getItem($item_id);	
		$this->db->where("id",$item_id);
		$this->db->delete("smsender_persons");
		$this->db->where("person_id",$item_id);
		$this->db->delete("smsender_persons2groups");			
		$this->event->register("AfterDeleteSMSenderPerson",$item_id);
		$this->SystemLog->write("smsender","persons","delete",3,"Person \"".$user->name."\" has been deleted from SMSender");
		return true;
	}
	
	public function uploadTemporaryImportFile(){
		$return=new stdClass;
		$return->result="error";
		$return->message="";
		$return->file="";
		if (isset($_FILES['file']['name'])) {
			if ($_FILES['file']['name']!="") {
				$dir=dirname(__FILE__)."/../../../uploads/smsender/";
				if(!is_dir($dir)) @mkdir($dir);
				$dir=dirname(__FILE__)."/../../../uploads/smsender/temp/";
				if(!is_dir($dir)) @mkdir($dir);
				if(!is_dir($dir)) $return->message=$this->lang->line("cannot_upload_import_file");
				else {
					$this->load->helper("file");
					@delete_files($dir,true);					
					$ext=strtolower(end(explode(".",$_FILES['file']['name'])));
					$filename=md5(microtime()).".".$ext;
					if (!@copy($_FILES['file']['tmp_name'],$dir.$filename)) {
						$return->message=$this->lang->line("cannot_upload_import_file");
					} else {
						$return->result="ok";
						$return->message="";
						$return->file=$dir.$filename;					
					}
				}
			}
		}
		return $return;
	}
	
	public function parseImportFile($file){
		$ext=strtolower(end(explode(".",$file)));
		ini_set('auto_detect_line_endings',true);
		$return=array();
		if ($ext=="csv") {
			$fh=fopen($file,"r");
			$r=0;
			while(!feof($fh)) {
				$cells=fgetcsv($fh,1000000,";");
				foreach($cells as $cell){
					$cell=iconv("cp1251","utf-8",$cell);
					$cell=trim(preg_replace("/[\n|\r]/sui","",$cell));
					$return[$r][]=$cell;
				}
				$r++;
			}
			fclose($fh);		
		} else {
			include_once(dirname(__FILE__)."/../../../application/third_party/PHPExcel/PHPExcel.php");
			$file_type=PHPExcel_IOFactory::identify($file);
			$file_reader=PHPExcel_IOFactory::createReader($file_type);
			$file_reader->setReadDataOnly(true);
			$file_excel=$file_reader->load($file);
			$file_excel->setActiveSheetIndex(0);
			$worksheet=$file_excel->getActiveSheet();
			$highestRow=$worksheet->getHighestRow();
			$highestColumn=$worksheet->getHighestColumn();
			$highestColumnIndex=PHPExcel_Cell::columnIndexFromString($highestColumn);
			for ($row=1;$row<=$highestRow;$row++) {
				for ($col=0;$col<$highestColumnIndex;$col++) {
					$cell=$worksheet->getCellByColumnAndRow($col,$row);
					$return[$row-1][$col]=trim(preg_replace("/[\n|\r]/sui","",$cell->getValue()));
				}
			}	
		}
		return $return;		
	}
	
	public function processImportFile($data){
		$return=false;
		$this->load->model('GroupsModel');	
		if (file_exists($data['file'])){
			$rows_from=$data['rows_from']-1;
			$rows_to=$data['rows_to']-1;
			if ($rows_to<$rows_from) {
				$temp=$rows_to;
				$rows_to=$rows_from;
				$rows_from=$temp;
			}
			$file_data=$this->parseImportFile($data['file']);
			for($i=$rows_from;$i<=$rows_to;$i++) {
				if (isset($file_data[$i])) {
					$store_person=array(
						"name"=>false,
						"email"=>false,
						"phone"=>false,
						"groups"=>false
					);
					for($c=0;$c<count($file_data[$i]);$c++) {
						if ($data['columns'][$c]=="name") {
							$store_person['name']=trim($file_data[$i][$c]);
						}
						if ($data['columns'][$c]=="email") {
							$store_person['email']=trim($file_data[$i][$c]);
						}
						if ($data['columns'][$c]=="phone") {
							$store_person['phone']=trim($file_data[$i][$c]);
						}
						if ($data['columns'][$c]=="groups_names") {
							$store_person['groups']=array();
							$groups=explode($data['extra_options']['groups_delimiter'][$c],trim($file_data[$i][$c]));
							foreach($groups as $group) {
								$group=trim($group);
								if ($group!="") {
									$this->db->select("id");
									$this->db->from("smsender_groups");
									$this->db->where("name LIKE '".$group."'");
									$query=$this->db->get();
									$group_result=$query->result();									
									if (count($group_result)>0) {
										if (!in_array($group_result[0]->id,$store_person['groups'])) $store_person['groups'][]=$group_result[0]->id;
									} else {
										if ($data['extra_options']['create_new_group'][$c]==1) {
											$group_data=array("name"=>$group);
											$this->GroupsModel->create($group_data,true);
											$group_id=$this->GroupsModel->created_id;
											if (!in_array($group_id,$store_person['groups'])) $store_person['groups'][]=$group_id;
										}
									}
								}
							}
						}
					}
					$person_id=0;
					if ($person_id==0 && $store_person['name']!="") {
						$this->db->select("id");
						$this->db->from("smsender_persons");
						$this->db->where("name LIKE '".$store_person['name']."'");
						$query=$this->db->get();
						$person_result=$query->result();							
						if (count($person_result)>0) $person_id=$person_result[0]->id;
					}
					if ($person_id==0 && $store_person['email']!="") {
						$this->db->select("id");
						$this->db->from("smsender_persons");
						$this->db->where("email LIKE '".$store_person['email']."'");
						$query=$this->db->get();
						$person_result=$query->result();							
						if (count($person_result)>0) $person_id=$person_result[0]->id;
					}					
					if ($person_id==0 && $store_person['phone']!="") {
						$this->db->select("id");
						$this->db->from("smsender_persons");
						$this->db->where("phone LIKE '".$store_person['phone']."'");
						$query=$this->db->get();
						$person_result=$query->result();							
						if (count($person_result)>0) $person_id=$person_result[0]->id;
					}
					if ($person_id==0) {
						if ($store_person['name']===false) $store_person['name']="";
						if ($store_person['email']===false) $store_person['email']="";
						if ($store_person['phone']===false) $store_person['phone']="";
						if ($store_person['groups']===false) $store_person['groups']=array();
						if ($this->create($store_person,true)) {
							$return=true;
							$this->created_items++;
						}
					} else {
						$person=$this->getItem($person_id);
						if ($store_person['name']===false) $store_person['name']=$person->name;
						if ($store_person['email']===false) $store_person['email']=$person->email;
						if ($store_person['phone']===false) $store_person['phone']=$person->phone;
						if ($store_person['groups']===false) {
							if ($person->person_groups_ids=="") $store_person['groups']=array();
							else $store_person['groups']=explode(";",$person->person_groups_ids);				
						}
						if ($this->update($store_person,$person_id,true)) {
							$return=true;
							$this->updated_items++;
						}
					}
				}
			}
			@unlink($data['file']);			
		}
		return $return;
	}
	
	public function exportItems(){
		$items=$this->getItems();
		include_once(dirname(__FILE__)."/../../../application/third_party/PHPExcel/PHPExcel.php");
		$date=date("d/m/Y H:i",time());
		$objPHPExcel=new PHPExcel();
		$boldFont = array(
			'font'=>array(
				'size'=>'12',
				'bold'=>true
			)
		);
		$center = array(
			'alignment'=>array(
				'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical'=>PHPExcel_Style_Alignment::VERTICAL_TOP
			)
		);	
		$left = array(
			'alignment'=>array(
				'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical'=>PHPExcel_Style_Alignment::VERTICAL_TOP
			)
		);			
		$objPHPExcel->getProperties()->setCreator($this->lang->line("smsender"))
					->setLastModifiedBy($this->lang->line("smsender"))
					->setTitle($this->lang->line("persons")." ".$date)
					->setSubject($this->lang->line("persons")." ".$date)
					->setDescription($this->lang->line("persons")." ".$date)
					->setKeywords($this->lang->line("persons")." ".$date)
					->setCategory($this->lang->line("export"));	

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1',$this->lang->line("name"));
		$objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($boldFont)->applyFromArray($left);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);	
		$objPHPExcel->getActiveSheet()->setCellValue('B1',$this->lang->line("email"));
		$objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($boldFont)->applyFromArray($left);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);	
		$objPHPExcel->getActiveSheet()->setCellValue('C1',$this->lang->line("phone"));
		$objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($boldFont)->applyFromArray($left);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);	
		$objPHPExcel->getActiveSheet()->setCellValue('D1',$this->lang->line("groups"));
		$objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($boldFont)->applyFromArray($left);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);			
		$dc=2;
		foreach($items as $item){
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$dc,$item->name);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$dc,$item->email);
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$dc,$item->phone);
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$dc,$item->person_groups);
			$dc++;
		}
		$objPHPExcel->getActiveSheet()->setTitle($this->lang->line("persons"));
		$objPHPExcel->setActiveSheetIndex(0);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$this->lang->line("persons").'-'.date("d.m.y_H.i").'.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;			
	}
	
	public function getSettings(){
		$return=new stdClass;
		$return->gateway_url="";
		$return->request_params="";
		$return->request_method="get";
		if (class_exists("GlobalSettings")) {
			$return->gateway_url=$this->GlobalSettings->getValue("smsender","gateway_url","");
			$return->request_params=$this->GlobalSettings->getValue("smsender","request_params","");
			$return->request_method=$this->GlobalSettings->getValue("smsender","request_method","get");
		}
		return $return;
	}	
	
	public function sendSMS($data){
		$this->load->model('GroupsModel');	
		$return=false;
		$settings=$this->getSettings();
		$phone="";
		$message="";
		$item=$this->getItem($data['person_id']);
		if ($item!==false && $settings->gateway_url!="" && $settings->request_params!="" && in_array($settings->request_method,array("get","post"))) {
			$phone=preg_replace("/[^0-9]/sui","",trim($item->phone));
			$replacements=array(
				"[name]"=>$item->name,
				"[phone]"=>$item->phone,
				"[email]"=>$item->email,
				"[groups_names]"=>str_replace(";",", ",$item->person_groups),
				"[username]"=>$this->session->userdata("user_name")
			);
			$message=str_replace(array("\n","\t","\r")," ",$data['message']);
			$message=preg_replace("/[ ]{2,}/sui"," ",trim($message));
			foreach($replacements as $key=>$value) {
				$message=str_replace($key,$value,$message);
			}
			if ($phone!="" && $message!="") {
				$request_params=$settings->request_params;
				$replacements=array(
					"[phone]"=>urlencode($phone),
					"[message]"=>urlencode($message),
					"[username]"=>urlencode($this->session->userdata("user_name"))
				);
				foreach($replacements as $key=>$value) {
					$request_params=str_replace($key,$value,$request_params);
				}	
				$http_authorization="";
				if (stripos($settings->gateway_url,"api.twilio.com")!==false) {
					parse_str($request_params,$request_params);
					if (isset($request_params['To'])) {
						if (mb_substr($request_params['To'],0,1)!="+") $request_params['To']="+".$request_params['To'];
					}
					if (isset($request_params['From'])) {
						if (mb_substr($request_params['From'],0,1)==" ") $request_params['From']="+".mb_substr($request_params['From'],1);
					}				
					$account_sid=@$request_params['account_sid'];
					$auth_token=@$request_params['auth_token'];
					if (isset($request_params['account_sid'])) unset($request_params['account_sid']);
					if (isset($request_params['auth_token'])) unset($request_params['auth_token']);
					$request_params=http_build_query($request_params);
					$settings->gateway_url.=$account_sid."/Messages.json";
					$http_authorization=$account_sid.":".$auth_token;
				}
				if (stripos($settings->gateway_url,"rest.nexmo.com")!==false) {
					parse_str($request_params,$request_params);
					if (isset($request_params['from'])) {
						if (mb_substr($request_params['from'],0,1)==" ") $request_params['from']=mb_substr($request_params['from'],1);
					}
					$request_params=http_build_query($request_params);
				}
				$curlOptions = array (
					CURLOPT_URL => $settings->gateway_url,
					CURLOPT_VERBOSE => 1,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_USERAGENT => "User-Agent: Mozilla/4.0 (compatible; MSIE 5.01; Widows NT)"
				);
				if ($settings->request_method=="get") {
					$curlOptions[CURLOPT_URL]=$settings->gateway_url."?".$request_params;
				}
				if ($settings->request_method=="post") {
					$curlOptions[CURLOPT_POST]=1;
					$curlOptions[CURLOPT_POSTFIELDS]=$request_params;
				}		
				if ($http_authorization!="") {
					$curlOptions[CURLOPT_USERPWD]=$http_authorization;
				}
				if (stripos($settings->gateway_url,"https://")!==false) {
					$curlOptions[CURLOPT_CAINFO]=dirname(__FILE__)."/../certificate/cacert.pem";
				}
				$ch=curl_init();
				curl_setopt_array($ch,$curlOptions);
				$response=curl_exec($ch);
				if (curl_getinfo($ch,CURLINFO_HTTP_CODE)!=404 && curl_getinfo($ch,CURLINFO_HTTP_CODE)!=500) {
					if ($data['quene_id']==0) {
						$insert=array(
							"message"=>$data['message'],
							"sent"=>date("Y-m-d H:i:s")
						);
						$this->db->insert("smsender_history_records",$insert);
						$quene_id=$this->db->insert_id();
						if (isset($data['groups'])) {
							if (is_array($data['groups'])) {
								foreach($data['groups'] as $group) {
									$real_group=$this->GroupsModel->getItem($group);
									if ($real_group!==false) {
										$insert=array(
											"record_id"=>$quene_id,
											"group_id"=>$real_group->id,
											"name"=>$real_group->name
										);
										$this->db->insert("smsender_history_groups",$insert);
									}
								}
							}
						}
					} else {
						$quene_id=$data['quene_id'];
					}
					$insert=array(
						"record_id"=>$quene_id,
						"person_id"=>$data['person_id'],
						"name"=>$item->name,
						"phone"=>$item->phone,
						"email"=>$item->email,
						"message"=>$message,
						"sent"=>date("Y-m-d H:i:s"),
						"response"=>$response
					);
					$this->db->insert("smsender_history_detailed_records",$insert);
					$return=new stdClass;
					$return->quene_id=$quene_id;
					$return->person=$item->name."<br/>".$item->phone."<br/>".$item->email;
					$return->message=$message;
					$return->sent=date("d/m/Y H:i:s",strtotime($insert['sent']));
					$return->response=mb_strlen($response)>300?mb_substr($response,0,300)."...":$response;
				}
				curl_close($ch);
			}
		}
		return $return;
	}

}
?>