<?php
/**
 * Patient RCH4 card - simply opens the read-only card view.
 */
require_once __DIR__ . '/../includes/init.php';
require_role('patient');
redirect('modules/rch4/view.php');
