var silence_warnings = false;
var popup1titletext = "";
var warnings_backup = "";
var tasmota_relays = {};
var dhtmlLoadQueue = [];
var dhtmlLoadSpacingMS = 500;
var dhtmlLoadTaskId = 9000;
var onLoadQueue = [];
var maxDhtmlLoads = 3;
var dhtmlLoopRunning = false;
var dhtml_retry_seconds = 5;

// In the name of all that is holy DO NOT CHANGE THIS
var id_delimiter = "[AND]";
function frankenyaml_onload()
{
    show_warnings();
    jump_to_onload_execute();
    for(var i in onLoadQueue)
        onLoadQueue[i]();
}
function addFunctionToOnload(fun)
{
    onLoadQueue.push(fun);
}
function jump_to_onload_execute()
{
    if(!jump_to_onload)
        return;
    var el = document.getElementById(jump_to_onload);
    if(!el)
    {
        console.log("Cannot jump, element with id " + jump_to_onload + " not found");
        return;
    }
    el.scrollIntoView({behavior: "smooth", block: "start", inline: "nearest"});
    //el.scrollIntoView();
}

function dhtmlLoadScript(url)
{
   var e = document.createElement("script");
   e.src = url;
   e.type="text/javascript";
   document.getElementsByTagName("head")[0].appendChild(e);
}
// 0 - task id
// 1 - url
// 2 - run count
// 3 - timer
// 4 - name
function dhtmlLoadScriptAddToQueue(url)
{
    count = dhtmlLoadQueue.length;
    
    dhtmlLoadQueue.push([0,url,0,false,""]);
    dhtmlLoadScriptNext(0);
}
function dhtmlLoadScriptAddToQueueNamed(url,name)
{
    count = dhtmlLoadQueue.length;
    
    dhtmlLoadQueue.push([0,url,0,false,name]);
    dhtmlLoadScriptNext(0);
}

