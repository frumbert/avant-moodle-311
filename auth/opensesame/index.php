<?php

require_once('../../config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Hacky Backdoor</title>
    <style>
    a { padding: 1rem; display: block; }
    </style>
</head>
<body>

<h1>Super Hacky Back Door</h1>

<li><a href='/auth/opensesame/login.php?id=10' target=_blank>Tim (admin)</a>
<li><a href='/auth/opensesame/login.php?id=4585' target=_blank>Ruth (admin)</a>

<form method="get" action="/auth/opensesame/login.php" target="_blank">
    <p><label>Any user id:</label>
    <input type="number" size="5" value="0" name="id">
    <input type="submit" value="Logon"></p>
</form>

<table>
    <tr>
        <th>Surgeons</th><th>GPs</th>
    </tr>
    <tr>
<?php
$cols = ['aa_surgeon','aa_gp'];
foreach ($cols as $col) {
    echo "<td>";
    $sql = "select id, concat(firstname, ' ', lastname) name from {user} where id in (
        select userid from {cohort_members} where cohortid in (
            select id from {cohort} where idnumber = ?
        )
    )";
    foreach (($rows = $DB->get_records_sql($sql, array($col))) as $row) {
        echo "<a href='/auth/opensesame/login.php?id=" . $row->id . "' target=_blank>" . $row->name . "</a>";
    }
    echo "</td>";
}
?>
    </tr>
</table>

</body>
</html>