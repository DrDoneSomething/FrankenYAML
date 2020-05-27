
/** Tasmota list javascript
 ** frankenyaml_javascript.js
 ** by Dr DoneSomething
 **/
 
var tasmota_hostnames = {};
var tasmota_all_click_buttons = [];
var tasmota_exec_queue = [];
var tasmota_queue_timer = false;
var tasmota_queue_spacing = 100;
function tasmota_refresh_list()
{
    if(typeof tasmota_exec_vars == 'undefined')
    {
        js_log("Login Apparently updated, luckily we do not appear to be displaying anything yet");
        return;
    }
    if(typeof tasmota_default_cmnd == 'undefined')
    {
        js_log("Cannot refresh list, tasmota_default_cmnd not found");
        return;
    }
    if(typeof tasmota_scan_mode == 'undefined')
    {
        js_log("Cannot refresh list, tasmota_scan_mode not found");
        return;
        
    }
    if("list_name" in tasmota_exec_vars)
    {
        console.log("Login Change detected, refreshing tasmotas");
        var master_checkbox = document.getElementById("select_all_hostnames_checkbox");
        if(!master_checkbox)
            console.log("Could not update tasmotas, 'select all' checkbox is not found");
        master_checkbox.checked = true;
        set_all_checkboxes_to_me(master_checkbox);
        exec_tasmota_selected(tasmota_default_cmnd,tasmota_scan_mode);
    }
    
}
function tasmota_manual_command(return_id,mode,cmnd)
{
    
    if(!tasmota_exec_vars)
    {
        console.log("ERROR: tasmota_exec_vars not set");
        return false;
    }
    var send_js =js_output=tooltip_output= false;
    
    var return_text = "";
    var gets = Object.assign({},tasmota_exec_vars);
    var exfpath = gets['exfpath'];
    delete gets['exfpath'];
    
    
    
    
    var id_prefix = return_id + "_tasmota_cmnd_";
    
    
    var js_output_id = return_id.replace("__relay__","")+"_td_js_output"
    var textbox = document.getElementById(id_prefix+"text");
    var checkbox = document.getElementById(id_prefix+"checkbox");
    var button = document.getElementById(id_prefix+"button");
    var tooltip = document.getElementById(id_prefix+"tooltip");
    var checkbox_tooltip =document.getElementById(id_prefix+"checkbox_tooltip");
    
    var id_error = " id not found: "+id_prefix;
    var error = false;
    if(!textbox)
        error = id_error+"text";
    if(!checkbox)
        error = id_error+"checkbox";
    if(!button)
        error = id_error+"button";
    if(!tooltip)
        error = id_error+"tooltip";
    if(!checkbox_tooltip)
        error = id_error+"checkbox_tooltip";
    if(error)
    {
        console.log("FAIL: tasmota_manual_command  "+error);
        return false;
    }
    var show_tooltip = true;
    
    switch(mode)
    {
        case 'get_reference_relay':
            if(!checkbox.checked)
            {
                set_element_disabled(button,true);
                return;
            }
            
            if(!textbox.value)
            {
                js_output = "[command blank]";
                break;
            }
            send_js = true;
            gets['is_relay'] = 1;
            gets['cmnd_reference'] = textbox.value;
            set_element_disabled(button,true);
            button.value = "";
            checkbox.checked = false;
            checkbox_tooltip.style.display = "Awaiting relay command parse by server...";
        
        
        break;
        case 'get_reference':
            if(!checkbox.checked)
            {
                set_element_disabled(button,true);
                return;
            }
            
            if(!textbox.value)
            {
                js_output = "[command blank]";
                break;
            }
            send_js = true;
            
            gets['cmnd_reference'] = textbox.value;
            set_element_disabled(button,false);
            button.value = textbox.value;
            checkbox_tooltip.style.display = "none";
        break;
        case 'insert':
            textbox.value = cmnd;
            button.value = cmnd;
            checkbox.checked = true;
            set_element_disabled(button,false);
            checkbox_tooltip.style.display = "none";
        break;
        case 'separate_insert':
        
            var check_keys = ['button','textbox'];
            for(var i in check_keys)
            {
                if(!(check_keys[i] in cmnd))
                {
                    console.log("FAIL: key "+check_keys[i]+" not found in cmnd ");
                    console.log(cmnd);
                    js_output = "[Error]";
                    error = true;
                    break;
                }
            }
            textbox.value = cmnd['textbox'];
            button.value = cmnd['button'];
            checkbox.checked = true;
            set_element_disabled(button,false);
            checkbox_tooltip.style.display = "none";
            
            
        break;
        case 'receive_reference_no_tip':
            show_tooltip = false;
        case 'receive_reference':
            var error = false;
            var return_direct = false;
            if('array' in cmnd)
            {
                return_direct = true;
                var check_keys = ['tooltip','array'];
                for(var i in check_keys)
                {
                    if(!(check_keys[i] in cmnd))
                    {
                        console.log("FAIL: key "+check_keys[i]+" not found in cmnd ");
                        console.log(cmnd);
                        js_output = "[Error]";
                        error = true;
                        break;
                    }
                }
                if(error)
                    break;
                var tooltip = cmnd['tooltip'];
                var array = cmnd['array'];
                cmnd = {};
                var count = 0;
                for(var i in array)
                {
                    if(count)
                        tasmota_manual_command(return_id,'receive_reference_no_tip',array[i]);
                    else
                        cmnd = array[i];
                    count++;
                }
                cmnd['tooltip'] = tooltip;
                
            }
            
            var check_keys = ['tooltip','parsed'];
            for(var i in check_keys)
            {
                if(!(check_keys[i] in cmnd))
                {
                    console.log("FAIL: key "+check_keys[i]+" not found in cmnd ");
                    console.log(cmnd);
                    js_output = "[Error]";
                    error = true;
                    break;
                }
            }
            if(error)
                break;
            if(!cmnd['parsed']['found'])
            {
                
                return_text = "No reference found for "+textbox.value;
                break;
                
            }
            if(cmnd['parsed']['general_error'])
            {
                return_text = cmnd['parsed']['general_error'];
                break;
            }
            return_text = cmnd['parsed']['text'];
            if(!return_text)
            {
                return_text = "[Error: no parsed -> text]";
                console.log(return_text);
                console.log(cmnd)
            }
            
        break;
        case 'textbox_update':
            checkbox.checked = false;
            set_element_disabled(button,true);
            checkbox_tooltip.style.display = "none";
            button.value = cmnd;
            checkbox.value = cmnd;
        break;
        default:
        console.log("FAIL: tasmota_manual_command INVALID MODE: "+mode);
        return false;
    }
    if(send_js)
    {
        gets['return_id'] = encodeURIComponent(return_id);
    
        var js_url = exfpath+"?"
        
        for(var i in gets)
        {
            if(!gets[i])
                error += " no "+i+" found in gets";
            js_url+=i+"="+gets[i]+"&";
        }
    
        if(error)
        {
            console.log("FAIL: tasmota_manual_command  "+error);
            return false;
        }
        
        dhtmlLoadScriptAddToQueueNamed(js_url,"Reference "+cmnd);
    }
    if(return_text)
    {
        if(show_tooltip)
            tooltip_output = return_text;
        js_output = return_text;
    }
    if(js_output)
    {
        var formatted = '<hr align="left" width="200"><span class="tasmota_reference_output">';
        formatted += "Reference: "
        formatted += js_output+'</span><hr align="left" width="200">';
        
        var tev = Object.assign({},tasmota_exec_vars);
        if(tev['display_mode']=="short")
            master_dump_append(formatted);
        else
            append_to_inner(js_output_id,formatted);
    }
    if(tooltip_output)
    {
        tooltip.innerHTML = tooltip_output;
    }
    return true;
}

