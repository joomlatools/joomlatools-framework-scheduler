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
    var request = function() {
        return kQuery.ajax(<?= json_encode($url); ?>).success(function(response) {
            if (typeof response === 'object' && response.continue) {
                setTimeout(request, 1000);
            }
        });
    };

    request();
</script>