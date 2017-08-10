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
     * @var int $editPermission
     */
    protected $editPermission;
    /**
     * @var int $isVisible
     */
    protected $isVisible;
    /**
     * @var int $read
     */
    protected $read;
    /**
     * @var int $write
     */
    protected $write;
    /**
     * @var int $delete
     */
    protected $delete;
    /**
     * @var int $upload
     */
    protected $upload;
    /**
     * @var int $editVideos
     */
    protected $editVideos;
    /**
     * @var int $operationCreateXOCT
     */
    protected $operationCreateXOCT;
    /**
     * @var String $type
     */
    protected $type;



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

        $this->setEditPermission();
        $this->setIsVisible();
        $this->setRead();
        $this->setWrite();
        $this->setDelete();
        $this->setUpload();
        $this->setEditVideos();

        $this->setType("xoct");

        $this->setOperationCreateXOCT();
    }

    public function lookUpRole($role) {
        $set = $this->db->query('SELECT ops_id FROM rbac_operations WHERE operation=' . "'" . $role . "'");
        $rec = $this->db->fetchAssoc($set);
        return $rec['ops_id'];
    }

    /**
     *
     */
    public function findAllCourseAdminsTutorsMembers() {
        $this->counter = 0;

        $this->log->write("finding all Course Admins, Tutors and Members");
        $this->log->write("=============================================");

        $set = $this->db->query('SELECT object_data_1.obj_id AS object_id_1, object_reference.ref_id AS ref_id, object_reference.obj_id AS object_id_2, rbac_fa.parent AS roles_parent_ref_id, object_data_1.title AS object_title, object_data_2.title AS role_title, object_data_2.description AS roles_description, object_data_2.obj_id AS role_id, object_data_1.type
	      FROM (object_data AS object_data_1, object_data AS object_data_2, object_reference, rbac_fa) 
	      WHERE object_data_1.obj_id = object_reference.obj_id 
	      AND object_data_2.obj_id = rbac_fa.rol_id
	      AND object_reference.ref_id = rbac_fa.parent
	      AND object_data_1.type="crs"
	      AND (object_data_2.title LIKE "il_crs_admin_%" OR object_data_2.title LIKE "il_crs_tutor_%" OR object_data_2.title LIKE "il_crs_member_%")');

        while ($rec = $this->db->fetchAssoc($set)) {
            if(substr($rec['role_title'], 0, 10) == "il_crs_adm" || substr($rec['role_title'], 0, 10) == "il_crs_tut") {
                $this->updatePermissionTable($rec);
            }
            $this->updateRoleTemplatesAdminTutorAndMember($rec);
            $this->counter++;
        }
        $this->log->write($this->counter . " Course Admins and Course Tutors found...\n");

    }

    /**
     *
     */
    public function findAllGroupAdminsAndTutors() {
        $this->counter = 0;

        $this->log->write("finding all Group Admins and Members and Course Admins and Tutors");
        $this->log->write("=================================================================");

        $set = $this->db->query('SELECT object_data_1.obj_id AS object_id_1, object_reference.ref_id AS ref_id, object_reference.obj_id AS object_id_2, rbac_fa.parent AS roles_parent_ref_id, object_data_1.title AS object_title, object_data_2.title AS role_title, object_data_2.description AS roles_description, object_data_2.obj_id AS role_id, object_data_1.type
	      FROM (object_data AS object_data_1, object_data AS object_data_2, object_reference, rbac_fa) 
	      WHERE object_data_1.obj_id = object_reference.obj_id 
	      AND object_data_2.obj_id = rbac_fa.rol_id
	      AND object_reference.ref_id = rbac_fa.parent
	      AND object_data_1.type="grp"
	      AND (object_data_2.title LIKE "il_crs_admin_%" OR object_data_2.title LIKE "il_crs_tutor_%" OR object_data_2.title LIKE "il_grp_admin_%" OR object_data_2.title LIKE "il_grp_member_%")');

        while ($rec = $this->db->fetchAssoc($set)) {
            if(substr($rec['role_title'], 0, 10) == "il_grp_mem") {
                $this->updateAllMemberTemplates($rec);
            } else {
                if(substr($rec['role_title'], 0, 10) == "il_grp_adm") {
                    $this->updateRoleTemplatesAdminTutorAndMember($rec);
                }
                $this->updatePermissionTable($rec);
            }
            $this->counter++;
        }
        $this->log->write($this->counter . " Group Admins and Course Tutors found...\n");
    }

    /**
     *
     */
    public function findAllFoldersAndRoles() {
        $this->counter = 0;

        $this->log->write("finding all Folder Roles (crs admin and crs tutor)");
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
            $this->counter++;
        }

        $this->log->write($this->counter . " Folder Roles found...\n");
    }



    /**
     * Retrieve from DB the current permission table for the course admin role and add the required permission to the
     * array, then update the permission table.
     */
    public function updatePermissionTable($rec) {
        $this->log->write("changing: " . print_r($rec['role_title'],true) . " role_id: " . print_r($rec['role_id'],true) .
                                " and ref_id: " . print_r($rec['ref_id'],true));

        $old_ops = $this->rbac_review->getRoleOperationsOnObject(
            $rec['role_id'],
            $rec['ref_id']
        );

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

        $this->counter++;


    }

    public function updateRoleTemplatesAdminTutorAndMember($rec) {
        if(substr($rec['role_title'], 0, 10) == "il_crs_adm" || substr($rec['role_title'], 0, 10) == "il_grp_adm") {
            $this->updateAllCourseAdminTemplates($rec);
            $this->log->write("admin template set !!!");
        } else if(substr($rec['role_title'], 0, 10) == "il_crs_tut") {
            $this->updateAllCourseTutorTemplates($rec);
            $this->log->write("tutor template set !!!");
        } else if(substr($rec['role_title'], 0, 10) == "il_crs_mem") {
            $this->updateAllMemberTemplates($rec);
            $this->log->write("member template set !!!");
        }
    }

    public function updateAllCourseAdminTemplates($rec) {
        $courseAdminRoleArray = array($this->getEditPermission(), $this->getIsVisible(), $this->getRead(), $this->getWrite(),
            $this->getDelete(), $this->getUpload(), $this->getEditVideos());

        foreach($courseAdminRoleArray as $adminRole) {
            $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="xoct"
              AND ops_id=' . "'" . $adminRole . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
            if(!$this->db->fetchAssoc($set)) {
                $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                    'VALUES( '.
                    $this->db->quote($rec['role_id'],'integer').', '.
                    $this->db->quote($this->getType(),'text').', '.
                    $this->db->quote($adminRole,'integer').', '.
                    $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                    ')';
                $this->db->manipulate($query);
            }
        }
    }

    public function updateAllCourseTutorTemplates($rec) {
        $courseTutorRoleArray = array($this->getIsVisible(), $this->getRead(), $this->getWrite(),
            $this->getUpload(), $this->getEditVideos());

        foreach($courseTutorRoleArray as $tutorRole) {
            $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="xoct"
              AND ops_id=' . "'" . $tutorRole . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
            if(!$this->db->fetchAssoc($set)) {
                $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                    'VALUES( '.
                    $this->db->quote($rec['role_id'],'integer').', '.
                    $this->db->quote($this->getType(),'text').', '.
                    $this->db->quote($tutorRole,'integer').', '.
                    $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                    ')';
                $this->db->manipulate($query);
            }
        }
    }

    public function updateAllMemberTemplates($rec) {
        $this->memberRoleArray = array();
        if(substr($rec['role_title'], 0, 10) == "il_grp_mem") {
            $this->memberRoleArray = array($this->getIsVisible(), $this->getRead(), $this->getUpload());
        } else {
            $this->memberRoleArray = array($this->getIsVisible(), $this->getRead());
        }


        foreach($this->memberRoleArray as $memberRole) {
            $set = $this->db->query('SELECT rol_id, type, ops_id, parent FROM rbac_templates
              WHERE rol_id=' . "'" . $rec['role_id'] . "'" . 'AND type="xoct"
              AND ops_id=' . "'" . $memberRole . "'" . 'AND parent=' . "'" . $rec['roles_parent_ref_id'] . "'");
            if(!$this->db->fetchAssoc($set)) {
                $query = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) '.
                    'VALUES( '.
                    $this->db->quote($rec['role_id'],'integer').', '.
                    $this->db->quote($this->getType(),'text').', '.
                    $this->db->quote($memberRole,'integer').', '.
                    $this->db->quote($rec['roles_parent_ref_id'],'integer').' '.
                    ')';
                $this->db->manipulate($query);
            }
        }
    }

    public function setEditPermission() {
        $this->editPermission = $this->lookUpRole("edit_permission"); // 1

    }
    public function getEditPermission() {
        return $this->editPermission;

    }

    public function setIsVisible() {
        $this->isVisible = $this->lookUpRole("visible"); // 2
    }
    public function getIsVisible() {
        return $this->isVisible;
    }


    public function setRead() {
        $this->read = $this->lookUpRole("read"); // 3
    }
    public function getRead() {
        return $this->read;
    }

    public function setWrite() {
        $this->write = $this->lookUpRole("write"); // 4
    }
    public function getWrite() {
        return $this->write;
    }

    public function setDelete() {
        $this->delete = $this->lookUpRole("delete"); // 6
    }
    public function getDelete() {
        return $this->delete;
    }

    public function setUpload() {
        $this->upload = $this->lookUpRole("rep_robj_xoct_perm_upload"); // 113
    }
    public function getUpload() {
        return $this->upload;
    }

    public function setEditVideos() {
        $this->editVideos = $this->lookUpRole("rep_robj_xoct_perm_edit_videos"); // 114
    }
    public function getEditVideos() {
        return $this->editVideos;
    }

    public function setOperationCreateXOCT() {
        $this->operationCreateXOCT = $this->lookUpRole("create_xoct");
    }
    public function getOperationCreateXOCT() {
        return $this->operationCreateXOCT;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

}
?>