<h1>FrankenYAML 1.0 WITH Tasmota List 1.0!</h1>

By Dr DoneSomething

BSD license

<h2>FrankenYAML</h2>

  - FrankenYAML will take all your .yaml files for HomeAssistant and turn each entry into an Object.
  - Not exciting enough for you? Well you can then add, edit, delete, disable (comment out), and export it, then re-import it!
  - Still not good enough? Well you can export either to one big file (makes the pi boot happier) or to a bunch of tiny files like <a href="https://www.youtube.com/watch?v=lndeybw21PY">Frenck did in his setup.</a>
  - Icing on the cake: You can easily create an account which will automatically save changes for quick editing later.
      - otherwise transfers the data from panel to panel via exported _POST_ values
      - therefore, unless you create an account, your config is stored nowhere but your browser RAM, for better security (don't close that browser window!)
  - <h3><a href="https://drdonesomething.com/FrankenYAML/">Try FrankenYAML Online</a></h3>
      - Note: This version has login disabled and no Tasmota List for obvious reasons.
 
 <h2>Tasmota List</h2>
 
  - Scans your network for tasmota devices via web send!
  - stores them for later
  - accesses by hostname not IP (when loading from stored)
  - sends commands
  - does it in a quick and efficient way
  - easily customizable for people who love to do lots of changes to tasmota devices
  - runs on just a php webserver ( no need for MQTT! )

<h2>Installation</h2>

  - Install a web server of your choice
  - Install php on that server
  - For Windows 10, I use <a href="https://aprelium.com/downloads/">Abyss</a> and used their php install instructions--Free, very stable, fast, etc)
  - put frankenyaml directory in the web directory
  - type the url into your web browser
  - Note: this saves data to text files so it must have permissions!

<h2>Instructions</h2>

  - <a href="https://www.youtube.com/watch?v=4iPefBPq0Wo">FrankenYAML Intro/Basics (video)</a>
  - <a href="https://www.youtube.com/watch?v=-sv9vlIR-7U">Tasmota List Basics, installation, advanced use (video)</a>

<h2>Removal</h2>

  - <u>Tasmota List</u>
    - To remove Tasmota List from FrankenYAML, just go into the frankenyaml/extensions directory and delete:
    - tasmota_list.php
    - tasmota_list.js
    - tasmota_list.css
    - tasmota_functions (directory)
  - <u>FrankenYAML</u>
    - To remove FrankenYAML and Tasmota List, remove the entire frankenyaml directory
    - To remove generated saved data from Tasmota List, remove extensions/tasmota_functions/tasmota_database.txt
    - To remove generated saved data from frankenyaml, remove saved_data directory
    - To remove FrankenYAML but keep Tasmota list: <b>You Can't</b>. Tasmota List requires too much from FrankenYAML
 
<h2>Customization</h2>

  - To modify the built-in button commands and vertical side results for Tasmota List, edit the extensions/tasmota_functions/z_CONFIG.php
    - Note That the vertical side results will create a refresh button for that value only if it is coded in reference.php
  - To add a new extension to FrankenYAML, create a .php file in the extensions folder, it will create an entry in the nav menu on the top automatically
    - To automatically load css / js files when your extension is loaded. make a '.js' and/or '.css' file with the same file name as your extensions' php file (minus .php). It will be dumped into the &lt;head&gt; tag. (eg. <i>my_extension.php</i>, when loaded, will have <i>my_extension.js</i> and <i>my_extension.css</i> in their &lt;head&gt; tag, if they exist
    - Extensions will inherit most of the functionality of FrankenYAML and will inherit the user's saved settings and whatnot
        
