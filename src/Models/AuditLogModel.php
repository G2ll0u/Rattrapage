<?php
namespace App\Models;

class AuditLogModel extends Model
{

    public function getAllLogs()
    {
        return $this->getAll("audit_log");
    }
    public function getLog($id)
    {
        return $this->getByID("audit_log", $id, "ID_Log");
    }
    public function createLog(int $ID_User, string $action, string $table_name, int $row_id, int $phone_number, $log_timestamp)
    {
        $data = [
            "ID_User" => $ID_User,
            "action" => $action,
            "table_name" => $table_name,
            "row_id" => $row_id,
            "log_timestamp" => $log_timestamp
        ];
        return $this->create("audit_log", $data);
    }

    public function deleteLog(int $id)
    {
        return $this->delete($id, "audit_log", "ID_Log");
    }
}
?>