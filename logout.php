<?php
/**
 * TOKO FHIKA - Logout
 * File: logout.php
 */
require_once 'koneksi.php';
session_destroy();
header('Location: login.php');
exit;
