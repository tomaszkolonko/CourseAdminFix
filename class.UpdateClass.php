<?php

/**
 * Class UpdateClass
 *
 * @author  Tomasz Kolonko <thomas.kolonko@ilub.unibe.ch>
 */
class UpdateClass {

    /**
     * Permission code retrieved from rbac_operations
     */
    const CRS_ADMIN_ROLE = 102;

    /**
     * @var ilDB $db
     */
    protected $db;

    /**
     * @var array $crsArray
     */
    protected $crsArray;

    /**
     * @var array $roleArray
     */
    protected $roleArray;

    /**
     * Constructor initializes ilDB and RBAC objects.
     */
    public function __construct() {
        global $ilDB, $rbacadmin, $rbacreview;

        // $this->log = xoctMigrationLog::getInstance();
        $this->db = $ilDB;

        $this->rbac_admin = $rbacadmin;
        $this->rbac_review = $rbacreview;

    }

    /**
     * Find all Courses with obj_id and ref_id and put them into an array: $this->crsArray.
     */
    public function findAllCourses() {
        $this->crsArray = array();

        $set = $this->db->query('SELECT * FROM object_data INNER JOIN object_reference USING(obj_id) WHERE type="crs"');

        while ($rec = $this->db->fetchAssoc($set)) {
            $this->crsArray[] = array('obj_id' => (int)$rec['obj_id'], 'ref_id' =>(int)$rec['ref_id'], 'type' => $rec['type'], 'title' => $rec['title'],
                'description' => $rec['description'], 'role_id' => 0);
        }
    }

    /**
     * Find all course admin roles and put them into an array: $this->roleArray
     */
    public function findAllCourseAdminRoles() {
        $this->roleArray = array();

        $set = $this->db->query('SELECT * FROM object_data WHERE type="role" AND title LIKE "il_crs_admin_%"');

        while ($rec = $this->db->fetchAssoc($set)) {
            $this->roleArray[] = array('role_id' => (int)$rec['obj_id'], 'type' => $rec['type'], 'title' => $rec['title'], 'description' => $rec['description']);
        }
    }

    /**
     * Iterate through all courses and roles and add the specific course admin roles to the correct course in the $this->crsArray.
     */
    public function addAdminRolesToCourses() {
        foreach($this->crsArray as $crs => $field) {
            foreach($this->roleArray as $role) {
                if($field['obj_id'] == (int)substr($role['description'], strpos($role['description'], ".") + 1)) {
                    $this->crsArray[$crs]['role_id'] = $role['role_id'];
                }
            }
        }
    }

    /**
     * Retrieve from DB the current permission table for the course admin role and add the required permission to the
     * array, then update the permission table.
     */
    public function getPermissionTable() {
        $counter = 0;

        foreach($this->crsArray as $crs) {
            $old_ops = $this->rbac_review->getRoleOperationsOnObject(
                $crs['role_id'],
                $crs['ref_id']
            );

            array_push($old_ops, CRS_ADMIN_ROLE);

            $this->rbac_admin->grantPermission(
                $crs['role_id'],
                array_unique($old_ops),
                $crs['ref_id']
            );

            $counter++;
            echo '<pre>';
            echo($crs['title'] . " changed");
        }

        echo '<pre>';
        echo '<pre>';
        echo($counter . " courses have been updated");
        echo("\n==============================");
    }

}
?>