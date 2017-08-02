<?php
/**
 * This db-update script is called from the browser and starts the crs_admin update procedure.
 * It fetches all courses' ref_id's and sets the persmissions of the course admin to allow the creation of
 * SWITCHcast Series objects within each course.
 *
 * Created by PhpStorm.
 * User: tomasz kolonko
 * Date: 16/01/16
 * Time: 21:10
 */

chdir(substr(__FILE__, 0, strpos(__FILE__, '/CourseAdminFix')));


require_once 'CourseAdminFix/class.CustomInitialization.php';
CustomInitialization::initILIAS();


require_once 'CourseAdminFix/class.UpdateClass.php';
$update = new UpdateClass();

try {
    $update->findAllCoursesAndGroupsAndFolders();
    echo '<pre>';
    echo 'All courses found...';
    $update->findAllCourseAndGroupAdminRoles();
    echo '<pre>';
    echo 'All Admin Roles found...';
    $update->findAllCourseTutorRoles();
    echo '<pre>';
    echo 'All Tutor Roles found...';
    $update->addAdminRolesToCoursesAndGroups();
    echo '<pre>';
    echo 'All Roles added...';
    $update->updatePermissionTableForCourses();
    $update->updatePermissionTableForGroups();
    $update->updatePermissionTableForFolders();
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
