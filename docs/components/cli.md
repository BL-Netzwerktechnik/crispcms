## Command Line Interface

Crisp comes with a built-in command-line interface (CLI) that enables you to perform a wide range of maintenance tasks either automatically or through third-party tools.


### Getting Started 

The CLI can be accessed through multiple entrypoints:

- crisp (Recommended)
- crisp-cli
- php bin/cli.php (From WORKDIR)


#### Get Instance ID

The Instance ID is a unique identifier specific to your installation, and it is essential for updating, removing, or generating a license.

Example:
<!-- tabs:start -->

#### **Bash**
```terminal
$|crisp -i
>|success|✓ Your instance id is: I0f243614-50f2-0f41-5daa-20fab68d960c

$|crisp --instance-id
>|success|✓ Your instance id is: I0f243614-50f2-0f41-5daa-20fab68d960c

$|crisp --instance-id --no-formatting # Print only the Instance ID
>|I0f243614-50f2-0f41-5daa-20fab68d960c
```
<!-- tabs:end -->



#### Overview

```bash
USAGE:
   cli.php <OPTIONS> <COMMAND> ...
   Interact with CrispCMS                                                                                                                                                      

OPTIONS:
   -v, --version                                       print version                                                                                                           
   -i, --instance-id                                   print instance id                                                                                                       
   -n, --no-formatting                                 Remove formatting of getter methods                                                                                     
   -h, --help                                          Display this help screen and exit immediately.                                                                          
   --no-colors                                         Do not use any colors in output. Useful when piping output to other tools or files.                                     
   --loglevel <level>                                  Minimum level of messages to display. Default is info. Valid levels are: debug, info, notice, success, warning, error,  
                                                       critical, alert, emergency.                                                                                             

COMMANDS:
   This tool accepts a command as first parameter as outlined below:                                                                                                           
   maintenance <OPTIONS>
     Get or Set the Maintenance Status of CrispCMS                                                                                                                             

     --on                                               Turn on the Maintenance Mode                                                                                           
     --off                                              Turn off the Maintenance Mode                                                                                          
   license <OPTIONS>
     Manage the Licensing System on CrispCMS                                                                                                                                   

     -c, --generate-private-key                         Generates a new key pair and saves it to /data                                                                         
     -i, --info                                         Get Info from your current /data/license.key                                                                           
     -t, --generate-test                                Generate a Test License to /data/license.key                                                                           
     -e, --expired                                      Generate an Expired License                                                                                            
     --no-expiry                                        Don't Expire the Test License                                                                                          
     --invalid-instance                                 Generate an invalid instance license                                                                                   
     -d, --delete                                       Delete the License Key                                                                                                 
     --delete-issuer                                    Delete the License Key                                                                                                 
     --get-issuer                                       Get the Issuer Public Key                                                                                              
     --delete-issuer-private                            Delete the Issuer Private Key                                                                                          
     --get-issuer-private                               Get the Issuer Private Key                                                                                             
   crisp <OPTIONS>
     Perform various tasks in the core of crisp                                                                                                                                

     -m, --migrate                                      Run the Database Migrations                                                                                            
     -p, --post-install                                 Run the Post Install Actions                                                                                           
   assets <OPTIONS>
     Perform various tasks for theme assets                                                                                                                                    

     -d, --deploy-to-s3                                 Deploy the assets/ folder to s3                                                                                        
   theme <OPTIONS>
     Interact with your Theme for CrispCMS                                                                                                                                     

     -b, --boot                                         Execute Boot files of your theme                                                                                       
     -c, --clear-cache                                  Clear Cache of the CMS                                                                                                 
     -m, --migrate                                      Migrate the Database for your theme                                                                                    
     -i, --install                                      Install the Theme mounted to crisptheme                                                                                
     -u, --uninstall                                    Uninstall the Theme mounted to crisptheme                                                                              
   migration <OPTIONS> <migrationName>
     Interact with CrispCMS Migrations                                                                                                                                         

     -c <migrationName>, --core <migrationName>         Create a new Core Migration File                                                                                       
     -t <migrationName>, --theme <migrationName>        Create a new Migration File for your Theme                                                                             
     <migrationName>                                    The name of your migration                                                                                             
   storage <OPTIONS>
     Interact with Crisps KVS                                                                                                                                                  

     -i, --install                                      Initialize the KVS from the theme.json                                                                                 
     -f, --force                                        Overwrite the KVS from the theme.json                                                                                  
     -u, --uninstall                                    Delete all KVS Items from the database                                                                                 
   translation <OPTIONS>
     Interact with Crisps KVS                                                                                                                                                  

     -i, --install                                      Initialize the Translations from the theme.json                                                                        
     -u, --uninstall                                    Delete all Translation Items from the database  
```