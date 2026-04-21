<?php
require_once '../config/config.php';

session_destroy();
redirect('/../autenticacion/login.php');
?>