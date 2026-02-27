<?php
$filename = "contacts.txt";

if(file_exists($filename)) {
    $file = fopen($filename, "r");

    while(!feof($file)) {
        $line = fgets($file);
        echo "<p>$line</p>";
    }

    fclose($file);
} else {
    echo "<p>Contact information not available.</p>";
}
?>