function dhtmlLoadScriptNext(mode)
{
    // mode = 0 - standart time out with loop IF not already;
    // mode = 1 - standard timeout with loop
    // mode = 2 - no timeout, no loop
    if(dhtmlLoopRunning && mode==0)
        return false;
    dhtmlLoopRunning = true;
    count = dhtmlLoadQueue.length;
    if(!count)
    {
        dhtmlLoopRunning = false;
        console.log("dhtmlLoadScriptNext has finished");
        
        return;
    }
    var lowest_index = -1;
    
    for(var i in dhtmlLoadQueue)
    {
        item = dhtmlLoadQueue[i];
        task_id = item[0];
        url = item[1];
        run_count = item[2];
        name =item[4]?" for "+item[4]: " ";
        if(!task_id)
        {
            dhtmlLoadTaskId++;
            task_id = dhtmlLoadTaskId;
            dhtmlLoadQueue[i][0] = task_id;
        }
        if(maxDhtmlLoads < run_count)
        {
            js_log("URL "+url+name+" loaded "+run_count+" times, removing");
            removeFromDhtmlQueue(task_id);
            continue;
        }
        if(lowest_index == -1 || run_count < dhtmlLoadQueue[lowest_index][2])
        {
            this_name = name;
            this_run_count = run_count;
            this_task_id = task_id;
            this_url = url;
            lowest_index = i;
        }
        
    }
    if(lowest_index == "-1")
    {
        dhtmlLoopRunning = false;
        console.log("dhtmlLoadScriptNext has finished");
        return false;
        
    }
    if(this_run_count == 0)
    {
        if(this_url.indexOf("task_id") != -1)
            console.log("What?? URL already has task id..."+this_url);
        else if(this_url.indexOf("?") != -1)
            dhtmlLoadQueue[lowest_index][1]=this_url = this_url + "&task_id="+this_task_id;
        else
            console.log("url has no query string... "+this_url);
    }
    var retry_seconds = (dhtml_retry_seconds*1000);
    
    if(this_run_count == 0 || mode==2)
    {
        dhtmlLoadQueue[lowest_index][2]++;
        this_run_count = dhtmlLoadQueue[lowest_index][2];
        
            
        run_text = (this_run_count!=1)?" attempt "+this_run_count:" First Attempt ";
        console.log("dhtmlLoadScriptNext connecting for task #"+this_task_id+this_name+ run_text);
        if(this_url.indexOf("?") != -1)
           this_url = this_url + "&attempt_number="+dhtmlLoadQueue[lowest_index][2];
        else
            console.log("url has no query string... "+this_url);
    
        dhtmlLoadScript(this_url);
        
    }
        
    if(this_run_count>1)
    {
        console.log("dhtmlLoadScriptNext "+this_url+" timeout, will retry in "+retry_seconds+" seconds");
    }
    dhtmlTimerSet(this_run_count);
}
function dhtmlTimerSet(this_run_count)
{
    
    var retry_seconds = (dhtml_retry_seconds*1000);
    if(this_run_count==1)
        setTimeout(function () { dhtmlLoadScriptNext(1)},dhtmlLoadSpacingMS);
    else
        setTimeout(function () { dhtmlLoadScriptNext(2)},retry_seconds);
    
}
function setTimer_recheck(task_id)
{
    var found=false;
    
    var retry_seconds = (dhtml_retry_seconds*1000);
    for(var i in dhtmlLoadQueue)
    {
        item = dhtmlLoadQueue[i];
        this_task_id = item[0];
        url = item[1];
        run_count = item[2];
        try {
            if(typeof item[3] == 'undefined') {
            // does not exist
            timer = false;
            }
            else {
            // does exist
            
            timer = item[3];
            }
        } 
        catch (error){ 
            timer = false;
        }
        if(task_id == this_task_id)
        {
            found=true;
            break;
        }
        
    }
    if(found)
    {
        if(timer)
        {
            console.log("Warning: resetting timer for "+url);
            window.clearTimeout(timer);
            
        }
        timer = window.setTimeout('recheckDhtmlTask('+task_id+');',retry_seconds);
        dhtmlLoadQueue[i][3]= timer;
    }
    else
        console.log("error: settimer Task "+task_id+" not found");
}
function recheckDhtmlTask(task_id)
{
    var found=false;
    for(var i in dhtmlLoadQueue)
    {
        item = dhtmlLoadQueue[i];
        this_task_id = item[0];
        url = item[1];
        run_count = item[2];
        if(task_id == this_task_id)
        {
            found=true;
            break;
        }
        
    }
    if(found)
    {
        if(maxDhtmlLoads < run_count)
        {
            console.log("FAIL: URL "+url+" loaded "+run_count+" times, removing");
            removeFromDhtmlQueue(task_id);
            return;
        }
        
    }
    
}
function removeFromDhtmlQueue(task_id)
{
    
    for(var i in dhtmlLoadQueue)
    {
        item = dhtmlLoadQueue[i];
        this_task_id = item[0];
        url = item[1];
        run_count = item[2];
        var name = item[4]?" for "+item[4]:"";
        if(task_id == this_task_id)
        {
            dhtmlLoadQueue.splice(i,1);
            var remaining= dhtmlLoadQueue.length + " remaining.</b>"
            js_log("URL #"+task_id+name+" Responded Successfully. <b>"+remaining);
            return;
        }
        
    }
    console.log("error could not remove from queue: Task "+task_id+" not found");
}
function insert_text_and_disable(target_id,text)
{
    var target_input = document.getElementById(target_id);
    if(!target_input)
    {
        console.log("id "+target_input+" could not be found");
        return;
    }
    
    if(!target_input.readOnly)
        target_input.old_value = target_input.value;
    
    if(!target_input.hasOwnProperty("old_placeholder"))
        target_input.old_placeholder = target_input.placeholder;
        
    if(text)
        target_input.placeholder = target_input.old_placeholder;
    else
        target_input.placeholder = "[DEFAULT]";
        
    target_input.value = text;
    target_input.readOnly = true;
    
}
function restore_text_and_enable(target_id)
{
    var target_input = document.getElementById(target_id);
    if(!target_input)
    {
        console.log("id "+target_input+" could not be found");
        return;
    }
    if(target_input.hasOwnProperty("old_value"))
        target_input.value = target_input.old_value;
    else
        target_input.value = "";
    target_input.readOnly =false;
    
    
    if(target_input.hasOwnProperty("old_placeholder"))
        target_input.placeholder = target_input.old_placeholder;
        
    target_input.focus();
}
function hasOwnProperty(obj, prop) {
    var proto = obj.__proto__ || obj.constructor.prototype;
    return (prop in obj) &&
        (!(prop in proto) || proto[prop] !== obj[prop]);
}
function popup_64(title,encoded_string)
{
    var string = atob(encoded_string);
    if(title)
        popup1titletext = title;
    var popup1message = document.getElementById('popup1message');
    if(!popup1message)
    {
        console.log("cannot show warning, popup1message not found");
        return;
    }
    if(popup1message.innerHTML)
        warnings_backup = popup1message.innerHTML
    popup1message.innerHTML = string;
    show_warnings();
        
    
}
function echo_saved_change_names(select,name)
{
    var type = select.value;
    console.log("Selecting Parse Mode "+type+" for item "+name);
    var hidden_val = document.getElementById(name+"_value");
    
    if(hidden_val)
        hidden_val.name = type+"_value[]";
    else
        console.log(name+"_value not found");
    
    var hidden_key = document.getElementById(name+"_key");
    if(hidden_key)
        hidden_key.name = type+"_key[]";
    else
        console.log(name+"_key not found");
}

