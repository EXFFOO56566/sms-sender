SMSender=function(){

	this.recipients=new Array();
	this.selected_groups=new Array();
	this.form_loader=false;
	this.quene_id=0;
	this.main_request=0;

	this.init=function(){
		this.bindEvents();
		return this;
	}
	
	this.bindEvents=function(){
		$(document).on("change","[import-column-selector]",function(){
			var _val=$(this).val();
			$(this).closest(".define-column-row").find(".smsender-column-extra-option").not("[related-to='"+_val+"']").slideUp(300);
			$(this).closest(".define-column-row").find(".smsender-column-extra-option[related-to='"+_val+"']").slideDown(300);
		});
		$(document).on("change","[destination-selector]",function(){
			setTimeout(function(){
				if (this.checked) {
					var _val=$(this).val();
					$(".smsender-send-to-related").not("[related-to='"+_val+"']").slideUp(300);
					$(".smsender-send-to-related[related-to='"+_val+"']").slideDown(300);
				}
			}.bind(this),1);
		});
		$(document).on("keyup","[smsender-search-input]",function(){
			var _val=$.trim($(this).val()).toLowerCase();
			var _wrp=$(this).closest(".smsender-send-to-related").find(".smsender-recipients-list");
			if (_wrp.length>0) {
				var _no_results=_wrp.find(".smsender-no-repecients");
				var _compare="";
				var _found=0;
				_wrp.find(".smsender-search-row").each(function(){
					if (_val=="") {
						_found++;
						$(this).show();
					} else {
						_compare=$.trim($(this).find("label").html()).toLowerCase();
						if (_compare.indexOf(_val)!=-1) {
							_found++;
							$(this).show();
						} else {
							$(this).hide();
							if ($(this).find("input[type='checkbox']")[0].checked) $(this).find("input[type='checkbox']").trigger("click");
						}
					}
				});
				if (_found>0) {
					_no_results.hide();
				} else {
					_no_results.show();
				}
			}
		});
		$(document).on("change","[smsender-select-item]",function(){
			setTimeout(function(){
				var _total=0;
				var _selected=0;
				$(this).closest(".smsender-recipients-list").find(".smsender-search-row:visible").each(function(){
					_total++;
					if ($(this).find("input[type='checkbox']")[0].checked) _selected++;
				});
				if (_selected>=_total && _total>0) {
					$(this).closest(".smsender-send-to-related").find("[smsender-select-all]").attr("checked","checked");
					$(this).closest(".smsender-send-to-related").find("[smsender-select-all]").parent().addClass("checkbox-wrapper-checked");
				} else {
					$(this).closest(".smsender-send-to-related").find("[smsender-select-all]").removeAttr("checked");
					$(this).closest(".smsender-send-to-related").find("[smsender-select-all]").parent().removeClass("checkbox-wrapper-checked");				
				}
			}.bind(this),1);
		});
		$(document).on("change","[smsender-select-all]",function(){
			setTimeout(function(){
				var _wrp=$(this).closest(".smsender-send-to-related").find(".smsender-recipients-list");			
				if (_wrp.length>0) {
					if (this.checked) {
						_wrp.find(".smsender-search-row:visible").each(function(){
							if (!$(this).find("input[type='checkbox']")[0].checked) $(this).find("input[type='checkbox']").trigger("click");
						});
					} else {
						_wrp.find(".smsender-search-row:visible").each(function(){
							if ($(this).find("input[type='checkbox']")[0].checked) $(this).find("input[type='checkbox']").trigger("click");
						});
					}
				}
			}.bind(this),1);
		});
		$(document).on("click","[smsender-send-button]",function(){
			if (formValidator.validate($("#send-sms-form"))) {
				$("[smsender-send-button]").hide();
				$("[smsender-stop-button]").show();
				var _form_wrapper=$("#send-sms-form").find(".ajax-form-wrapper");
				if (_form_wrapper.length>0) {
					smsender.form_loader=_form_wrapper.find(".ajax-form-loader");
					if (smsender.form_loader.length==0) {
						_form_wrapper.append('<div class="ajax-form-loader"><span class="grey-middle-loader">'+lang['loading']+'</span></div>');
						smsender.form_loader=_form_wrapper.find(".ajax-form-loader");
					}
					smsender.form_loader.fadeTo(300,1);
				}
				smsender.quene_id=0;
				smsender.sendSMS();
			}
		});
		$(document).on("click","[smsender-stop-button]",function(){
			smsender.main_request.abort();
			$("#smsender-send-result-table .smsender-sending-row").remove();
			smsender.form_loader.fadeTo(300,0,function(){
				smsender.form_loader.remove();
			});
			$("[smsender-send-button]").show();
			$("[smsender-stop-button]").hide();
			$("#smsender-send-result-table").append('<tr><td colspan="4" class="no-records-found-row smsender-stopped-row">'+lang['sending_stopped']+'</td></tr>');		
		});
		$(document).on("change","[calculate-total-to-send]",function(){
			setTimeout(function(){
				var total_people_to_send=0,_val;
				$("[calculate-total-to-send]:visible").each(function(){
					if (this.checked) {
						_val=$(this).attr("calculate-total-to-send");
						if (typeof _val=="undefined") _val="0";
						_val=parseFloat(_val);
						total_people_to_send+=_val;
					}
				});
				$("#smsender-total-people-to-send").html(total_people_to_send);
			},310);
		});
		$(document).on("keyup","[smsender-characters-counting]",function(){
			var _count=$(this).val().replace(/\n|\r/gi,"").length;
			$("[smsender-characters-counter='"+$(this).attr("id")+"']").html(_count);
		});
	}
	
	this.sendSMS=function(){
		smsender.selected_groups=new Array();
		$("#smsender-send-result").slideDown(300);
		var _cur_selected_source=$("#send-sms-form").find("input[name='send_to']:checked").val();
		var _recipients=new Array();
		if (_cur_selected_source=="all") _recipients=_all_persons;
		if (_cur_selected_source=="selected_groups") {
			$(".smsender-send-to-related[related-to='selected_groups']").find("input[name='selected_groups[]']:visible").each(function(){
				if (this.checked) {
					var _group_id=parseFloat($(this).val());
					smsender.selected_groups.push(_group_id);
					if (typeof _group_persons[_group_id]!="undefined") {
						for(_i=0;_i<_group_persons[_group_id].length;_i++){
							if (_recipients.indexOf(_group_persons[_group_id][_i])==-1) _recipients.push(_group_persons[_group_id][_i]);
						}
					}
				}
			});			
		}
		if (_cur_selected_source=="selected_persons") {
			$(".smsender-send-to-related[related-to='selected_persons']").find("input[name='selected_persons[]']:visible").each(function(){
				if (this.checked) {
					var _person_id=parseFloat($(this).val());
					if (_recipients.indexOf(_person_id)==-1) _recipients.push(_person_id);
				}
			});			
		}
		$("#smsender-send-result-table").html("");
		if (_recipients.length==0) {
			smsender.form_loader.fadeTo(300,0,function(){
				smsender.form_loader.remove();
			});
			$("[smsender-send-button]").show();
			$("[smsender-stop-button]").hide();
			$("#smsender-send-result-table").append('<tr><td colspan="4" class="no-records-found-row">'+lang['no_recipients']+'</td></tr>');
		} else {
			smsender.recipients=_recipients;
			smsender.message=$("[smsender-message-input]").val();
			this.sendSMSToRecipient(_recipients[0],0);
		}
	}
	
	this.sendSMSToRecipient=function(person_id,offset){
		$("#smsender-send-result-table").append('<tr><td colspan="4" class="no-records-found-row smsender-sending-row">'+lang['sending_sms']+'</td></tr>');
		smsender.main_request=$.post(base_url+"smsender/persons/send",{person_id:person_id,task:"send_to_recipient",message:smsender.message,groups:smsender.selected_groups,quene_id:smsender.quene_id},function(data){
			$("#smsender-send-result-table .smsender-sending-row").remove();
			try {
				data=$.parseJSON(data);
				smsender.quene_id=data.quene_id;
				$("#smsender-send-result-table").append('<tr><td>'+data.person+'</td><td>'+data.message+'</td><td>'+data.sent+'</td><td>'+data.response+'</td></tr>');
			} catch(e) {
				$("#smsender-send-result-table").append('<tr><td colspan="4" class="no-records-found-row">'+lang['error_occred_during_sendig_sms_to']+' '+smsender.getPersonName(person_id)+'</td></tr>');
			}
			if (offset<smsender.recipients.length-1) smsender.sendSMSToRecipient(smsender.recipients[offset+1],offset+1);
			else {
				smsender.form_loader.fadeTo(300,0,function(){
					smsender.form_loader.remove();
				});
				$("[smsender-send-button]").show();
				$("[smsender-stop-button]").hide();				
			}
		});
	}
	
	this.getPersonName=function(person_id){
		var _checkbox=$(".smsender-send-to-related[related-to='selected_persons']").find("input[name='selected_persons[]'][value='"+person_id+"']");
		if (_checkbox.length>0) {
			return _checkbox.closest(".smsender-search-row").find("label").html();
		} else return "";
	}

	this.processUploadRequest=function(data){
		try{
			data=$.parseJSON(data);
		} catch(e) {}
		if (typeof data.result!="undefined") {
			$("#smsender-import-persons-form").find("[error-handler='true']").slideUp(0);
			if (data.result=="error") {
				$("#smsender-import-persons-form").find("[error-handler='true']").html(data.message);
				$("#smsender-import-persons-form").find("[error-handler='true']").slideDown(300);
			}
		} else {
			$("#smsender-import-persons-form").closest(".modal-wrapper").addClass("column-8");
			$("#replacement-import-container").html(data);
		}
	}
	
	this.deleteSelected=function(){
		var _to_delete=new Array();
		$("*[batch-handler='history-row']").each(function(){
			if (this.checked) _to_delete.push($(this).val());
		});
		if (_to_delete.length>0){
			$("body").prepend('<form method="post" action="'+base_url+'smsender/history/batchdelete" id="delete-selected-form"></form>');
			for(var i=0;i<_to_delete.length;i++){
				$("#delete-selected-form").append('<input type="hidden" name="ids[]" value="'+_to_delete[i]+'" />');
			}
			$("#delete-selected-form").submit();
		}	
	}

	return this.init();

}
var smsender=new SMSender();

