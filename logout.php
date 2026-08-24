<?php
require_once __DIR__ . '/includes/auth.php';
logout();
start_session();
flash('success', 'You have been logged out.');
redirect('login.php');
