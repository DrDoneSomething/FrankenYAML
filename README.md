FrankenYAML 1.0 WITH Tasmota List 1.0!

By Dr DoneSomething

BSD license

<h1>FrankenYAML</h1>

  - FrankenYAML will take all your .yaml files for HomeAssistant and turn each entry into an Object.
  - Not exciting enough for you? Well you can then add, edit, delete, disable (comment out), and export it, then re-import it!
  - Still not good enough? Well you can export either to one big file (makes the pi boot happier) or to a bunch of tiny files like <a href="https://www.youtube.com/watch?v=lndeybw21PY">Frenck did in his setup.</a>
  - Icing on the cake: You can easily create an account which will automatically save changes for quick editing later.
      - otherwise transfers the data from panel to panel via exported _POST_ values
      - therefore, unless you create an account, your config is stored nowhere but your browser RAM, for better security (don't close that browser window!)
 
 Tasmota List
  - Scans your network for tasmota devices via web send!
  - stores them for later
  - accesses by hostname not IP (when loading from stored)
  - sends commands
  - does it in a quick and efficient way
  - easily customizable for people who love to do lots of changes to tasmota devices
  - runs on just a php webserver ( no need for MQTT! )

Installation
- Install a web server of your choice
- Install php on that server
- put frankenyaml directory in the web directory
- type the url into your web browser
- Note: this saves data to text files so it must have permissions!

Instructions
- <a href="https://www.youtube.com/watch?v=4iPefBPq0Wo">FranekYAML</a>
- <a href="https://www.youtube.com/watch?v=-sv9vlIR-7U">Tasmota List Basics, installation, advanced use</a>
