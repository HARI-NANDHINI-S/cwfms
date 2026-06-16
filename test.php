<?php
require_once 'includes/config.php';
$db = new Supabase(true);
$result = $db->select('user_roles');
print_r($result);
