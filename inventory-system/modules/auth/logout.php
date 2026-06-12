<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
logout();
session_start();   // fresh session for flash message
flash('info', 'You have been logged out.');
redirect('modules/auth/login.php');
