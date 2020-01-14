<?php
// Enable for debug output
define('DEBUG', 1);

$file = 'service_plans.xml';

$xml = new DOMDocument();
$xml->loadXML('<service_plans>' . file_get_contents($file) . '</service_plans>');

$convert = array('wu_script' => 'wuscripts', 'nonexist_mail' => 'no_usr', 'errdocs' => 'err_docs', 'pdir_plesk_stat' => 'webstat_protdir', 'stat_ttl' => 'keep_traf_stat');
$deprecated = array('fp', 'fp_ssl', 'fp_auth', 'php_handler_type', 'php_safe_mode', 'dns_type', 'same_ssl', 'allow_license_stubs');
$sizes = ['disk_space', 'quota', 'mbox_quota'];

$service_plans = $xml->getElementsByTagName('domain-service-plan');

foreach ($service_plans as $service_plan) {
    $command = '/usr/local/psa/bin/service_plan ';
    $service_plan_name = $service_plan->getAttribute('name');
    $service_plan_owner = $service_plan->getAttribute('owner-login');
    // echo "$service_plan_name - Owned by: $service_plan_owner\n";
    $command .= "--create '$service_plan_name' ";
    if ($service_plan_owner != "admin") {
        $command .= "-owner $service_plan_owner ";
    }

    foreach ($service_plan->getElementsByTagName('service-plan-item') as $service_plan_item) {
        $name = $service_plan_item->getAttribute('name');
        $value = $service_plan_item->nodeValue;
        if (($name == 'vh_type') && ($value == 'physical')) {
            $name = 'hosting';
            $value = 'true';
        }
        // Remove deprecated settings
        if (in_array($name, $deprecated)) {
            continue;
        }
        // Convert old settings
        if (in_array($name, array_keys($convert))) {
            $name = $convert[$name];
        }
        // Convert bytes to MB
        if (in_array($name, $sizes)) {
            $value = $value / 1024 / 1024;
            $value .= 'M';
        }
        $command .= "-$name $value ";
    }

    $final_command = trim($command);
    $command_result = `$final_command`;
    if (!preg_match("/(SUCCESS: | successfully )/", $command_result)) {
        print("ERROR: $command_result - $final_command\n");
    }
}
