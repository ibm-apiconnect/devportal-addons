## IBM API Connect
 
Custom module to fix missing apic_state field on users. This module introduces a new drush command which will fix the apic_state field should it be missing for the specified users. 
It is intended to be used as a workaround to specific problems in API Connect v2018. This tool is no longer required with API Connect 2018.4.1.7 and later releases.

Please do not use unless you have been requested to by IBM Support.

#### Usage

`drush fix_missing_apic_state <username>`

Where <username> is the name of the user you wish to fix the apic_state field.



 