function remove_tasmota(hostname)
{
    var tr_classname = get_tasmota_var(hostname,"tr_classname");
    remove_all_with_classname(tr_classname);
}
function loadScriptAndShowPending(pending_id,scriptURL,name)
{
    dhtmlLoadScriptAddToQueueNamed(scriptURL,name);
    pending_message(pending_id,"Pending ...");
}
function pending_message(pending_id,text)
{
    if(split_ids(pending_id,text,"pending_message"))
        return;
    
    
    if(pending_id.indexOf("_td_js_output")== -1)
        pending_id += "_td_js_output";
        
    var obj = document.getElementById(pending_id);
    if(!obj)
    {
        console.log(pending_id+" not found to place text: "+text);
        return;
    }
    
    animate_result_container(obj);
    pending_backup(obj);
    obj.innerHTML = text;
}
function clear_pending(obj)
{
    if(!obj.Pending)
        return;
    obj.Pending =false;
    obj.innerHTML = obj.backupHTML;
}
function pending_backup(obj)
{
    if(obj.Pending)
        return;
    obj.Pending = true;
    var cur_text = obj.innerHTML;
    obj.backupHTML = cur_text;
    
}
function toggle_backup(obj)
{
    if(obj.backupHTML)
    {
        obj.innerHTML = obj.backupHTML;
        obj.backupHTML ="";
        return true;
    }
    return false;
    
}
function split_id(id)
{
    if(id.indexOf(id_delimiter)== -1)
        return false;
    return id.split(id_delimiter);
}
function split_ids(ids,text,split_mode)
{
    if(Array.isArray(ids))
        var split = ids;
    else
        var split = split_id(ids);
        
    if(!split)
        return false;
    
    switch(split_mode)
    {
        case "return_to_inner":
            for(var i in split)
                return_to_inner(split[i],text);
            break;
        case "append_to_inner":
            for(var i in split)
                append_to_inner(split[i],text);
            break;
        case "pending_message":
            for(var i in split)
                pending_message(split[i],text);
            break;
        default:
            console.log("ERROR IN SPLIT_IDS: FUNCTION mode "+split_mode+" NOT FOUND");
            return false;
            
    }
    return true;
}
function return_to_inner(id,text)
{
    if(split_ids(id,text,"return_to_inner"))
        return;
    var obj = document.getElementById(id);
    if(!obj)
    {
        //console.log(id+" not found to return");
        return;
    }
    animate_result_container(obj);
    
    clear_pending(obj);
    var new_div = document.createElement("div");
    new_div.innerHTML = text;
    new_div.className = "constrain_this";
    var inner_divs = new_div.getElementsByTagName("div");
        
    
    if(inner_divs.length)
        obj.innerHTML = text;
    else
    {
        obj.innerHTML = "";
        obj.appendChild(new_div);
    }
        
}

