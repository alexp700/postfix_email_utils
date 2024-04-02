<?php

// Process to run thru process mailbox and create an array of bounced emails
// Doesn't handle emails split by = on line
// skips emails with whitelisted domain suffix
// Expects all bounce emails to go to /var/mail/root
// Detects unsubscribes also
// Calls Icegram Express API hacked to support {"email":"a@a.net"} lookups in delete:
// add after $params is set in delete_subscribers()
//   if( isset($params['email']) )
//                        {
//                                $params['contact_id'] = ES()->contacts_db->get_contact_id_by_email( $params["email"] );
//                        }
//
//


$icegramkey = '';
$icegramapi = '';
$icegramuser = '';
$whitelist = "WHITELISTDOMAIN.COM";

$arr = array();
$files = glob('/var/mail/root');

foreach($files as $f)
{
	getBounces($f);
}

processBounces($f);

function getBounces($filename)
{
	global  $arr, $whitelist;

	// Path to the mail log file
	$logFilePath = $filename;

	// Open the mail log file for reading
	$fileHandle = fopen($logFilePath, 'r');

	if ($fileHandle)
	{
		echo("Processing $filename\n");
		// Iterate through each line in the log file
    		while (($line = fgets($fileHandle)) !== false)
		{
        		// Check if the line contains an email address and status
        		if (preg_match('/<([^<>:@]+@[^<>]+)>/', $line, $matches))
			{
            			$email = $matches[1];
				if(strpos($email, $whitelist) === false)
				{
					$arr[trim($email)] = "error";
            			}
        		}
                        // Check if the line contains failed Receipients header
                        if (preg_match('/^X-Failed-Recipients:\s+([^<>:@]+@[^<>]+)/', $line, $matches))
                        {
                                $email = $matches[1];
                                if(strpos($email, $whitelist) === false)
                                {
                                        $arr[trim($email)] = "X-failed recipients";
                                }
                        }
			// Check if the line contains an apple unsubscribe
                        if (preg_match('/^Subject: Unsubscribe\s+([^<>:@]+@[^<>]+)\s+from/', $line, $matches))
                        {
                                $email = $matches[1];
                                if(strpos($email, $whitelist) === false)
                                {
                                        $arr[trim($email)] = "unsubscribe";
                                }
                        }

    		}

    		// Close the file handle
    		fclose($fileHandle);
	}
	else
	{
	    	echo "Error: Unable to open file $filename.\n";
	}
}

function processBounces()
{
	global $arr;
	$cur = 1;
	$resStr = "";
        $cnt = count($arr);
        echo ("Total emails bounced: $cnt\n");

	foreach($arr as $k=>$v)
        {
		$res = ice_api('{"email":"' . $k .'"}', "DELETE");
		$resStr = print_r($res,1);
                echo "Rec:$cur Email:$k status: $v \n". $resStr . "\n";
		$cur++;
        }

	echo ("Total emails bounced: $cnt\n");
}


function ice_api( $json, $action)
{
            global $icegramkey, $icegramapi, $icegramuser;
	    $url = $icegramapi;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $action);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', "username: $icegramuser", "password: $icegramkey"));
            curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);

            $output = curl_exec($ch);

            if(empty($output)){
		echo("failed curl $url $json $action");
                die(curl_error($ch));
                curl_close($ch);
            }
            else{
		//echo "\n". $json ."\n";
                $info = curl_getinfo($ch);
                curl_close($ch);
            }

            if (empty($info['http_code'])) {
                echo("failed curl no return code  $url $json $action");
                die();
            }

            $return = [
                'http_code' => $info['http_code'],
                'output' => $output
            ];

            return $return;
}
