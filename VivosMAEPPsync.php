<?php

# Bring in Database Constants
# require_once dirname(__FILE__) . '/../../../dbconnect.php';
require_once dirname(__FILE__) . '/../../../init.php';

# Setup include dir
$include_path = ROOTDIR . '/modules/registrars/VivosMAEPP';
set_include_path($include_path . PATH_SEPARATOR . get_include_path());
# Include EPP stuff we need
require_once 'VivosMAEPP.php';
# Additional functions we need
require_once ROOTDIR . '/includes/functions.php';
# Registrar Functions
require_once ROOTDIR . '/includes/registrarfunctions.php';

# Grab module parameters
$params = getregistrarconfigoptions('VivosMAEPP');

# Let's Go...
try {
	$client = _VivosMAEPP_Client();

	# Pull list of domains which are registered using this module
	$queryresult = mysql_query("SELECT domain FROM tbldomains WHERE registrar = 'VivosMAEPP'");
	while($data = mysql_fetch_array($queryresult)) {
		$domains[] = trim(strtolower($data['domain']));
	}

	# Loop with each one
	foreach($domains as $domain) {
		sleep(1);

		# Query domain
		$output = $client->request($xml='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
      <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
       <command>
        <info>
         <domain:info
          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
				<domain:name>'.$domain.'</domain:name>
			</domain:info>
		</info>
		<clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
      </epp>');

		$doc= new DOMDocument();
		$doc->loadXML($output);
		$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $resultpr =  $doc->getElementsByTagName('result')->item(0)->nodeValue;
		if($coderes == '1000') {
			if( $doc->getElementsByTagName('status')) {
				if($doc->getElementsByTagName('status')->item(0)) {
					$statusres = $doc->getElementsByTagName('status')->item(0)->getAttribute('s');
					$createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
					$nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
				} else {
					$status = "Domain $domain not registered!";
					continue;
				}
                               if($doc->getElementsByTagName('status')->item(1)) {
                                       $statusres2 = $doc->getElementsByTagName('status')->item(1)->getAttribute('s');
                                }
			}
                 } else {
                       $queryresult = mysql_query("SELECT status FROM tbldomains WHERE registrar = 'VivosMAEPP' AND  domain='".$domain."'");
                       $data = mysql_fetch_array($queryresult);
                       $domainStatus = trim(strtolower($data['status']));
                       echo ("The Status of  '".$domain."'  is '".$domainStatus."' \n");
                       $pendingStatus = 'Pending';
                        if (strcmp($domainStatus, 'pending') == 0) {
                            echo ("|NOTICE: The '".$domain."' Status is Pending in WHMCS, please check your registrar Log \n");
                          }
                         elseif  ($resultpr == 'Object does not exist') {
                                echo "Domain check on $domain not successful :::$resultpr \n";
 
                                 
                                      echo ("$domain does not exist at registrar");
                                      echo ("|-----------------------------------------------------------\n");
                                  
                                continue;
                               }
                         elseif ($resultpr == 'Authorization error') {
                               echo "Domain check on $domain not successful :::$resultpr \n";
 
                    
                                      echo ("$domain authorization error");
                                      echo ("|-----------------------------------------------------------\n");
                                                     
                               continue;

                             }
                            else {

                               echo "Domain check on $domain not successful :::$resultpr \n";
                               continue;

                                  }
                }

		# This is the template we going to use below for our updates
		$querytemplate = "UPDATE tbldomains SET status = '%s', registrationdate = '%s', expirydate = '%s', nextduedate = '%s' WHERE domain = '%s'";
                $queryupdatetemplate = "UPDATE tbldomains SET registrationdate = '%s', expirydate = '%s', nextduedate = '%s' WHERE domain = '%s'";
		# Check status and update
		if ($statusres == "ok") {
                    echo "Domain : $domain has status :$statusres \n";
			mysql_query(sprintf($querytemplate,"Active",
					mysql_real_escape_string($createdate),
					mysql_real_escape_string($nextduedate),
					mysql_real_escape_string($nextduedate),
					mysql_real_escape_string($domain)
			));
                 } elseif ($statusres == "inactive" && $statusres2 == "serverHold") {
                          echo "Domain : $domain has status :$statusres \n";
                          mysql_query(sprintf($querytemplate,"Pending",
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));
                 } elseif ($statusres == "inactive" && $statusres2 != "serverHold") {
                    echo "Domain : $domain has status :$statusres \n";

                          mysql_query(sprintf($querytemplate,"Active",
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));

		} elseif ($statusres == "serverHold" && $statusres != "inactive"){
                  echo "Domain : $domain has status :$statusres \n";
                  mysql_query(sprintf($querytemplate,"Pending",
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));
                } elseif ($statusres == "clientRenewProhibited" || $statusres == "clientDeleteProhibited" || $statusres == "clientTransferProhibited" ) {
                 echo "Domain : $domain has status :$statusres \n";
                 mysql_query(sprintf($queryupdatetemplate,
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));
                } elseif ($statusres == "serverDeleteProhibited" ||  $statusres == "serverRenewProhibited" || $statusres == "serverTransferProhibited") {
                 echo "Domain : $domain has status :$statusres \n";
                 mysql_query(sprintf($queryupdatetemplate,
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));
                } elseif ($statusres == "pendingCreate") {
                    echo "Domain : $domain has status :$statusres \n";
                    mysql_query(sprintf($querytemplate,"Pending",
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));
                } elseif ($statusres == "pendingDelete") { 
                    echo "Domain : $domain has status :$statusres \n";
                   mysql_query(sprintf($querytemplate,"Expired",
                                        mysql_real_escape_string($createdate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($nextduedate),
                                        mysql_real_escape_string($domain)
                        ));

		} elseif ($statusres == "expired") {
                    echo "Domain : $domain has status :$statusres \n";
			mysql_query(sprintf($querytemplate,"Expired",
					mysql_real_escape_string($createdate),
					mysql_real_escape_string($nextduedate),
					mysql_real_escape_string($nextduedate),
					mysql_real_escape_string($domain)
			));
                        update_query("tbldomains", array("expirydate" => $nextduedate, "synced" => "1"), array("domain" => $domain));

		} else {
			update_query("tbldomains", array("expirydate" => $nextduedate, "synced" => "1"), array("domain" => $domain));
		}
	}      
    
} catch (Exception $e) {
	echo("ERROR: ".$e->getMessage()."\n");
	exit;
}

?>