function append_to_inner(id,text)
{
    if(split_ids(id,text,"append_to_inner"))
        return;
    
    var obj = document.getElementById(id);
    if(!obj)
    {
        //console.log(id+" not found to append");
        return;
    }
    clear_pending(obj);
    animate_result_container(obj);
    var inner_divs = obj.getElementsByTagName("div");
    
    var new_div = document.createElement("div");
    new_div.innerHTML = text;
    new_div.className = "constrain_this";
    
    var input_div_status = text.indexOf("<div")
    if(input_div_status != -1)
    {
        if(input_div_status ==0 || !text.substr(0,input_div_status+1).trim())
        {
            new_div = new_div.getElementsByTagName("div")[0];
            text = new_div.innerHTML;
        }
    }
    // If there exists a div, we will always use that div!
    
    var delimiter = "<br /><br />";
    
    
    var div = false;
    if(inner_divs.length)
        div = inner_divs[0];
        
    if(div)
        div.innerHTML = text+delimiter+div.innerHTML ;
    else
    {
        new_div.innerHTML += delimiter+obj.innerHTML;
        obj.innerHTML = "";
        obj.appendChild(new_div);
    }
    
}
function set_all_checkboxes_to_me(checkbox)
{
    var inputs = document.getElementsByTagName("input");
    
    var className = checkbox.className;
    if(!className)
    {
        console.log("ERROR: class is not defined for checkbox, I am not clicking shit");
        return false;
        
    }
    
    for(var i in inputs)
        if(inputs[i].type == "checkbox" && inputs[i] != checkbox && 
        (!className || inputs[i].classList.contains(className)))
        {
            inputs[i].checked = !checkbox.checked;
            inputs[i].click();
        }
        
}
function exec_tasmota_relays_ip(command_string)
{
    exec_tasmota_relays(command_string,true);
}
function exec_tasmota_relays_hostname(command_string)
{
    exec_tasmota_relays(command_string,false);
    
}
function exec_tasmota_relays(command_string,use_ip)
{
    var placeholder_num = 123456789;
    if(command_string.indexOf(placeholder_num) == -1)
    {
        console.log("ERROR: Could not exec command on relays, placeholder "+placeholder_num+
        " not found in command :"+command_string);
        return false;
    }
    for(var hostname in tasmota_relays)
    {
        var host_relays = tasmota_relays[hostname];
        for(var relay_num in host_relays)
        {
            if(!host_relays[relay_num])
                continue;
                
            var cmnd = command_string.replace(placeholder_num,relay_num);
            
            exec_tasmota_queue(hostname,hostname,cmnd,use_ip);
        }
            
    }
    return true;
}
function exec_tasmota_ip(data_id,return_id,command_string)
{
    return exec_tasmota_queue(data_id,return_id,command_string,true);
}

function exec_tasmota_hostname(data_id,return_id,command_string)
{
    return exec_tasmota_queue(data_id,return_id,command_string,false);
}

function exec_tasmota_selected_hostname(command_string)
{
    exec_tasmota_selected(command_string,false);
}
function exec_tasmota_selected_ip(command_string)
{
    exec_tasmota_selected(command_string,true);
    
}

function exec_tasmota_selected(command_string,use_ip)
{
    for(var hostname in tasmota_hostnames)
    {
        if(!tasmota_hostnames[hostname])
            continue;
            
        exec_tasmota_queue(hostname,hostname,command_string,use_ip);
    }
}