function popup_help(title,string)
{
    if(title)
    {
        title = "Help: "+title;
        popup1titletext = title;
    }
    var popup1message = document.getElementById('popup1message');
    if(!popup1message)
    {
        console.log("cannot show warning, popup1message not found");
        return;
    }
    if(popup1message.innerHTML)
        warnings_backup = popup1message.innerHTML
    
    popup1message.innerHTML = string;
    
    show_warnings();
}
function ics_warn(string)
{
    restore_warning_backup();
    var popup1message = document.getElementById('popup1message');
    if(!popup1message)
    {
        console.log("cannot show warning, popup1message not found");
        return;
    }
    
    if(popup1message.innerHTML)
        popup1message.innerHTML = string + "<hr />"+popup1message.innerHTML;
    else
        popup1message.innerHTML = string;
    
    var pcl = document.getElementById("popup_close_link");
    if(pcl)
    {
        // disabled for now
        if(false &&!document.getElementById("popup_silence"))
        {
            var sispan = document.createElement("a");
            sispan.id = "popup_silence";
            sispan.innerHTML = "[Silence Warnings]";
            sispan.href = 'javascript:silence_dem_warnings();';
            pcl.parentNode.appendChild(sispan);
        }
    }
    
    show_warnings();
}
function silence_dem_warnings()
{
    // pulled from pop, so we'll close it :(
    silence_warnings = true;
    collapse_warnings();
}
function show_warnings()
{
    if(silence_warnings)
    return;
    
    var popu = document.getElementById("popup1");
    if(!popu)
    {
        console.log("popup not loaded yet");
        return; // not loaded yet
    }
    
    var pmessage = document.getElementById('popup1message');
    if(!pmessage.innerHTML)
    {
        console.log("No Errors or warnings!");
        return;
    }
    
    var popup1title = document.getElementById('popup1title');
    if(!popup1title)
    {
        console.log("cannot show warning, popup1title not found");
        return;
    }
    if(popup1titletext)
    {
        popup1title.innerHTML = popup1titletext;
        popup1titletext = "Warnings & Messages";
    }
    
    pmessage.style.maxHeight = (window.innerHeight -256) + "px";
    var string = pmessage.innerHTML;
    if(string.trim() !=0)
        popu.className = "overlay_show";
    return;
    
}
function restore_warning_backup()
{
    var popup1message = document.getElementById('popup1message');
    if(!popup1message)
    {
        console.log("cannot show warning, popup1message not found");
        return;
    }
    
    if(warnings_backup)
        popup1message.innerHTML = warnings_backup;
    warnings_backup ="";
}
function collapse_warnings()
{
    restore_warning_backup();
    
    var popu = document.getElementById("popup1");
    if(!popu)
    {
        console.log("popup not loaded yet");
        return; // not loaded yet
    }
    popu.className = "overlay";
    return;
}
// --------------- TASMOTA -----------------
function add_hostname_to_list(hostname,add_or_remove)
{
    tasmota_hostnames[hostname] = add_or_remove;
}
function add_relay_to_list(json_string,add_or_remove)
{
    var jp = JSON.parse(json_string);
    var error ="";
    if(!jp['hostname'])
        error += " hostname";
    if(!jp['relay_number'])
        error += " relay_number";
    if(error)
    {
        console.log("COULD NOT ADD RELAY TO LIST, COULD NOT FIND: "+error);
        return false;
    }
    
    if(!tasmota_relays[jp['hostname']])
        tasmota_relays[jp['hostname']] = {}
        
    tasmota_relays[jp['hostname']][jp['relay_number']] = add_or_remove;
    if(add_or_remove)
        console.log(json_string+" added");
    else
        console.log(json_string+" removed");
        
}
function remove_all_with_classname(className)
{
    var elements = document.getElementsByClassName(className);
    var total = elements.length;
    if(!total)
    {
        console.log("no elements found with className "+className);
        return false;
    }
    while (elements.length)
    {
        var el = elements[0];
        el.remove();
        var elements = document.getElementsByClassName(className);
    }
    console.log("removed "+total+" elements with classname "+className);
    return true;
}
function set_element_disabled(input,state)
{
    var id = "";
    if (typeof input === 'string' || input instanceof String)
    {
        id = input;
        input = document.getElementById(input);
    }
    if(!input)
    {
        console.log(id+" object not found to  disable ");
        return;
    }
    if(state)
        input.style.opacity = .3;
    else
        input.style.opacity = 1;
        
    input.readOnly = state;
    input.disabled = state;
}

function js_log(text)
{
    console.log(text);
    return master_dump_append(text);
    
}
function master_dump_append(text) {
    
    var result = false;
    while(true)
    {
        if(!js_master_dump_id)
            break;
        
        var dump = document.getElementById(js_master_dump_id);
        if(!dump)
            break;
        
        
        result = append_to_inner(js_master_dump_id,"* "+text+"<br />");
        break;
    }
    return result;
}