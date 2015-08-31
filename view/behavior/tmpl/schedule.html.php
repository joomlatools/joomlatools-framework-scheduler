<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

defined('KOOWA') or die; ?>

<?= helper('behavior.jquery'); ?>

<script type="text/javascript">
    var csrf_token = <?= json_encode($csrf_token); ?>;

    kQuery.ajax(<?= json_encode($url); ?>)
        .success(function(response, status, xhr) {
            console.log(response);
        })
        .fail(function(xhr) {
            console.log(response);
        });

</script>