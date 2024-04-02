<?php

// Process to run thru process mail logs and create an array of bounced emails
// Doesn't handle gz logs I am afraid!

$arr = array();
$files = glob('/var/log/mail.log*');

foreach($files as $f)
{
        getBounces($f);
}

processBounces($f);

function getBounces($filename)
{
        // Path to the mail log file
        $logFilePath = $filename;

        // Open the mail log file for reading
        $fileHandle = fopen($logFilePath, 'r');

        global  $arr;

        if ($fileHandle)
        {
                echo("Processing $filename\n");
                // Iterate through each line in the log file
                while (($line = fgets($fileHandle)) !== false)
                {
                        // Check if the line contains an email address and status
                        if (preg_match('/to=<([^>]+)>.+status=(\w+)/', $line, $matches))
                        {
                                $email = $matches[1];
                                $status = $matches[2];
                                // Skip Deferred emails
                                if($status == 'deferred')
                                        continue;
                                // Check if status is not "ok"
                                if ($status !== 'sent')
                                {
                                        //echo "Email: $email status: $status \n";
                                        $arr[$email] = $status;
                                }
                                else
                                {
                                        if(array_key_exists($email, $arr))
                                        {
                                                echo "$email was sent after all\n";
                                                unset($arr[$email]);
                                        }
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
        foreach($arr as $k=>$v)
        {
                echo "Email:$k status: $v \n";
        }
        $cnt = count($arr);
        echo ("Total emails bounced: $cnt\n");
}

