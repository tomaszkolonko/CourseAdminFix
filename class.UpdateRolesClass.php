<?php
/**
 * Class UpdateRolesClass
 *
 * @author  Tomasz Kolonko <thomas.kolonko@ilub.unibe.ch>
 */
class UpdateRolesClass {

    /**
     * @var ilDB $db
     */
    protected $db;

    /**
     * @var ilRbacAdmin $rbac_admin
     */
    protected $rbac_admin;

    /**
     * @var ilRbacReview $rbac_review
     */
    protected $rbac_review;

    /**
     * @var UpdateRolesLog $log
     */
    protected $log;

    /**
     * @var int $counter
     */
    protected $counter;

    /**
     * Constructor initializes ilDB and RBAC objects.
     */
    public function __construct() {
        global $ilDB, $rbacadmin, $rbacreview;

        // $this->log = xoctMigrationLog::getInstance();
        $this->db = $ilDB;
        $this->rbac_admin = $rbacadmin;
        $this->rbac_review = $rbacreview;

        require_once('./CourseAdminFix/class.UpdateRolesLog.php');
        $this->log = UpdateRolesLog::getInstance();

        $this->counter = 0;

        $this->operationCreateXOCT = $this->lookUpRole("create_xlvo");
    }

    public function lookUpRole($role) {
        $set = $this->db->query('SELECT ops_id FROM rbac_operations WHERE operation=' . "'" . $role . "'");
        $rec = $this->db->fetchAssoc($set);
        return $rec['ops_id'];
    }

    /**
     *
     */
    public function findAllCourseAdminsAndTutors() {

        $this->log->write("finding all Course Admins and Course Tutors");
        $this->log->write("===========================================");

        $set = $this->db->query('SELECT object_data_1.obj_id AS object_id_1, object_reference.ref_id AS ref_id, object_reference.obj_id AS object_id_2, rbac_fa.parent AS roles_parent_ref_id, object_data_1.title AS object_title, object_data_2.title AS role_title, object_data_2.description AS roles_description, object_data_2.obj_id AS role_id, object_data_1.type
	      FROM (object_data AS object_data_1, object_data AS object_data_2, object_reference, rbac_fa) 
	      WHERE object_data_1.obj_id = object_reference.obj_id 
	      AND object_data_2.obj_id = rbac_fa.rol_id
	      AND object_reference.ref_id = rbac_fa.parent
	      AND object_data_1.type="crs"
	      AND (object_data_2.title LIKE "il_crs_admin_%" OR object_data_2.title LIKE "il_crs_tutor_%")');

        while ($rec = $this->db->fetchAssoc($set)) {
            $this->updatePermissionTable($rec);
        }
    }

    /**
     *
     */
    public function findAllGroupAdminsAndTutors() {

        $this->log->write("finding all Group Admins and Course Tutors");
        $this->log->write("===========================================");

        $set = $this->db->query('SELECT object_data_1.obj_id AS object_id_1, object_reference.ref_id AS ref_id, object_reference.obj_id AS object_id_2, rbac_fa.parent AS roles_parent_ref_id, object_data_1.title AS object_title, object_data_2.title AS role_title, object_data_2.description AS roles_description, object_data_2.obj_id AS role_id, object_data_1.type
	      FROM (object_data AS object_data_1, object_data AS object_data_2, object_reference, rbac_fa) 
	      WHERE object_data_1.obj_id = object_reference.obj_id 
	      AND object_data_2.obj_id = rbac_fa.rol_id
	      AND object_reference.ref_id = rbac_fa.parent
	      AND object_data_1.type="grp"
	      AND (object_data_2.title LIKE "il_crs_admin_%" OR object_data_2.title LIKE "il_crs_tutor_%")');

        while ($rec = $this->db->fetchAssoc($set)) {
            $this->updatePermissionTable($rec);
        }
    }

    /**
     *
     */
    public function findAllFoldersAndRoles() {

        $this->log->write("finding all Folder Roles (grp admin crs admin and crs tutor)");
        $this->log->write("============================================================");

        $set = $this->db->query('SELECT object_data.obj_id, object_data.type, object_data.title, object_reference.ref_id, rbac_pa.rol_id AS role_id, object_data_2.title
          FROM object_data, object_reference, rbac_pa, object_data AS object_data_2
          WHERE object_data.type="fold"
          AND (object_data_2.title LIKE "il_crs_admin_%" OR object_data_2.title LIKE "il_crs_tutor_%")
          AND object_data.obj_id = object_reference.obj_id
          AND object_reference.ref_id = rbac_pa.ref_id
          AND object_data_2.obj_id = rbac_pa.rol_id');

        while ($rec = $this->db->fetchAssoc($set)) {
            $this->updatePermissionTable($rec);
        }
    }



    /**
     * Retrieve from DB the current permission table for the course admin role and add the required permission to the
     * array, then update the permission table.
     */
    public function updatePermissionTable($rec) {

        $this->log->write("changing: " . print_r($rec['role_title'],true) . " role_id: " . print_r($rec['role_id'],true) .
                                " and ref_id: " . print_r($rec['ref_id'],true));
        $this->log->write("before: ");

        $old_ops = $this->rbac_review->getRoleOperationsOnObject(
            $rec['role_id'],
            $rec['ref_id']
        );

        $this->log->write(print_r($old_ops,true));
        $this->log->write("after: ");

        array_push($old_ops, $this->getOperationCreateXOCT());

        $this->rbac_admin->grantPermission(
            $rec['role_id'],
            array_unique($old_ops),
            $rec['ref_id']
        );

        $new_ops = $this->rbac_review->getRoleOperationsOnObject(
            $rec['role_id'],
            $rec['ref_id']
        );
        $this->log->write(print_r($new_ops,true));

        $this->counter++;


    }
/*
    public function setCoursesAndGroupsAndFolderPermissions($rec) {

        $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="crs"
              AND ops_id=' . "'" . $this->getOperationCreateXOCT() . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
        if(!$this->db->fetchAssoc($set)) {
            echo "\nadding crs";
            $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                'VALUES( '.
                $this->db->quote($rec['role_id'],'integer').', '.
                $this->db->quote("crs",'text').', '.
                $this->db->quote($this->getOperationCreateXOCT(),'integer').', '.
                $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                ')';
            $this->db->manipulate($query);
        }


        $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="grp"
              AND ops_id=' . "'" . $this->getOperationCreateXOCT() . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
        if(!$this->db->fetchAssoc($set)) {
            echo "\nadding grp";
            $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                'VALUES( '.
                $this->db->quote($rec['role_id'],'integer').', '.
                $this->db->quote("grp",'text').', '.
                $this->db->quote($this->getOperationCreateXOCT(),'integer').', '.
                $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                ')';
            $this->db->manipulate($query);
        }


        $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="fold"
              AND ops_id=' . "'" . $this->getOperationCreateXOCT() . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
        if(!$this->db->fetchAssoc($set)) {
            echo "\nadding fold";
            $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                'VALUES( '.
                $this->db->quote($rec['role_id'],'integer').', '.
                $this->db->quote("fold",'text').', '.
                $this->db->quote($this->getOperationCreateXOCT(),'integer').', '.
                $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                ')';
            $this->db->manipulate($query);
        }
    }
*/

    public function getOperationCreateXOCT() {
        return $this->operationCreateXOCT;
    }
}
?>