Just add function to a registry module. in the WHMCS. I have the registry module ready and we work with it for many years, but now the Registry made a change that will take effect in few days. Need an additional fields that require NID : National ID. I have tried to register an .MA domain name now after the MA Registry Upgrade and seems that National ID was not sent to be saved with Registrant, Admin Contacts Attached screenshot from MA registry panel. What I done is to add this line to VivosMAEPP.php file. $RegistrantIDTitulare = $params["nid"];

and using the https://docs.whmcs.com/Additional_Domain_Fields i have added to additionalfields.php

// .MA $additionaldomainfields[".ma"][] = array( "Name" => "ID Titulaire ou Passeport", "LangVar" => "NID", "Type" => "text", "Size" => "20", "Default" => "ID Titulaire", "Required" => true, );

I think I made something wrong, can you please check

for each new domain name registration for .MA Can you please check

So the module need while creating the registrant, Tech admin contact, needs to include National ID or passport ID

term = NID = National ID

I have tried to register an .MA domain name now after the MA Registry Upgrade and seems that National ID was not sent to be saved with Registrant, Admin Contacts Attached screenshot from MA registry panel

Do I need to delete what I have added in my whmcs files additionalfields.php

// .MA $additionaldomainfields[".ma"][] = array( "Name" => "ID Titulaire ou Passeport", "LangVar" => "NID", "Type" => "text", "Size" => "20", "Default" => "ID Titulaire", "Required" => true, );

currently the National ID do not receive the NID data from the module after few days the MA registry will turn it required field and no one will be able to register domain names bcz that NID field is empty while contact creation the issue that we need to add a field named NID while creating a new domain contact
