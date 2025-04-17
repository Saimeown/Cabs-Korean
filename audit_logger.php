<?php
function log_audit_action($action_type, $table_affected = null, $record_id = null, $old_values = null, $new_values = null) {
    include 'db_connect.php';
    
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? 'guest';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (is_array($old_values)) $old_values = json_encode($old_values);
    if (is_array($new_values)) $new_values = json_encode($new_values);
    
    $stmt = $conn->prepare("INSERT INTO audit_log 
        (user_id, user_role, action_type, table_affected, record_id, old_values, new_values, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("isssissss", 
        $user_id, $user_role, $action_type, $table_affected, $record_id, 
        $old_values, $new_values, $ip_address, $user_agent);
    
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>