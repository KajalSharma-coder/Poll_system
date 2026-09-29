<?php
require_once __DIR__ . '/../php/json_store.php';

writeJsonData('users.json', jsonDataDefaults('users.json'));
writeJsonData('polls.json', jsonDataDefaults('polls.json'));
writeJsonData('votes.json', []);

echo "JSON demo data initialized.\n";

