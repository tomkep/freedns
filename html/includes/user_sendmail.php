<?php
$mailbody = '
' . $l['str_email_this_is_an_automatic_email'] . '

' . sprintf($l['str_email_address_modified_on_x'],$config->sitename) . '
' . sprintf($l['str_email_this_email_is_sent_to_validate_email_x'],addslashes($email)) .'

' . $l['str_email_please_follow_this_link'] . '
' . $config->mainurl . 'validate.php?id=' . $randomid . '&language=' . $lang . '

' . $l['str_email_regards'] . '

--
' . $config->emailsignature . '


';
?>
