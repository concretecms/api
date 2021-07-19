<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$iterator = new DirectoryIterator(__DIR__ . '/api');
$highest = '0';

foreach ($iterator as $version) {
    if ($iterator->isDot() || $iterator->isFile()) {
        continue;
    }

    if (version_compare($highest, $version->getBasename(), '<')) {
        $highest = $version->getBasename();
    }
}

$redirect = "{$highest}/";
header('Location: ' . $redirect);
?>
<script type="application/javascript">
    window.location = <?= json_encode($redirect); ?>;
</script>
