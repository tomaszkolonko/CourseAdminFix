<?php

/**
 * Class UpdateClass
 *
 * @author  Tomasz Kolonko <thomas.kolonko@ilub.unibe.ch>
 */
class UpdateClass {

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
     * @var array $crsArray
     */
    protected $crsArray;

    /**
     * @var array $grpArray
     */
    protected $grpArray;

    /**
     * @var array $foldArray
     */
    protected $foldArray;

    /**
     * @var array $courseAdminRoleArray
     */
    protected $courseAdminRoleArray;

    /**
     * @var array $groupAdminRoleArray
     */
    protected $groupAdminRoleArray;

    /**
     * @var array $courseTutorRoleArray
     */
    protected $courseTutorRoleArray;

    /**
     * @var int $operationCreateXOCT
     */
    protected $operationCreateXOCT;

    /**
     * Constructor initializes ilDB and RBAC objects.
     */
    public function __construct() {
        global $ilDB, $rbacadmin, $rbacreview;

        // $this->log = xoctMigrationLog::getInstance();
        $this->db = $ilDB;
        $this->rbac_admin = $rbacadmin;
        $this->rbac_review = $rbacreview;

        $this->setCrsArray();
        $this->setGrpArray();
        $this->setFoldArray();
        $this->setCourseAdminRoleArray();
        $this->setGroupAdminRoleArray();
        $this->setCourseTutorRoleArray();

        $this->operationCreateXOCT = $this->lookUpRole("create_xlvo");
    }

    public function lookUpRole($role) {
        $set = $this->db->query('SELECT ops_id FROM rbac_operations WHERE operation=' . "'" . $role . "'");
        $rec = $this->db->fetchAssoc($set);
        return $rec['ops_id'];
    }

    /**
     * Find all Courses with obj_id and ref_id and put them into an array: $this->crsArray.
     */
    public function findAllCoursesAndGroupsAndFolders() {

        $set = $this->db->query('SELECT * FROM object_data INNER JOIN object_reference ON object_data.obj_id = object_reference.obj_id WHERE type="crs" OR type="grp" OR type="fold"');

        while ($rec = $this->db->fetchAssoc($set)) {
            if($rec['type'] == "crs") {
                $this->addCrsToArray($rec);
            } else if($rec['type'] == "grp") {
                $this->addGrpToArray($rec);
            } else if($rec['type'] == "fold") {
                $this->addFoldToArray($rec);
            } else {
                echo 'unknown type in UpdateClass::findAllCoursesAndGroupsAndFolders()';
            }
        }
    }

    /**
     * Find all course and group admin roles and put them into an their array respectively
     */
    public function findAllCourseAndGroupAdminRoles() {
        $set = $this->db->query('SELECT * FROM object_data WHERE type="role" AND title LIKE "il_crs_admin_%" OR title LIKE "il_grp_admin_%"');

        while ($rec = $this->db->fetchAssoc($set)) {
            if(substr($rec['title'], 0, 6) == 'il_crs') {
                $this->addCrsAdminToArray($rec);
            } else if(substr($rec['title'], 0, 6) == 'il_grp') {
                $this->addGrpAdminToArray($rec);
            } else {
                echo 'unknown title in UpdateClass::findAllCourseAndGroupAdminRoles()';
            }
        }
    }

    /**
     * Find all course tutor roles and put them into the tutor array
     */
    public function findAllCourseTutorRoles() {
        $set = $this->db->query('SELECT * FROM object_data WHERE type="role" AND title LIKE "il_crs_tutor_%"');

        while ($rec = $this->db->fetchAssoc($set)) {
            if(substr($rec['title'], 0, 10) == 'il_crs_tut') {
                $this->addCrsTutorToArray($rec);
            } else {
                echo 'unknown title in UpdateClass::findAllCourseTutorRoles()';
            }
        }
    }

