<?php
require 'includes/config.php';
startSecureSession();
session_destroy();
header('Location: index.php');
exit;
