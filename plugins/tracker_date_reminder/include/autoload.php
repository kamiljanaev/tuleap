<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloada9aa0c5fe8557aa5451043c3a5d5d309($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'artifactdatereminder' => '/ArtifactDateReminder.class.php',
            'artifactdatereminderfactory' => '/ArtifactDateReminderFactory.class.php',
            'tracker_date_reminderplugin' => '/tracker_date_reminderPlugin.class.php',
            'trackerdatereminder_artifactfield' => '/TrackerDateReminder_ArtifactField.class.php',
            'trackerdatereminder_artifactfieldfactory' => '/TrackerDateReminder_ArtifactFieldFactory.class.php',
            'trackerdatereminder_artifactfieldhtml' => '/TrackerDateReminder_ArtifactFieldHtml.class.php',
            'trackerdatereminder_artifacttype' => '/TrackerDateReminder_ArtifactType.class.php',
            'trackerdatereminder_logger' => '/TrackerDateReminder_Logger.class.php',
            'trackerdatereminder_logger_prefix' => '/TrackerDateReminder_Logger_Prefix.class.php',
            'trackerdatereminderplugindescriptor' => '/TrackerDateReminderPluginDescriptor.class.php',
            'trackerdatereminderplugininfo' => '/TrackerDateReminderPluginInfo.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloada9aa0c5fe8557aa5451043c3a5d5d309');
// @codeCoverageIgnoreEnd