    /**
     * Iterate through all courses and roles and add the specific course admin roles to the correct course in the $this->crsArray.
     */
    public function addAdminRolesToCoursesAndGroups() {

        // Course Admin in Courses
        foreach($this->crsArray as $crs => $field) {
            foreach($this->courseAdminRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->crsArray[$crs]['AdminRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseAdminRoleArray -> crsArray finished\n";

        // Course Admin in Groups
        foreach($this->grpArray as $grp => $field) {
            foreach($this->courseAdminRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->grpArray[$grp]['AdminRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseAdminRoleArray -> grpArray finished\n";

        // Course Admin in Folders
        foreach($this->foldArray as $fold => $field) {
            foreach($this->courseAdminRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->foldArray[$fold]['AdminRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseAdminRoleArray -> foldArray finished\n";

        // Course Tutor in Courses
        foreach($this->crsArray as $crs => $field) {
            foreach($this->courseTutorRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->crsArray[$crs]['TutorRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseTutorRoleArray -> crsArray finished\n";

        // Course Tutor in Groups
        foreach($this->grpArray as $grp => $field) {
            foreach($this->courseTutorRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->grpArray[$grp]['TutorRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseTutorRoleArray -> grpArray finished\n";

        // Course Tutor in Folders
        foreach($this->foldArray as $fold => $field) {
            foreach($this->courseTutorRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->foldArray[$fold]['TutorRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ncourseAdminRoleArray -> crsArray finished\n";

        foreach($this->grpArray as $grp => $field) {
            foreach($this->groupAdminRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->grpArray[$grp]['GroupAdminRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ngroupAdminRoleArray -> grpArray finished\n";


        foreach($this->foldArray as $fold => $field) {
            foreach($this->groupAdminRoleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->foldArray[$fold]['GroupAdminRole_id'] = $role['role_id'];
                    echo ".";
                }
            }
        }
        echo "\ngroupAdminRoleArray -> foldArray finished\n";
    }

    /**
     * Retrieve from DB the current permission table for the course admin role and add the required permission to the
     * array, then update the permission table.
     */
    public function updatePermissionTableForCourses() {
        $counter = 0;

        foreach($this->crsArray as $crs) {
            $old_AdminOps = $this->rbac_review->getRoleOperationsOnObject(
                $crs['AdminRole_id'],
                $crs['ref_id']
            );

            array_push($old_AdminOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $crs['AdminRole_id'],
                array_unique($old_AdminOps),
                $crs['ref_id']
            );

            $old_TutorOps = $this->rbac_review->getRoleOperationsOnObject(
                $crs['TutorRole_id'],
                $crs['ref_id']
            );

            array_push($old_TutorOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $crs['TutorRole_id'],
                array_unique($old_TutorOps),
                $crs['ref_id']
            );

            $counter++;
            echo "\n";
            echo($crs['title'] . " changed");
        }

        echo "\n";
        echo "\n";
        echo($counter . " courses have been updated (for Crs_Admin and Crs_Tutor)");
        echo("\n===========================================================");
    }

    /**
     * Retrieve from DB the current permission table for the group admin role and add the required permission to the
     * array, then update the permission table.
     */
    public function updatePermissionTableForGroups() {
        $counter = 0;

        foreach($this->grpArray as $grp) {
            $old_AdminOps = $this->rbac_review->getRoleOperationsOnObject(
                $grp['AdminRole_id'],
                $grp['ref_id']
            );

            array_push($old_AdminOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $grp['AdminRole_id'],
                array_unique($old_AdminOps),
                $grp['ref_id']
            );



            $old_TutorOps = $this->rbac_review->getRoleOperationsOnObject(
                $grp['TutorRole_id'],
                $grp['ref_id']
            );

            array_push($old_TutorOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $grp['TutorRole_id'],
                array_unique($old_TutorOps),
                $grp['ref_id']
            );



            $old_GrpAdminOps = $this->rbac_review->getRoleOperationsOnObject(
                $grp['GroupAdminRole_id'],
                $grp['ref_id']
            );

            array_push($old_GrpAdminOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $grp['GroupAdminRole_id'],
                array_unique($old_GrpAdminOps),
                $grp['ref_id']
            );

            $counter++;
            echo '<pre>';
            echo($grp['title'] . " changed");
        }

        foreach($this->grpArray as $grp) {

        }

        echo "\n";
        echo "\n";
        echo($counter . " groups have been updated");
        echo("\n============================");
    }

    /**
     * Retrieve from DB the current permission table for the folder admin and tutor role and add the required permission to the
     * array, then update the permission table.
     */
    public function updatePermissionTableForFolders() {
        $counter = 0;

        foreach($this->foldArray as $fold) {
            $old_AdminOps = $this->rbac_review->getRoleOperationsOnObject(
                $fold['AdminRole_id'],
                $fold['ref_id']
            );

            array_push($old_AdminOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $fold['AdminRole_id'],
                array_unique($old_AdminOps),
                $fold['ref_id']
            );



            $old_TutorOps = $this->rbac_review->getRoleOperationsOnObject(
                $fold['TutorRole_id'],
                $fold['ref_id']
            );

            array_push($old_TutorOps, $this->getOperationCreateXOCT());

            $this->rbac_admin->grantPermission(
                $fold['TutorRole_id'],
                array_unique($old_TutorOps),
                $fold['ref_id']
            );

            $counter++;
            echo '<pre>';
            echo($crs['title'] . " changed");
        }

        echo "\n";
        echo "\n";
        echo($counter . " folders have been updated (for Crs_Admin and Crs_Tutor)");
        echo("\n===========================================================");
    }

    public function getOperationCreateXOCT() {
        return $this->operationCreateXOCT;
    }

    /**
     * Adds courses from DB into $this->crsArray[]
     *
     * @param array $rec single course entry from DB query
     */
    private function addCrsToArray($rec) {
        $this->crsArray[] = array('obj_id' => (int)$rec['obj_id'], 'ref_id' =>(int)$rec['ref_id'], 'type' => $rec['type'], 'title' => $rec['title'],
            'description' => $rec['description'], 'role_id' => 0);
    }

    /**
     * Adds groups from DB into $this->grpArray[]
     *
     * @param array $rec single group entry from DB query
     */
    private function addGrpToArray($rec) {
        $this->grpArray[] = array('obj_id' => (int)$rec['obj_id'], 'ref_id' =>(int)$rec['ref_id'], 'type' => $rec['type'], 'title' => $rec['title'],
            'description' => $rec['description'], 'role_id' => 0);
    }

    /**
     * Adds folders from DB into $this->foldArray[]
     *
     * @param array $rec single folder entry from DB query
     */
    private function addFoldToArray($rec) {
        $this->foldArray[] = array('obj_id' => (int)$rec['obj_id'], 'ref_id' =>(int)$rec['ref_id'], 'type' => $rec['type'], 'title' => $rec['title'],
            'description' => $rec['description'], 'role_id' => 0);
    }

    private function addCrsAdminToArray($rec) {
        $this->courseAdminRoleArray[] = array('role_id' => (int)$rec['obj_id'], 'type' => $rec['type'], 'title' => $rec['title'], 'description' => $rec['description']);
    }

    private function addGrpAdminToArray($rec) {
        $this->groupAdminRoleArray[] = array('role_id' => (int)$rec['obj_id'], 'type' => $rec['type'], 'title' => $rec['title'], 'description' => $rec['description']);
    }

    private function addCrsTutorToArray($rec) {
        $this->courseTutorRoleArray[] = array('role_id' => (int)$rec['obj_id'], 'type' => $rec['type'], 'title' => $rec['title'], 'description' => $rec['description']);
    }





    public function setCrsArray() {
        $this->crsArray = array();
    }

    public function getCrsArray() {
        return $this->crsArray;
    }

    public function setGrpArray() {
        $this->grpArray = array();
    }
    public function getGrpArray() {
        return $this->grpArray;
    }

    public function setFoldArray() {
        $this->foldArray = array();
    }

    public function getFoldArray() {
        return $this->foldArray;
    }

    public function setCourseAdminRoleArray() {
        $this->courseAdminRoleArray = array();
    }

    public function getCourseAdminRoleArray() {
        return $this->courseAdminRoleArray;
    }

    public function setGroupAdminRoleArray() {
        $this->groupAdminRoleArray = array();
    }

    public function getGroupAdminRoleArray() {
        return $this->groupAdminRoleArray;
    }

    public function setCourseTutorRoleArray() {
        $this->courseTutorRoleArray = array();
    }

    public function getCourseTutorRoleArray()
    {
        return $this->courseTutorRoleArray;
    }
}
?>