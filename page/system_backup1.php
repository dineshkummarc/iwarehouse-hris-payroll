<?php
$program_code = 36;
require_once('../system.config.php');
require_once('../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$access_rights = $cfn->get_user_rights($program_code);
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-backup-data":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        get_backup_data();
                    }
                break;
                case "make-backup":
                    if (substr($access_rights, 0, 2) === "A+") {
                        $user = 'root';
                        make_backup($user);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "del-backup-data":
                    if (substr($access_rights, 4, 2) === "D+") {
                        delete_backup($_POST['recid']);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

function get_backup_data(){
    global $db_hris, $db;

    $backup = $db->prepare("SELECT * FROM $db_hris.`_backup` ORDER BY `backup_id` ASC");
    $backup->execute();
    if ($backup->rowCount()) {
        $records = array();
        while ($backup_data = $backup->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            array_push($records, array("recid" => $backup_data["backup_id"], "desc" => $backup_data["backup_desc"], "date" => $backup_data["timestamp"], "size" => $backup_data["size"], "storage" => md5($backup_data["location"]).uniqid()));
        }
        echo json_encode(array("status" => "success", "total" => count($records), "records" => $records));
    } else {
        echo json_encode(array("status" => "error", "message" => "NO BACK UP FOUND!"));
    }
}

function make_backup($user){
    global $db_hris, $db;

    $backup_file = "../backup/".$db_hris ."_". date("Y-m-d-H-i-s") . ".sql";
    $filename = $db_hris ."_". date("Y-m-d-H-i-s") . ".sql";
    $command = "c:/xampp/mysql/bin/mysqldump  -u $user $db_hris > $backup_file";
    exec($command);
    if(file_exists($backup_file)){
        $insert = $db->prepare("INSERT INTO $db_hris.`_backup`(`backup_desc`, `size`, `location`) VALUES (:desc, :size, :loc)");
        $insert->execute(array(":desc" => $filename, ":size" => number_format(filesize($backup_file) / 1024) .' KB', ":loc" => $backup_file));
        if($insert->rowCount()){
            echo json_encode(array("status" => "success", "message" => "Backup created successfully: {$filename}"));
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Backup Failed"));
    }
}

function delete_backup($id){
    global $db_hris, $db;

    $backup = $db->prepare("SELECT * FROM $db_hris.`_backup` WHERE `backup_id`=:id");
    $backup->execute(array(":id" => $id));
    if ($backup->rowCount()) {
        $backup_data = $backup->fetch(PDO::FETCH_ASSOC);
        $file = $backup_data['location'];
        @unlink($file);
        $del_backup = $db->prepare("DELETE FROM $db_hris.`_backup` WHERE `backup_id`=:id");
        $del_backup->execute(array(":id" => $id));
        if ($del_backup->rowCount()) {
            echo json_encode(array("status" => "success", "message" => "Backup Deteled"));
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Delete Failed"));
    }
}