formValidator.rules["at-least-one-column-attached"]=function(obj,value,params){
	var _form=$(obj).closest("form");
	if (_form.length>0) {
		var _found_selected=0;
		_form.find("[import-column-selector]").each(function(){
			if ($(this).val()!="") _found_selected++;
		});
		if (_found_selected==0) return false;
	}
	return true;
}

formValidator.rules["at-least-one-selected-recipient"]=function(obj,value,params){
	var _form=$(obj).closest("form");
	if (_form.length>0) {
		var _cur_selected_source=_form.find("input[name='send_to']:checked").val();
		if (_cur_selected_source=="all") return true;
		if (_cur_selected_source=="selected_groups" && $(obj).attr("name")=="selected_groups[]") {
			var _found=false;
			$(".smsender-send-to-related[related-to='selected_groups']").find("input[name='selected_groups[]']").each(function(){
				if (this.checked) _found=true;
			});
			if (_found) return true;
			else return false;
		}
		if (_cur_selected_source=="selected_groups" && $(obj).attr("name")!="selected_groups[]") return true;
		if (_cur_selected_source=="selected_persons" && $(obj).attr("name")=="selected_persons[]") {
			var _found=false;
			$(".smsender-send-to-related[related-to='selected_persons']").find("input[name='selected_persons[]']").each(function(){
				if (this.checked) _found=true;
			});
			if (_found) return true;
			else return false;
		}
		if (_cur_selected_source=="selected_persons" && $(obj).attr("name")!="selected_persons[]") return true;

	}
	return true;
}