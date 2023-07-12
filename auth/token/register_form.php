<?php
$columns = 'row'; //  ($showLogon) ? 'twocolumns' : '';

if (!empty($CFG->loginpasswordautocomplete)) {
    $autocomplete = 'autocomplete="off"';
} else {
    $autocomplete = '';
}
//if (empty($CFG->authloginviaemail)) {
//    $strusername = get_string('username');
//} else {
    $strusername = get_string('usernameemail');
//}

$frmusername = false;
if (isset($frm) and isset($frm->username)) {
    $frmusername = $frm->username;
}

?>
<div class="loginbox clearfix token-help">
  <p>The following information allows certification of course completion and reporting / research that will help in the administration and improvement of this course. All information will be treated as confidential and will only be used in a de-identified form for the purpose of research and or course improvement.</p>
</div>
<div class="loginbox clearfix <?php echo $columns ?>">
  <div class="signuppanel">
    <h2><?php print_string("signup_registerusing","auth_token") ?></h2>
    <div class="subcontent">
    <?php $mform_signup->display(); ?>
    </div>
  </div>

<?php if (!empty($potentialidps)) { ?>
    <div class="subcontent potentialidps">
        <h6><?php print_string('potentialidps', 'auth'); ?></h6>
        <div class="potentialidplist">
<?php foreach ($potentialidps as $idp) {
    echo  '<div class="potentialidp"><a href="' . $idp['url']->out() . '" title="' . $idp['name'] . '">' . $OUTPUT->render($idp['icon'], $idp['name']) . $idp['name'] . '</a></div>';
} ?>
        </div>
    </div>
<?php } ?>
</div>
