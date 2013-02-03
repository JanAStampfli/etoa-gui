Escape to Andromeda Installation Guide
======================================

Frontend - Version 1.0

Files
-----
Copy all files to the gameservers root directory, e.g. /var/www/roundx.etoa.net/htdocs,
or user svn checkout:

    $ svn co http://dev.etoa.net/svn/game/trunk /var/www/roundx.etoa.net/htdocs
	
	
Permissions
-----------
Execute the following command:
	
    $ chmod 777 -R /var/www/roundx.etoa.net/htdocs/cache

to allow writing to the cache directory


Config
------
Edit the example.conf.inc.php file to your needs and rename it to conf.inc.php

PASSWORD_SALT is a random choosen value, which has to be set at the begining of a round and should not be changed during a round


Scripts
-------
Execute scripts/reset_admin_pw.sh for setting the admin mode htaccess password to it's default value
(default user and passwort will be shown at the end of the above script)


Cronjobs
--------
On the unix shell, execute 

    $ crontab -e

this will open the cron editor. Insert the following text (when using vi as editor, press INSERT first)
 
    * * * * * php /path/to/etoa/scripts/update.php
    1,6,11,16,21,26,31,36,41,46,51,56 * * * * php /path/to/etoa/scripts/userstats.php
    3 * * * * php /path/to/etoa/scripts/gamestats.php > /dev/null
	
Save and exit (in vi, press CTRL+C, then write wq and press ENTER), type

    $ crontab -l

to verify your settings.


Misc
----

The admin panel can be accessed at roundx/admin.

 * Go to Admin-Panel => Config => Imagepacks and generate the downloadable imagepack files
 * Go to Admin-Panel => Config => Generate Universe to create the universe for this round
 
Sample installation on host.etoa.net
------------------------------------

Assuming round name 'round12'. 

Create directory and checkout code from SCM:

    $ mkdir -p /var/www/etoa/round12/htdocs
    $ ln -s /var/www/etoa/round12/htdocs ~/round12
    $ cd ~/round12
    $ svn co https://devel.etoa.net/svn/etoa-gui/trunk/htdocs 
    
Set permissions on cache directory:
    
    $ chmod -R 777 cache/

Create database config:

    $ cp config/db.conf.dist config/db.conf
    $ vim config/db.conf

Create database and user and import schema and data sql using e.g. phpMyAdmin on http://host.etoa.net/phpmyadmin

Create apache config (Root account required):
    
    $ vim /etc/apache2/sites-available/4_etoa_round12
    
    <VirtualHost *:80>
            ServerAdmin mail@etoa.ch
            DocumentRoot "/var/www/etoa/round12/htdocs/"
            ServerName round12.live.etoa.net
            DirectoryIndex index.php index.html
            ErrorLog /var/log/apache2/round12.live.etoa.net_error_log
            CustomLog /var/log/apache2/round12.live.etoa.net_access_log combined
            <Directory "/var/www/etoa/round12/htdocs">
                    Options -Indexes
                    AllowOverride All
                    Order allow,deny
                    Allow from all
            </Directory>
            ErrorDocument 401 /error/error.php
            ErrorDocument 403 /error/error.php
            ErrorDocument 404 /error/error.php
    </VirtualHost>
    
    $ ln -s /etc/apache2/sites-available/4_etoa_round12 /etc/apache2/sites-enabled/
    $ service apache2 reload

Access admin panel on http://round12.live.etoa.net/admin

Visit the base config page on [http://round12.live.etoa.net/admin/?page=config](http://round12.live.etoa.net/admin/?page=config) and change the settings to match the round name. 

The eventhandler IPC-Key can be obtained by starting the eventhandler backend for this round in debug mode.

