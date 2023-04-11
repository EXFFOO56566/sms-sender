<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SmsenderCatcher {

	private $CI;
	private $_predefined_gateways=array(
		"http://api.clickatell.com/http/sendmsg/"=>"Clickatell",
		"https://rest.nexmo.com/sms/json"=>"Nexmo",
		"https://api.twilio.com/2010-04-01/Accounts/"=>"Twilio"
	);
	private $_predefined_credentials=array(
		"http://api.clickatell.com/http/sendmsg/"=>array("user","password","api_id","from"),
		"https://rest.nexmo.com/sms/json"=>array("api_key","api_secret","from"),
		"https://api.twilio.com/2010-04-01/Accounts/"=>array("account_sid","auth_token","From")
	);
	private $_predefined_request_methods=array(
		"http://api.clickatell.com/http/sendmsg/"=>"get",
		"https://rest.nexmo.com/sms/json"=>"get",
		"https://api.twilio.com/2010-04-01/Accounts/"=>"post"
	);	
	private $_predefined_params=array(
		"http://api.clickatell.com/http/sendmsg/"=>"user=&password=&api_id=&to=[phone]&text=[message]&from=",
		"https://rest.nexmo.com/sms/json"=>"api_key=&api_secret=&text=[message]&to=[phone]&from=&type=unicode",
		"https://api.twilio.com/2010-04-01/Accounts/"=>"account_sid=&auth_token=&From=&Body=[message]&To=[phone]"
	);

	function __construct() {
		$this->CI=& get_instance();
	}
	
	function onBeforeNavigationHTML(){
		if (!class_exists("GlobalSettings")) {
			$this->CI->load->model("settings/GlobalSettings");
		}
		$request_params=$this->CI->GlobalSettings->getValue("smsender","request_params","");
		$gateway_url=$this->CI->GlobalSettings->getValue("smsender","gateway_url","");
		if (preg_match("/username=(.*?)&password=(.*?)&text=\[message\]&to=\[phone\]&from=(.*?)/sui",$request_params) && $gateway_url=="https://rest.nexmo.com/sms/json" && $this->CI->acl->checkPermissions("settings","listing","index")) {
			$this->CI->notifications->setMessage($this->CI->language->getModuleLanguageLine("smsender","message_nexmo_changed_credentials"));	
		}
	}

	public function onRegisterSettings(){
		$this->CI->GlobalSettings->registerSettingsSection("smsender","SMSender");
		$this->CI->GlobalSettings->registerSetting("smsender","gateway_url","Gateway URL","");
		$this->CI->GlobalSettings->registerSetting("smsender","request_params","Request Parameters<br/><small style='color:#999;'>param_1=value_1&amp;param_2=value_2...<br/>Allowed shortcodes: [message] and [phone]</small>","");
		$this->CI->GlobalSettings->registerSetting("smsender","request_method","Request Method","get");
		$this->CI->GlobalSettings->setSettingOptions("smsender","request_method",array("get"=>$this->CI->language->getModuleLanguageLine("smsender","get"),"post"=>$this->CI->language->getModuleLanguageLine("smsender","post")));
	}
	
	public function _checkRestrictedParam($field_name,$gateway){
		$return=false;
		if ($gateway=="https://rest.nexmo.com/sms/json") {
			if ($field_name=="username") $return=true;
			if ($field_name=="password") $return=true;
		}
		return $return;
	}
	
	public function onSettingUpdateFormRow($setting){
		if ($setting->name=="gateway_url" && $setting->section_module=="smsender") {
			echo '
			<script>
			var _predefined_gateways={
			';
			$c=0;
			foreach($this->_predefined_gateways as $url=>$value) {
				echo '"'.$url.'":"'.$value.'"'.($c<count($this->_predefined_gateways)-1?",":"");
				$c++;
			}
			echo '
			};
			var _cur_val=$("#setting_value").val();
			if (typeof _predefined_gateways[_cur_val]=="undefined") {
				$("#setting_value").attr("original-state",_cur_val);
			}
			var _real_row=$("#setting_value").closest(".inline-form-row");
			_real_row.find("label").html("'.$this->CI->language->getModuleLanguageLine("smsender","custom_gateway").'");
			_real_row.attr("id","manual-gateway-row");
			var _options=\'<option value="">- '.$this->CI->language->getModuleLanguageLine("smsender","please_select").' -</option>\';
			for(var _i in _predefined_gateways) {
				_options+=\'<option value="\'+_i+\'">\'+_predefined_gateways[_i]+\'</option>\';
			}
			_options+=\'<option value="other">'.$this->CI->language->getModuleLanguageLine("smsender","other").'</option>\';
			_real_row.before(\'<div class="inline-form-row"><div class="column-6"><label for="predefined_gateway">'.$this->CI->language->getModuleLanguageLine("smsender","select_gateway").'</label></div><div class="column-6"><select id="predefined_gateway" required-field="true" validation="[not-empty]" class="full-width">\'+_options+\'</select></div><div class="clearfix"></div></div>\');
			if (_cur_val=="" || typeof _predefined_gateways[_cur_val]!="undefined") {
				_real_row.hide();
				if (_cur_val!="") {
					$("#predefined_gateway option:selected").removeAttr("selected");
					$("#predefined_gateway option[value=\'"+_cur_val+"\']").attr("selected","selected");
				}
			} else {
				_real_row.show();
				$("#predefined_gateway option:selected").removeAttr("selected");
				$("#predefined_gateway option[value=\'other\']").attr("selected","selected");				
			}
			$(document).on("change","#predefined_gateway",function(){
				var _val=$(this).val();
				if (_val!="other") {
					_real_row.slideUp(300,function(){
						$("#setting_value").val(_val);
					});
				} else {
					_real_row.slideDown(300);
					$("#setting_value").val($("#setting_value").attr("original-state"));
				}
			});
			$(document).on("keyup","#setting_value",function(){
				$(this).attr("original-state",$(this).val());
			});
			</script>
			';
		}
		if ($setting->name=="request_params" && $setting->section_module=="smsender") {
			$predefined_gateway="";
			$parsed_params=$this->_parseParams($setting->value);
			$gateway_url=$this->CI->GlobalSettings->getValue("smsender","gateway_url","");
			if ($gateway_url!="") {
				if (isset($this->_predefined_gateways[$gateway_url])) {
					$predefined_gateway=$gateway_url;
				}
			}
			$credentials_rendered=false;
			if ($predefined_gateway!="") {	
				if (isset($this->_predefined_credentials[$predefined_gateway])) {
					$credentials_rendered=true;
					$added_params=array();
					foreach($this->_predefined_credentials[$predefined_gateway] as $param) {
						$added_params[]=$param;
						$param_title=$this->CI->language->getModuleLanguageLine("smsender","parameter_name_".$param);
						if ($param_title=="") $param_title=ucfirst($param);
						echo '
						<div class="inline-form-row" param-row="true">
							<div class="column-6">
								<label for="credential_row_'.$param.'">'.$param_title.'</label>
								<input type="hidden" param-field="true" value="'.$param.'" />
							</div>
							<div class="column-6">
								<input type="text" class="full-width" param-value="true" id="credential_row_'.$param.'" value="'.htmlspecialchars(@$parsed_params[$param]).'" />
							</div>
							<div class="clearfix"></div>
						</div>
						';						
					}
					foreach($parsed_params as $field=>$value) {
						if (!in_array($field,$added_params) && !$this->_checkRestrictedParam($field,$predefined_gateway)) {
							echo '
							<div style="display:none;" param-row="true">
								<input type="hidden" param-field="true" value="'.htmlspecialchars($field).'" />
								<input type="hidden" param-value="true" value="'.htmlspecialchars($value).'" />
							</div>
							';
						}
					}
					echo '
					<script>
					$("[name=\'setting_value\']:first").closest(".inline-form-row").hide();
					$("[name=\'setting_value\']:first").closest(".inline-form-row").attr("id","hidden-setting-value-row");
					$("#hidden-setting-value-row").prev().find("div:eq(1)").html(\'<label><strong>'.$this->CI->language->getModuleLanguageLine("smsender","credentials").'</strong></label>\');
					$("[param-field]").bind("keyup",function(){smsender_complie_params_string();});
					$("[param-value]").bind("keyup",function(){smsender_complie_params_string();});
					function smsender_complie_params_string(){
						var _params_string="";
						$("[param-row]").each(function(){
							var _field=$(this).find("[param-field]").length>0?$(this).find("[param-field]").val():"";
							if (typeof _field=="undefined") _field="";
							var _value=$(this).find("[param-value]").length>0?$.trim($(this).find("[param-value]").val()):"";
							if (typeof _value=="undefined") _value="";							
							if (_field!="") {
								_params_string+=(_params_string==""?"":"&")+_field+"="+_value;
							}
						});
						$("[name=\'setting_value\']:first").val(_params_string);
					}
					</script>
					';
				}
			}
			if (!$credentials_rendered) {
				echo '
				<style>
				[param-row] .table-action-button i{line-height:14px;}
				[param-row] .table-action-button{float:right;}
				</style>
				<div class="inline-form-row">
					<div class="column-5 margin-right-1">
						<h4>'.$this->CI->language->getModuleLanguageLine("smsender","field").'</h4>
					</div>
					<div class="column-5">
						<h4>'.$this->CI->language->getModuleLanguageLine("smsender","value").'</h4>
					</div>
					<div class="clearfix"></div>
				</div>
				';					
				foreach($parsed_params as $param=>$value){
					$param_title=$this->CI->language->getModuleLanguageLine("smsender","parameter_name_".$param);
					if ($param_title=="") $param_title=ucfirst($param);
					echo '
					<div class="inline-form-row" param-row="true">
						<div class="column-5 margin-right-1">
							<input type="text" class="full-width" param-field="true" value="'.htmlspecialchars($param).'" onkeyup="smsender_complie_params_string();" />
						</div>
						<div class="column-5">
							<input type="text" class="full-width" param-value="true" value="'.htmlspecialchars($value).'" onkeyup="smsender_complie_params_string();" />
						</div>
						<a href="#" onclick="smsender_remove_parameter(this);return false;" class="table-action-button" tooltip-text="'.$this->CI->language->getModuleLanguageLine("smsender","remove_parameter").'"><i class="typcn typcn-trash"></i></a>
						<div class="clearfix"></div>
					</div>
					';						
				}	
				echo '
					<div class="inline-form-row">
						<a href="#" onclick="smsender_add_parameter(this);return false;" class="button medium-button primary-button">+ '.$this->CI->language->getModuleLanguageLine("smsender","add_parameter").'</a>
					</div>
				';
				echo '
				<script>
				$("[name=\'setting_value\']:first").closest(".inline-form-row").hide();
				$("[name=\'setting_value\']:first").closest(".inline-form-row").attr("id","hidden-setting-value-row");
				function smsender_remove_parameter(obj){
					$(obj).closest("[param-row]").slideUp(300,function(){
						$(this).remove();
						smsender_complie_params_string();
					});
				}
				function smsender_add_parameter(obj){
					$(obj).closest(".inline-form-row").before(\'<div class="inline-form-row" param-row="true" style="display:none;"><div class="column-5 margin-right-1"><input type="text" class="full-width" param-field="true" value="" onkeyup="smsender_complie_params_string();" /></div><div class="column-5"><input type="text" class="full-width" param-value="true" value="" onkeyup="smsender_complie_params_string();" /></div><a href="#" onclick="smsender_remove_parameter(this);return false;" class="table-action-button" tooltip-text="'.$this->CI->language->getModuleLanguageLine("smsender","remove_parameter").'"><i class="typcn typcn-trash"></i></a><div class="clearfix"></div></div>\');
					$(obj).closest(".inline-form-row").prev().slideDown(300,function(){
						smsender_complie_params_string();
					});
				}
				function smsender_complie_params_string(){
					var _params_string="";
					$("[param-row]").each(function(){
						var _field=$(this).find("[param-field]").length>0?$(this).find("[param-field]").val():"";
						if (typeof _field=="undefined") _field="";
						var _value=$(this).find("[param-value]").length>0?$(this).find("[param-value]").val():"";
						if (typeof _value=="undefined") _value="";							
						if (_field!="") {
							_params_string+=(_params_string==""?"":"&")+_field+"="+_value;
						}
					});
					$("[name=\'setting_value\']:first").val(_params_string);
				}
				</script>
				';							
			}
		}
	}
	
	public function onSettingsTableRow($setting,$i){
		if ($setting->name=="gateway_url" && $setting->section_module=="smsender") {
			if (isset($this->_predefined_gateways[$setting->value])) {
				echo '
				<script>
					$("td:contains(\''.$setting->value.'\')").html(\''.$this->_predefined_gateways[$setting->value].'\');
				</script>';
			}
		}
		if ($setting->name=="request_params" && $setting->section_module=="smsender") {
			$predefined_gateway="";
			$gateway_url=$this->CI->GlobalSettings->getValue("smsender","gateway_url","");
			if ($gateway_url!="") {
				if (isset($this->_predefined_gateways[$gateway_url])) {
					$predefined_gateway=$gateway_url;
				}
			}
			if ($predefined_gateway!="") {
				$parsed_params=$this->_parseParams($setting->value);
				$params_string="";
				if (isset($this->_predefined_credentials[$predefined_gateway])) {
					foreach($this->_predefined_credentials[$predefined_gateway] as $param) {
						$param_title=$this->CI->language->getModuleLanguageLine("smsender","parameter_name_".$param);
						if ($param_title=="") $param_title=ucfirst($param);
						$params_string.=($params_string==""?"":"<br/>").$param_title.": <strong>".(@$parsed_params[$param]!=""?$parsed_params[$param]:"<spn style=\"color:#FF0000;\">".$this->CI->language->getModuleLanguageLine("smsender","missed")."</span>")."</strong>";
					}
				} else {
					foreach($parsed_params as $param=>$value){
						$param_title=$this->CI->language->getModuleLanguageLine("smsender","parameter_name_".$param);
						if ($param_title=="") $param_title=ucfirst($param);
						$params_string.=($params_string==""?"":"<br/>").$param_title.": <strong>".$value."</strong>";
					}
				}
				echo '
				<script>
					$("td:last").prev("td").html(\''.$this->CI->language->getModuleLanguageLine("smsender","credentials").'\');
					$("td:last").html(\''.addslashes($params_string).'\');
				</script>';
			} else {
				$parsed_params=$this->_parseParams($setting->value);
				$params_string="";
				foreach($parsed_params as $param=>$value){
					$param_title=$param;
					$params_string.=($params_string==""?"":"<br/>").$param_title.": <strong>".$value."</strong>";
				}	
				if ($params_string=="") $params_string="-";
				echo '
				<script>
					$("td:last").html(\''.addslashes($params_string).'\');
				</script>';							
			}
		}
		if ($setting->name=="request_method" && $setting->section_module=="smsender") {
			$predefined_gateway="";
			$gateway_url=$this->CI->GlobalSettings->getValue("smsender","gateway_url","");
			if ($gateway_url!="") {
				if (isset($this->_predefined_gateways[$gateway_url])) {
					$predefined_gateway=$gateway_url;
				}
			}
			if ($predefined_gateway!="") {
				echo '
				<script>
					$("tr:last").hide();
				</script>';					
			}		
		}
	}
	
	protected function _parseParams($input){
		$output=array();
		$temp=explode("&",$input);
		foreach($temp as $param_row){
			$parts=explode("=",$param_row);
			if (count($parts)>1) {
				$field=$parts[0];
				$value="";
				for($i=1;$i<count($parts);$i++) $value.=($value!=""?"=":"").$parts[$i];
				$output[$field]=$value;
			}
		}
		return $output;
	}
	
	public function onBeforeUpdateSetting($data,$item_id){
		$setting=$this->CI->SettingsModel->getItem($item_id);
		if ($setting->name=="gateway_url" && $setting->section_module=="smsender") {
			if ($setting->value!=$data['setting_value']) {
				$params_string="";
				if ($data['setting_value']!="") {
					if (isset($this->_predefined_params[$data['setting_value']])) {
						$params_string=$this->_predefined_params[$data['setting_value']];
					}
				}
				$this->CI->GlobalSettings->forceUpdateSetting("smsender","request_params",$params_string);
			}
		}
	}
	
	public function onAfterUpdateSetting($data,$item_id){
		$setting=$this->CI->SettingsModel->getItem($item_id);
		if ($setting->name=="gateway_url" && $setting->section_module=="smsender") {
			$predefined_gateway="";
			if ($setting->value!="") {
				if (isset($this->_predefined_gateways[$setting->value])) {
					$predefined_gateway=$setting->value;
				}
			}
			if ($predefined_gateway!="") {
				if (isset($this->_predefined_request_methods[$predefined_gateway])) {
					$this->CI->GlobalSettings->forceUpdateSetting("smsender","request_method",$this->_predefined_request_methods[$predefined_gateway]);
				}
			}			
		}
	}
    
}
?>