<?php
require_once '../includes/config.php';
require_role('admin');
require_permission('inventory');
// Reuse the existing inventory screen to avoid duplicating or changing its business logic.
require '../staff/inventory.php';
