<?php
require 'config.php';
if ($mysqli) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
?>