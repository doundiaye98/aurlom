<?php
require_once __DIR__ . '/includes/init.php';
logout_user();
flash_set('success', 'Vous êtes déconnecté.');
redirect('index.php');