function exec_tasmota_queue(data_id,return_id,command_string,use_ip)
{
    tasmota_exec_queue.push([data_id,return_id,command_string,use_ip]);
    console.log("tasmota_exec_queue: Added cmnd "+command_string+" for  " + data_id);
    exec_tasmota_next(false);
}
function exec_tasmota_next(self_call)
{
    if(!tasmota_exec_queue.length)
    {
        console.log("tasmota_exec_queue finished.");
        tasmota_queue_timer = false;
        return;
    }
    if(tasmota_queue_timer && !self_call)
        return;
    
    tasmota_queue_timer = setTimeout(function() { exec_tasmota_next(true); },tasmota_queue_spacing);
    
    this_command = tasmota_exec_queue.shift();
    exec_tasmota(this_command[0],this_command[1],this_command[2],this_command[3]);

}
function exec_tasmota(data_id,return_id,command_string,use_ip)
{
    //list_name
    //exfpath
    if(!tasmota_exec_vars)
    {
        js_log("ERROR: tasmota_exec_vars not set");
        return false;
    }
    var gets = Object.assign({},tasmota_exec_vars);
    var exfpath = gets['exfpath'];
    delete gets['exfpath'];
    gets['return_id'] = encodeURIComponent(return_id);
    
    //device_username
    //device_password
    //protocol
    if(!tasmota_device_vars)
    {
        console.log("ERROR: tasmota_device_vars not set");
        return false;
    }
    var error = "";
    while(true)
    {
        var username = "";
        var password = "";
        var ip = get_tasmota_var(data_id,"ip_address");
        if(!ip)
            error = "no ip_address in tasmota var ";
        var hostname = get_tasmota_var(data_id,"hostname");
        if(!hostname)
            error = "no hostname_string found in tasmota var ";
            
        
        if('username' in tasmota_device_vars)
            username = encodeURIComponent(tasmota_device_vars['username']);
        else
            error = "no username found in tasmota var ";
        
        if('password' in tasmota_device_vars)
            password = encodeURIComponent(tasmota_device_vars['password']);
        else
            error = "no password found in tasmota var ";
            
        var protocol = tasmota_device_vars['protocol'];
        if(!protocol)
            error = "no protocol found in tasmota var ";
        command_string_encoded = encodeURIComponent(command_string);
        if(!command_string)
            error = "no command_string found in tasmota var ";
        
        var address = (use_ip)?ip:hostname.toLowerCase();
        var login = "";
        if(username)
            login += "&user="+username;
        if(password)
            login += "&password="+password;
        if(!login)
            js_log("Warning: No tasmota login/password set in cookie (or a programming error on my part).")
        var tasmota_url = protocol+"://"+address+"/cm?cmnd="+command_string_encoded+login;
            
        var exec_tasmota = btoa(tasmota_url);
        gets['exec_tasmota'] = exec_tasmota;
        gets['hostname'] = encodeURIComponent(hostname);
        var js_url = exfpath+"?";
        
        for(var i in gets)
        {
            if(!gets[i])
                error += " no "+i+" found in gets";
            js_url+=i+"="+gets[i]+"&";
        }
        //error=tasmota_url;
        if(error)
            break;
        var name = data_id+" cmnd: "+command_string;
        loadScriptAndShowPending(return_id,js_url,name);
        return true;
    }
    js_log("Command to "+return_id+" cmnd: '"+command_string+" data_id "+data_id+" FAILED: "+error);
    return false;
    //
}
// return id should be HOSTNAME.. ish
function get_tasmota_var(return_id,variable)
{
    var id = return_id+"_"+variable+"_JSON";
    var json_div = document.getElementById(id);
    if(!json_div)
    {
        console.log("could not get tasmota variable, id "+id+" not found");
        return false;
    }
    var contents = json_div.innerHTML;
    parsed = JSON.parse(contents);
    return parsed;
}
function click_tasmota_command_buttons(id_suffix)
{
    timeout=0;
    for(var hostname in tasmota_hostnames)
    {
        if(!tasmota_hostnames[hostname])
            continue;
        button = document.getElementById(hostname+id_suffix);
        if(!button)
        {
            console.log(hostname+id_suffix+ " Not found");
        continue;
        }
        tasmota_all_click_buttons.push(button);
        setTimeout(click_next_tasmota_button,timeout);
        timeout += 200;
    }
}
function click_next_tasmota_button()
{
    if(!tasmota_all_click_buttons.length)
    {
        console.log("ERROR: NO BUTTON TO CLICK");
        return;
    }
    this_but = tasmota_all_click_buttons.shift();
    this_but.click();
}
function animate_result_container(obj)
{
    //obj.className='animated_result_container';
    obj.classList.add('animated_result_container');
    obj.classList.add('start');
    setTimeout(function(){
    obj.classList.remove('start')},100);
}