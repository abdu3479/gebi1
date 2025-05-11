<?php
require_once '../../config/database.php';
require_once '../../classes/Owner.php';
require_once '../../classes/Tenant.php';

header('Content-Type: text/html');

$type = $_POST['type'] ?? 'owner';
$db = new Database();
$html = '';

try {
    if ($type === 'owner') {
        $ownerObj = new Owner($db->getConnection());
        $owners = $ownerObj->getAll(['status' => 'active']);
        foreach ($owners as $owner) {
            $html .= sprintf('<option value="%d">%s (%s)</option>', 
                $owner['id'],
                htmlspecialchars($owner['full_name']),
                htmlspecialchars($owner['phone'])
            );
        }
    } elseif ($type === 'tenant') {
        $tenantObj = new Tenant($db->getConnection());
        $tenants = $tenantObj->getAll(['status' => 'active']);
        foreach ($tenants as $tenant) {
            $html .= sprintf('<option value="%d">%s (%s)</option>', 
                $tenant['id'],
                htmlspecialchars($tenant['full_name']),
                htmlspecialchars($tenant['phone'])
            );
        }
    } elseif ($type === 'group') {
        $html .= '<option value="all_owners">All Active Owners</option>';
        $html .= '<option value="all_tenants">All Active Tenants</option>';
        $html .= '<option value="overdue">Accounts with Overdue Payments</option>';
    }
    
    echo $html;
} catch (Exception $e) {
    echo '<option value="">Error loading recipients</option>';
}
?>