<?php

use Controllers\Admin\StatsValidateController;
use Utils\Uri\SimpleRouter;

?>
<script>

    function checkAll(set)
    {
        $('.chkStatsValidate').prop('checked', set);
    }

</script>

<div class="content2-container">

    <div class="content2-pagetitle">
        {{admin_statcheck_title}}
    </div>
    <p><?= $view->message ?></p>
    <br />

    <form
        action = "<?= SimpleRouter::getLink('Admin.StatsValidate', 'run') ?>"
        method = "post"
        style = "padding: 0 1em 0 1em"
    >

        <div style="columns: 3">
            <p style="line-height: 1.8em">
            <?php $last = false; ?>
            <?php foreach ($view->statsFields as $field) { ?>
                <?php
                    $t = explode('.', $field)[0];
                    if ($last && $t != $last) {
                        echo "<br />";
                    }
                    $last = $t;
                ?>
                <span style="white-space: nowrap;">
                &nbsp;
                <label>
                    <!-- If there were discrepancies found for this field, we will
                         pre-check it for repair. --> 
                    <input
                        type="checkbox" class="chkStatsValidate"
                        name="sv-<?= str_replace('.', '|', $field) ?>"
                        <?php if (!empty($view->results[$field])) { ?>checked<?php } ?> 
                    >
                    <?= $field ?>
                </label>
                <?php if (isset($view->results[$field])) { ?>
                    &nbsp; <span style="color:
                        <?= is_array($view->results[$field]) && count($view->results[$field]) > 0 ? 'red' : 'green' ?>
                    ">
                    <?= is_array($view->results[$field])
                        ? count($view->results[$field]) : $view->results[$field] ?>
                    </span>
                <?php } ?>
                <br />

            <?php } ?>
            </p>
        </div>

        <br />

        <div style="text-align: center">
            <p>
                <a href="#" onclick="checkAll(true)">{{select_all}}</a>
                &bull;
                <a href="#" onclick="checkAll(false)">{{unselect_all}}</a>
            </p>
        </div>

        <input
            type="submit" class="btn btn-default btn-sm" name="check"
            value="{{admin_statcheck_check}}"
        >
        <input
            type="submit" class="btn btn-default btn-sm" name="repair"
            value="{{admin_statcheck_repair}}"
        >

        <br /><br />

        <?php
            // Show lists of objects with discrepancies

            if (!empty($view->results)) {
                foreach ($view->results as $field => $result) {
                    if (is_array($result) && count($result) > 0) {
                        $first = true;
                        echo '<p>'.$field.': ';
                        foreach ($result as $id) {
                            echo
                                (!$first ? ', ' : '') .
                                '<a href="/' . StatsValidateController::objectUri($field, $id) . '"
                                target="_blank">'.$id.'</a>';
                            $first = false;
                        }
                        echo "</p>\n";
                    }
                }
            }
        ?>
    </form>

</div>
