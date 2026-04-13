<?php
define('OSTSCPINC', TRUE);
require('scp/secure.inc.php');
require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.ticket.php');

if(!$thisstaff || !$thisstaff->isAdmin()) {
    die('Доступ запрещён');
}

$file = 'closedTicketReport';

$csv_output = "Ticket ID, Department, Priority, Staff Firstname, Staff Surname, Email, Name, Subject, Phone, Status, Source, Reopened, Closed, Created";
$csv_output .= "\n";

$query = "SELECT t.ticket_id, d.dept_name, t.priority_id, s.firstname, s.lastname, t.email, t.name, t.subject, t.phone, t.status, t.source, t.reopened, t.closed, t.created
FROM ".TICKET_TABLE." t
LEFT JOIN ".STAFF_TABLE." s ON t.staff_id = s.staff_id
LEFT JOIN ".DEPT_TABLE." d ON s.dept_id = d.dept_id
WHERE t.staff_id > 0
LIMIT 10000";

$result = db_query($query);

if ($result && db_num_rows($result) > 0) {
    while ($row = db_fetch_row($result)) {
        for ($j = 0; $j < 14; $j++) {
            $csv_output .= '"' . str_replace('"', '""', $row[$j]) . '",';
        }
        $csv_output .= "\n";
    }
}

$filename = $file . "_" . date("Y-m-d_H-i", time());

header("Content-type: text/csv; charset=utf-8");
header("Content-disposition: attachment; filename=" . $filename . ".csv");

print $csv_output;
exit;
?>
